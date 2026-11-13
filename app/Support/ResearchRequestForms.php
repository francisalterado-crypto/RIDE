<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Proposal;

/**
 * The RDO request forms filed by researchers (WPU-QSF-RIDE-RDO-23, -29 to -32, Rev.01 10.12.26). They are
 * saved as proposals (summary.form_type) so they go through the usual approval chain: Researcher > R&D
 * Coordinator > Dean > RDO Director > VPRIDE.
 *
 * Forms that refer to an existing research offer a picker of the researcher's approved Research Proposal
 * Applications; the picked one's RIDE Control No. (its project code), title and researchers fill the form.
 *
 * Field types: text, textarea, date, number, radio (options), checks (options), rows (columns), timeline.
 * A field with 'column' is stored in that proposals column instead of the summary.
 */
final class ResearchRequestForms
{
    /** @return array<string, array<string, mixed>> form_type => definition */
    public static function all(): array
    {
        $sdgs = ['label' => 'SDG/s', 'type' => 'checks', 'options' => ResearchFormOptions::SDGS];
        $ched = ['label' => 'CHED A.C.H.I.E.V.E.', 'type' => 'checks', 'options' => ResearchFormOptions::CHED_ACHIEVE];
        $gad = ['label' => 'Gender and Development', 'type' => 'textarea'];
        $lead = ['label' => 'Lead Researcher/s', 'type' => 'textarea', 'required' => true, 'default' => 'proponent'];
        $co = ['label' => 'Co-researchers/collaborators', 'type' => 'textarea'];
        $fundsDownloaded = ['label' => 'For externally funded, specify if funds are downloaded to WPU', 'type' => 'text'];
        $origin = [
            'study_origin_status' => ['label' => 'Ongoing or completed', 'type' => 'radio', 'options' => ['ongoing' => 'Ongoing', 'completed' => 'Completed'], 'group' => 'Program/Project/Study origin'],
            'study_origin_completion_date' => ['label' => 'Date of completion (if completed)', 'type' => 'date', 'group' => 'Program/Project/Study origin'],
        ];

        return [
            'critical_activities_request' => [
                'label' => 'Critical Activities Request',
                'slug' => 'critical-activities',
                'code' => 'WPU-QSF-RIDE-RDO-23',
                'heading' => 'CRITICAL ACTIVITIES REQUEST FORM',
                'description' => 'Request to continue critical lab-based research activities.',
                'research_link' => 'required',
                'intro' => 'For purposes of lab-based research, critical research activities are those that would be impossible to restart; could not be restarted without a significant investment of time; would require significant financial resources to be restarted; involve seasonality; are extremely time-sensitive; or are critical graduate student experiments required to ensure graduation during this calendar year.',
                'contact' => true,
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'column' => 'title', 'from_research' => 'title'],
                    'activities' => ['label' => 'Critical Activities Requested to Continue', 'type' => 'rows', 'columns' => [
                        'research_activity' => 'Research Activity',
                        'justification' => 'Description & Justification for Why this Activity is Critical',
                        'location' => 'Location',
                        'team_members' => 'Names of Researcher/Team Member(s)',
                        'length_of_time' => 'Length of Time Activities Must Continue',
                    ]],
                ],
            ],
            'research_program_proposal' => [
                'label' => 'Program/Project/Study/Center Proposal',
                'slug' => 'program-project-study-center',
                'code' => 'WPU-QSF-RIDE-RDO-29',
                'heading' => 'RESEARCH PROGRAM, PROJECT, STUDY, CENTER PROPOSAL FORM',
                'description' => 'Proposal for a new research program, project, study or center.',
                'research_link' => null,
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'research_category' => ['label' => 'Research Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'program' => 'Program', 'project' => 'Project', 'study' => 'Study', 'center' => 'Center',
                    ]],
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'amount' => ['label' => 'Amount (PhP)', 'type' => 'text', 'hint' => 'For research conducted on official time, leave blank.'],
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission', 'type' => 'date', 'group' => 'Duration'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                    'expected_outcomes' => ['label' => 'Expected Outcomes', 'type' => 'textarea'],
                    'implementation_plan' => ['label' => 'Implementation Plan and Time Frame', 'type' => 'timeline', 'year_label' => 'FY'],
                ],
            ],
            'external_funding_proposal' => [
                'label' => 'Writing Proposal for External Funding',
                'slug' => 'external-funding',
                'code' => 'WPU-QSF-RIDE-RDO-30',
                'heading' => 'WRITING PROPOSAL FOR EXTERNAL FUNDING FORM',
                'description' => 'Request to write a research proposal for external funding.',
                'research_link' => null,
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'research_category' => ['label' => 'Research Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'program' => 'Program', 'project' => 'Project', 'study' => 'Study',
                    ]],
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Target Funding Source', 'type' => 'text', 'required' => true, 'column' => 'funding_source'],
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission', 'type' => 'date', 'group' => 'Duration'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                ],
            ],
            'paper_presentation' => [
                'label' => 'Paper Presentation',
                'slug' => 'paper-presentation',
                'code' => 'WPU-QSF-RIDE-RDO-31',
                'heading' => 'RESEARCH/INNOVATION/EXTENSION PAPER PRESENTATION FORM',
                'description' => 'Request to present a research, innovation or extension paper.',
                'research_link' => 'optional',
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'study_origin_details' => ['label' => 'Program/Project/Study', 'type' => 'textarea', 'group' => 'Program/Project/Study origin', 'from_research' => 'origin'],
                ] + $origin + [
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'presentation_category' => ['label' => 'Category', 'type' => 'radio', 'required' => true, 'options' => [
                        'research' => 'Research', 'innovation' => 'Innovation', 'extension' => 'Extension',
                    ]],
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source', 'from_research' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'target_presentation_date' => ['label' => 'Target date of Presentation', 'type' => 'date'],
                    'target_conference' => ['label' => 'Target Symposium/Forum/Conference (if already decided)', 'type' => 'text'],
                    'organizer' => ['label' => 'Organizer', 'type' => 'text'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'highlights_of_results' => ['label' => 'Highlights of results', 'type' => 'textarea'],
                ],
            ],
            'manuscript' => [
                'label' => 'Manuscript Writing for Publication',
                'slug' => 'manuscript',
                'code' => 'WPU-QSF-RIDE-RDO-32',
                'heading' => 'MANUSCRIPT WRITING FOR PUBLICATION FORM',
                'description' => 'Request to write a manuscript for journal publication.',
                'research_link' => 'optional',
                'fields' => [
                    'title' => ['label' => 'Working Title', 'type' => 'text', 'required' => true, 'column' => 'title'],
                    'study_origin_details' => ['label' => 'Program/Project/Study', 'type' => 'textarea', 'group' => 'Program/Project/Study origin', 'from_research' => 'origin'],
                ] + $origin + [
                    'lead_researchers' => $lead,
                    'co_researchers' => $co,
                    'funding_source' => ['label' => 'Funding Source', 'type' => 'text', 'column' => 'funding_source', 'from_research' => 'funding_source'],
                    'funds_downloaded' => $fundsDownloaded,
                    'duration_from' => ['label' => 'Start of writing', 'type' => 'date', 'group' => 'Duration'],
                    'duration_to' => ['label' => 'Target submission to journal', 'type' => 'date', 'group' => 'Duration'],
                    'target_journal' => ['label' => 'Target Journal/publisher', 'type' => 'text'],
                    'sdgs' => $sdgs,
                    'ched_achieve' => $ched,
                    'gender_development' => $gad,
                    'brief_rationale' => ['label' => 'Brief Rationale', 'type' => 'textarea'],
                    'objectives' => ['label' => 'Objectives', 'type' => 'textarea'],
                    'general_methodology' => ['label' => 'General Methodology', 'type' => 'textarea'],
                    'highlights_of_results' => ['label' => 'Highlights of results', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $formType): ?array
    {
        return self::all()[$formType] ?? null;
    }

    public static function fromSlug(string $slug): ?string
    {
        foreach (self::all() as $formType => $definition) {
            if ($definition['slug'] === $slug) {
                return $formType;
            }
        }

        return null;
    }

    /** The request form type of a saved proposal, or null when it is not a request form. */
    public static function typeOf(?array $proposal): ?string
    {
        $formType = proposal_form_type($proposal);

        return isset(self::all()[$formType]) ? $formType : null;
    }

    /**
     * Approved Research Proposal Applications the user leads or co-authors, newest first. The project code
     * minted on final approval is the RIDE Control No. the request forms ask for.
     *
     * @return list<array{id: int, control_no: string, title: string, lead: string, coauthors: string, funding_source: string, status: string}>
     */
    public static function approvedResearchFor(int $userId): array
    {
        $options = [];
        foreach (Proposal::forUser($userId) as $proposal) {
            $isResearchApplication = proposal_is_research_application($proposal)
                || (proposal_form_type($proposal) === '' && (string) ($proposal['project_type'] ?? '') === 'research');
            if (!$isResearchApplication
                || !in_array((string) ($proposal['status'] ?? ''), ['ongoing', 'approved', 'completed'], true)
                || trim((string) ($proposal['project_code'] ?? '')) === '') {
                continue;
            }

            $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
            $summary = is_array($summary) ? $summary : [];
            $coauthors = [];
            foreach (is_array($summary['coauthors'] ?? null) ? $summary['coauthors'] : [] as $coauthor) {
                $name = trim((string) ($coauthor['first_name'] ?? '') . ' ' . (string) ($coauthor['last_name'] ?? ''));
                if (is_array($coauthor) && $name !== '') {
                    $coauthors[] = $name;
                }
            }

            $options[] = [
                'id' => (int) $proposal['id'],
                'control_no' => (string) $proposal['project_code'],
                'title' => (string) $proposal['title'],
                'lead' => trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? '')),
                'coauthors' => implode(', ', $coauthors),
                'funding_source' => (string) ($proposal['funding_source'] ?? ''),
                'status' => (string) $proposal['status'] === 'completed' ? 'completed' : 'ongoing',
            ];
        }

        return $options;
    }

    /** Field types whose value is a list rather than a string. */
    public static function isListType(string $type): bool
    {
        return in_array($type, ['checks', 'rows', 'timeline'], true);
    }
}
