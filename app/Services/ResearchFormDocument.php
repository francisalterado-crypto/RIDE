<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;
use App\Support\AccessControl;
use App\Support\ResearchFormOptions;
use App\Support\ResearchRequestForms;
use DOMElement;

/**
 * Builds the filled Word form of an RDO proposal from the office's own template (resources/form-templates):
 * the Research Proposal Application (RDO-20) and the request forms (RDO-23, -29 to -32). The result is what
 * Print Preview downloads (opened in Word to view, print or save as PDF), once the form is filled in.
 */
final class ResearchFormDocument
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    private const TEMPLATES = [
        'research' => 'rdo-20.docx',
        'critical_activities_request' => 'rdo-23.docx',
        'research_program_proposal' => 'rdo-29.docx',
        'external_funding_proposal' => 'rdo-30.docx',
        'paper_presentation' => 'rdo-31.docx',
        'manuscript' => 'rdo-32.docx',
    ];

    private const SIX_PS = [
        'patent_granted' => 'Patent granted',
        'publication' => 'Publication',
        'people_trained' => 'People trained',
        'partnership_developed' => 'Partnership developed',
        'products_processes_developed' => 'Products/Processes developed',
        'policies_formulated' => 'Policies formulated',
    ];

    /** @var array<string, mixed> */
    private array $summary;

    private function __construct(private readonly array $proposal, private readonly DocxTemplate $doc)
    {
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $this->summary = is_array($summary) ? $summary : [];
    }

    /** Form key of a proposal ('research' or a request form type), or null if it has no Word template. */
    public static function formKey(array $proposal): ?string
    {
        $type = ResearchRequestForms::typeOf($proposal);
        if ($type !== null) {
            return isset(self::TEMPLATES[$type]) ? $type : null;
        }
        if (proposal_form_type($proposal) === '' && (string) ($proposal['project_type'] ?? '') !== 'extension') {
            return 'research';
        }

        return null;
    }

    /** The filled .docx bytes, or null when the proposal has no Word template. */
    public static function build(array $proposal): ?string
    {
        $key = self::formKey($proposal);
        if ($key === null) {
            return null;
        }
        $self = new self($proposal, DocxTemplate::open(BASE_PATH . '/resources/form-templates/rdo/' . self::TEMPLATES[$key]));
        $key === 'research' ? $self->fillApplication() : $self->fillRequest($key);

        return $self->doc->render();
    }

    /** Download file name, e.g. "Soil Study - RDO-20.docx". */
    public static function fileName(array $proposal): string
    {
        $key = self::formKey($proposal);
        $code = $key === 'research' ? 'RDO-20' : str_replace('WPU-QSF-RIDE-', '', (string) (ResearchRequestForms::get((string) $key)['code'] ?? 'RDO'));
        $title = trim((string) preg_replace('/[^A-Za-z0-9 _.-]+/', '', (string) ($proposal['title'] ?? '')));

        return ($title !== '' ? substr($title, 0, 80) : 'Form') . ' - ' . $code . '.docx';
    }

    // ---- RDO-20 Research Proposal Application ----------------------------------------------------------------

    private function fillApplication(): void
    {
        $doc = $this->doc;
        $doc->appendToCell($doc->cell(0, 1, 0), (string) ($this->proposal['project_code'] ?? ''));

        // I. The Proponent (from the applicant's profile, frozen into the summary).
        $doc->setCell($doc->cell(1, 0, 1), $this->text('applicant_last_name'));
        $doc->setCell($doc->cell(1, 0, 2), $this->text('applicant_first_name'));
        $doc->setCell($doc->cell(1, 0, 3), $this->text('applicant_middle_name'));
        $doc->setCell($doc->cell(1, 2, 1), $this->text('applicant_title_prefix'));
        $doc->setCell($doc->cell(1, 2, 3), $this->text('applicant_sex'));
        $doc->setCell($doc->cell(1, 3, 1), $this->text('applicant_email'));
        $doc->setCell($doc->cell(1, 3, 3), $this->text('applicant_contact_number'));
        $doc->setCell($doc->cell(1, 4, 1), $this->text('applicant_college_department') !== '' ? $this->text('applicant_college_department') : (string) ($this->proposal['college_name'] ?? ''));
        $doc->setCell($doc->cell(1, 4, 3), $this->text('applicant_campus'));
        $doc->setCell($doc->cell(1, 5, 1), $this->text('applicant_google_scholar_link'));
        $doc->setCell($doc->cell(1, 6, 1), $this->text('applicant_researchgate_link'));
        $coAuthors = array_values(array_filter(
            is_array($this->summary['coauthors'] ?? null) ? $this->summary['coauthors'] : [],
            static fn ($c): bool => is_array($c) && trim(implode('', [(string) ($c['last_name'] ?? ''), (string) ($c['first_name'] ?? ''), (string) ($c['middle_name'] ?? '')])) !== ''
        ));
        foreach (['last_name' => 1, 'first_name' => 2, 'middle_name' => 3] as $part => $column) {
            $doc->setCell($doc->cell(1, 7, $column), implode("\n", array_map(static fn (array $c): string => trim((string) ($c[$part] ?? '')), $coAuthors)));
        }

        // II. Project Information.
        $doc->setCell($doc->cell(2, 0, 1), (string) ($this->proposal['title'] ?? ''));
        $doc->setCell($doc->cell(2, 1, 1), $this->text('period_covered'));
        $doc->setCell($doc->cell(2, 1, 3), $this->text('duration_months'));
        $projectTable = $doc->table(2);
        $fundingRow = $projectTable !== null ? ($doc->rows($projectTable)[2] ?? null) : null;
        foreach (ResearchFormOptions::labels($this->summary['funding_support'] ?? [], ResearchFormOptions::FUNDING_SUPPORT) as $label) {
            $doc->tick($label, $fundingRow);
        }
        $sourceRow = $projectTable !== null ? ($doc->rows($projectTable)[3] ?? null) : null;
        if ($sourceRow !== null) {
            $cells = $doc->cells($sourceRow);
            $doc->setCell($cells[count($cells) - 1] ?? null, (string) ($this->proposal['funding_source'] ?? ''));
        }

        foreach ([
            'Abstract' => 'abstract',
            'Graphical Abstract' => 'graphical_abstract_link',
            'Introduction' => 'introduction',
            'Research Gaps' => 'research_gaps',
            'Significance of the Study' => 'significance',
            'Objectives' => 'objectives',
            'Materials and Methods' => 'methods',
            'Gender and Development' => 'gender_development',
            'Ethical Considerations' => 'ethical_considerations',
            'Expected Outcomes' => 'expected_outcomes',
            'Sustainability Plan' => 'sustainability_plan',
            'Literature Cited' => 'literature_cited',
        ] as $heading => $key) {
            $doc->fillAfterHeading($heading, $this->text($key));
        }
        $doc->fillAfterHeading('CHED A.C.H.I.E.V.E.', $this->optionLines('ched_achieve', ResearchFormOptions::CHED_ACHIEVE));
        $doc->fillAfterHeading('Sustainable Development Goal', $this->optionLines('sdgs', ResearchFormOptions::SDGS));

        // Budget (in-house funded research).
        $doc->appendToCell($doc->cell(3, 0, 0), $this->text('budget_total'));
        $budget = [];
        foreach (is_array($this->summary['budget_items'] ?? null) ? $this->summary['budget_items'] : [] as $item) {
            if (is_array($item) && trim((string) ($item['item'] ?? '')) !== '') {
                $budget[] = [(string) $item['item'], (string) ($item['amount'] ?? ''), (string) ($item['justification'] ?? '')];
            }
        }
        $doc->fillRows($doc->table(3), 2, $budget, 1);
        $budgetTable = $doc->table(3);
        if ($budgetTable !== null) {
            $resourcesRow = $doc->rowByLabel($budgetTable, 'Resources Available');
            $doc->setCell($resourcesRow !== null ? ($doc->cells($resourcesRow)[1] ?? null) : null, $this->text('resources_available'));
        }

        $this->fillTimeline($doc->table(4), $this->summary['implementation_plan'] ?? []);

        foreach (is_array($this->summary['six_ps'] ?? null) ? $this->summary['six_ps'] : [] as $item) {
            if (is_string($item) && isset(self::SIX_PS[$item])) {
                $doc->tick(self::SIX_PS[$item]);
            }
        }

        // Sign-off: applicant date, then the R&D Coordinator's and Dean's endorsements from the approvals.
        $doc->fillBlanksAfter('SIGNATURE OF THE APPLICANT', [null, proposal_format_workflow_date($this->proposal['submitted_at'] ?? null)]);
        foreach ([
            'ENDORSEMENT (R&D COORDINATOR)' => [AccessControl::COORDINATOR_RESEARCH, 'R&D Coordinator'],
            'APPROVAL (COLLEGE DEAN' => [AccessControl::DEAN, 'College Dean/Immediate Supervisor'],
        ] as $heading => [$step, $position]) {
            $sign = $this->signatory($step, $position);
            if ($sign !== null) {
                $doc->fillBlanksAfter($heading, [null, $sign['date'], $sign['name'], $sign['mobile'], $sign['position'], $sign['email']]);
            }
        }
    }

    // ---- Request forms (RDO-23, -29 to -32) --------------------------------------------------------------------

    private function fillRequest(string $type): void
    {
        $doc = $this->doc;
        $controlNo = (string) ($this->proposal['project_code'] ?? '');

        if ($type === 'critical_activities_request') {
            $doc->setCell($doc->cell(0, 0, 1), $this->text('contact_name') !== '' ? $this->text('contact_name') : $this->researcherName());
            $doc->setCell($doc->cell(0, 1, 1), implode(' / ', array_filter([$this->text('contact_mobile'), $this->text('contact_email')])));
            $doc->setCell($doc->cell(1, 0, 1), (string) ($this->proposal['title'] ?? ''));
            $doc->setCell($doc->cell(1, 1, 1), $this->text('ride_control_no'));
            $columns = array_keys((array) (ResearchRequestForms::get($type)['fields']['activities']['columns'] ?? []));
            $rows = [];
            foreach (is_array($this->summary['activities'] ?? null) ? $this->summary['activities'] : [] as $row) {
                if (is_array($row)) {
                    $rows[] = array_map(static fn (string $c): string => (string) ($row[$c] ?? ''), $columns);
                }
            }
            $doc->fillRows($doc->table(2), 1, $rows);
        } else {
            $doc->appendToCell($doc->cell(0, 1, 0), $controlNo);
            $main = $doc->table(1);
            foreach ($main !== null ? $doc->rows($main) : [] as $row) {
                $this->fillRequestRow($row);
            }
            if ($type === 'research_program_proposal') {
                $plan = $doc->table(2);
                if ($plan !== null) {
                    // The template's "FY 20__ (delete row if within same year)" divider: activities are not split
                    // per fiscal year, so the range goes in the heading and the divider is dropped.
                    $doc->removeRow($doc->rowByLabel($plan, 'FY 20__'));
                }
                $this->fillTimeline($plan, $this->summary['implementation_plan'] ?? []);
            }
        }

        $this->fillRequestSignoff();
    }

    private function fillRequestRow(DOMElement $row): void
    {
        $doc = $this->doc;
        $cells = $doc->cells($row);
        if (count($cells) < 2) {
            return;
        }
        $label = strtolower(trim($doc->text($cells[0])));
        $value = $cells[count($cells) - 1];
        $is = static fn (string $prefix): bool => str_starts_with($label, $prefix);

        if ($is('research category') || $is('category')) {
            $key = $is('category') ? 'presentation_category' : 'research_category';
            $definition = ResearchRequestForms::get((string) ResearchRequestForms::typeOf($this->proposal)) ?? [];
            $option = (string) ($definition['fields'][$key]['options'][strtolower($this->text($key))] ?? '');
            if ($option !== '') {
                $doc->tick($option, $row);
            }
            return;
        }

        $text = match (true) {
            $is('working title') => (string) ($this->proposal['title'] ?? ''),
            $is('program/project') => $this->studyOrigin(),
            $is('lead researcher') => $this->text('lead_researchers'),
            $is('co-researchers') => $this->text('co_researchers'),
            $is('target funding source'), $is('funding source') => $this->fundingSource(),
            $is('amount') => $this->text('amount'),
            $is('duration') => $this->dateRange('duration_from', 'duration_to'),
            $is('target journal') => $this->text('target_journal'),
            $is('target date of presentation') => $this->date($this->text('target_presentation_date')),
            $is('target symposium') => $this->text('target_conference'),
            $is('organizer') => $this->text('organizer'),
            $is('sdg') => $this->optionLines('sdgs', ResearchFormOptions::SDGS),
            $is('ched') => $this->optionLines('ched_achieve', ResearchFormOptions::CHED_ACHIEVE),
            $is('gender and development') => $this->text('gender_development'),
            $is('brief rationale') => $this->text('brief_rationale'),
            $is('objectives') => $this->text('objectives'),
            $is('general methodology') => $this->text('general_methodology'),
            $is('expected outcomes') => $this->text('expected_outcomes'),
            $is('highlights of results') => $this->text('highlights_of_results'),
            default => '',
        };
        $doc->setCell($value, $text);
    }

    private function fillRequestSignoff(): void
    {
        $doc = $this->doc;
        $signers = [
            'submitted by' => [
                ['name' => $this->researcherName(), 'date' => proposal_format_workflow_date($this->proposal['submitted_at'] ?? null)],
                $this->signatory(AccessControl::COORDINATOR_RESEARCH, 'R&D Coordinator'),
            ],
            'recommending approval' => [
                $this->signatory(AccessControl::DEAN, 'College Dean/Immediate Supervisor'),
                $this->signatory(AccessControl::DIRECTOR_RESEARCH, 'Director, Research and Development Office'),
            ],
            'approval' => [
                $this->signatory(AccessControl::VPRIDE, 'Vice President for Research, Innovation, Development, and Extension'),
            ],
        ];
        for ($i = 0; ($table = $doc->table($i)) !== null; $i++) {
            $rows = $doc->rows($table);
            $heading = strtolower(trim($doc->text($rows[0] ?? $table)));
            foreach ($signers as $prefix => $people) {
                if (!str_starts_with($heading, $prefix) || !isset($rows[1])) {
                    continue;
                }
                foreach ($doc->cells($rows[1]) as $c => $cell) {
                    $person = $people[$c] ?? null;
                    if (is_array($person) && trim((string) $person['name']) !== '') {
                        $doc->setCell($cell, trim($person['name'] . "\n" . $person['date']));
                    }
                }
                break;
            }
        }
    }

    // ---- Shared -----------------------------------------------------------------------------------------------

    /** Time-frame grid: heading years in row 0, month names in row 1, then one row per activity. */
    private function fillTimeline(?DOMElement $table, mixed $plan): void
    {
        if ($table === null) {
            return;
        }
        $from = $this->text('plan_year_from');
        $to = $this->text('plan_year_to') !== '' ? $this->text('plan_year_to') : $from;
        $this->doc->fillYears($this->doc->rows($table)[0] ?? null, $from, $to);

        $rows = [];
        foreach (is_array($plan) ? $plan : [] as $row) {
            if (!is_array($row) || trim((string) ($row['activity'] ?? '')) === '') {
                continue;
            }
            $months = is_array($row['months'] ?? null) ? $row['months'] : [];
            $shade = [];
            foreach (self::MONTHS as $i => $month) {
                if (in_array($month, $months, true)) {
                    $shade[] = $i + 1;
                }
            }
            $rows[] = ['cells' => [(string) $row['activity']], 'shade' => $shade];
        }
        $this->doc->fillRows($table, 2, $rows);
    }

    /** @return array{name: string, date: string, mobile: string, position: string, email: string}|null */
    private function signatory(string $step, string $position): ?array
    {
        $id = (int) ($this->proposal['id'] ?? 0);
        $approval = $id > 0 ? Proposal::approvalAtStep($id, $step) : null;
        if ($approval === null) {
            return null;
        }
        $sign = proposal_step_endorsement($id, $step, $position) ?? [];
        $user = User::findById((int) ($approval['user_id'] ?? 0)) ?? [];

        return [
            'name' => (string) ($sign['name'] ?? ''),
            'date' => (string) ($sign['date'] ?? ''),
            'mobile' => (string) ($user['contact_number'] ?? ''),
            'position' => trim((string) ($user['position'] ?? '')) !== '' ? (string) $user['position'] : $position,
            'email' => (string) ($sign['email'] ?? ''),
        ];
    }

    private function researcherName(): string
    {
        return trim((string) ($this->proposal['first_name'] ?? '') . ' ' . (string) ($this->proposal['last_name'] ?? ''));
    }

    private function studyOrigin(): string
    {
        $status = strtolower($this->text('study_origin_status'));
        $line = match ($status) {
            'ongoing' => 'Ongoing',
            'completed' => 'Completed' . ($this->text('study_origin_completion_date') !== '' ? ' (' . $this->date($this->text('study_origin_completion_date')) . ')' : ''),
            default => '',
        };

        return trim($this->text('study_origin_details') . "\n" . $line);
    }

    private function fundingSource(): string
    {
        $source = trim((string) ($this->proposal['funding_source'] ?? ''));
        $downloaded = $this->text('funds_downloaded');

        return trim($source . ($downloaded !== '' ? "\nFunds downloaded to WPU: " . $downloaded : ''));
    }

    private function dateRange(string $fromKey, string $toKey): string
    {
        $from = $this->date($this->text($fromKey));
        $to = $this->date($this->text($toKey));

        return $from !== '' || $to !== '' ? trim($from . ' to ' . $to, ' to') : $this->text('duration_writing');
    }

    /** Y-m-d > mm/dd/yyyy, the format the forms ask for. */
    private function date(string $value): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $parsed instanceof \DateTimeImmutable ? $parsed->format('m/d/Y') : $value;
    }

    /** @param array<string, string> $options */
    private function optionLines(string $key, array $options): string
    {
        return implode("\n", ResearchFormOptions::labels($this->summary[$key] ?? [], $options));
    }

    private function text(string $key): string
    {
        $value = $this->summary[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
