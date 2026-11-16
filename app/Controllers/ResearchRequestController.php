<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Support\AccessControl;
use App\Support\ProponentProfile;
use App\Support\ResearchRequestForms;

/**
 * The RDO request forms (Critical Activities, Program/Project/Study/Center, External Funding, Paper
 * Presentation, Manuscript Writing). Saved as proposals, so viewing, submitting and approving go through
 * ProposalController like every other form.
 */
final class ResearchRequestController
{
    private const MAX_TEXT = 10000;
    private const MAX_ROWS = 30;

    public function create(string $slug): void
    {
        $formType = $this->formTypeOr404($slug);
        if ($formType === null || !$this->allowAccess()) {
            return;
        }

        $this->renderForm($formType, null, []);
    }

    public function store(string $slug): void
    {
        $formType = $this->formTypeOr404($slug);
        if ($formType === null || !$this->allowAccess()) {
            return;
        }
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('research-requests/new/' . $slug);
        }

        $user = Auth::user() ?? [];
        [$data, $summary, $errors] = $this->validatedInput($formType, (int) $user['id']);
        if ($errors !== []) {
            $this->renderForm($formType, null, $summary + ['title' => $data['title'], 'funding_source' => $data['funding_source']], $errors);
            return;
        }

        $id = Proposal::create($data + [
            'user_id' => (int) $user['id'],
            'college_id' => (int) ($user['college_id'] ?? 0) ?: null,
            'campus_id' => (int) ($user['campus_id'] ?? 0) ?: null,
        ]);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', ResearchRequestForms::get($formType)['label'] . ' saved as draft.');
        redirect('proposals/' . $id);
    }

    public function edit(int $id): void
    {
        $proposal = $this->editableOr404($id);
        if ($proposal === null) {
            return;
        }

        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $values = (is_array($summary) ? $summary : []) + [
            'title' => (string) $proposal['title'],
            'funding_source' => (string) ($proposal['funding_source'] ?? ''),
        ];
        $this->renderForm((string) ResearchRequestForms::typeOf($proposal), $proposal, $values);
    }

    public function update(int $id): void
    {
        $proposal = $this->editableOr404($id);
        if ($proposal === null) {
            return;
        }
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('research-requests/' . $id . '/edit');
        }

        $formType = (string) ResearchRequestForms::typeOf($proposal);
        [$data, $summary, $errors] = $this->validatedInput($formType, (int) $proposal['user_id']);
        if ($errors !== []) {
            $this->renderForm($formType, $proposal, $summary + ['title' => $data['title'], 'funding_source' => $data['funding_source']], $errors);
            return;
        }

        Proposal::update($id, $data);
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', ResearchRequestForms::get($formType)['label'] . ' updated.');
        redirect('proposals/' . $id);
    }

    /** Print preview of a request form's current, unsaved values. */
    public function preview(string $slug): void
    {
        $formType = $this->formTypeOr404($slug);
        if ($formType === null || !$this->allowAccess()) {
            return;
        }
        if (!verify_csrf()) {
            http_response_code(419);
            echo 'Invalid session. Reload the form and try again.';
            return;
        }

        $user = Auth::user() ?? [];
        $existing = null;
        $id = (int) ($_POST['preview_proposal_id'] ?? 0);
        if ($id > 0) {
            $existing = $this->editableOr404($id);
            if ($existing === null) {
                return;
            }
        }
        [$data] = $this->validatedInput($formType, (int) ($existing['user_id'] ?? $user['id']));
        $proposal = array_merge($existing ?? [
            'id' => 0,
            'status' => 'draft',
            'project_code' => '',
            'current_step' => '',
            'submitted_at' => null,
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
        ], $data);

        send_form_docx($proposal, true);
    }

    /**
     * @param array<string, mixed>|null $proposal
     * @param array<string, mixed> $values
     * @param list<string> $errors
     */
    private function renderForm(string $formType, ?array $proposal, array $values, array $errors = []): void
    {
        $definition = ResearchRequestForms::get($formType);
        $ownerId = $proposal !== null ? (int) $proposal['user_id'] : (int) (Auth::user()['id'] ?? 0);
        view('requests.form', [
            'pageTitle' => ($proposal === null ? 'New ' : 'Edit ') . $definition['label'] . ' | RIDE Hub',
            'formType' => $formType,
            'definition' => $definition,
            'proposal' => $proposal,
            'values' => $values,
            'errors' => $errors,
            'researchOptions' => $definition['research_link'] !== null ? ResearchRequestForms::approvedResearchFor($ownerId) : [],
            'proponent' => \App\Models\User::findById($ownerId) ?? [],
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: list<string>} proposal columns, summary, errors
     */
    private function validatedInput(string $formType, int $ownerId): array
    {
        $definition = ResearchRequestForms::get($formType);
        $summary = ['form_type' => $formType, 'version' => 2];
        $columns = ['title' => '', 'funding_source' => ''];
        $errors = [];

        // Linked research (RIDE Control No.)
        $linked = null;
        if ($definition['research_link'] !== null) {
            $linkedId = (int) ($_POST['linked_proposal_id'] ?? 0);
            foreach (ResearchRequestForms::approvedResearchFor($ownerId) as $option) {
                if ($option['id'] === $linkedId) {
                    $linked = $option;
                    break;
                }
            }
            $summary['linked_proposal_id'] = $linked['id'] ?? 0;
            $summary['ride_control_no'] = $linked['control_no'] ?? '';
            if ($definition['research_link'] === 'required' && $linked === null) {
                $errors[] = 'Select the approved research this request is for.';
            }
        }

        foreach ($definition['fields'] as $name => $field) {
            $raw = $_POST[$name] ?? null;
            $value = match ($field['type']) {
                'checks' => \App\Support\ResearchFormOptions::picked($raw ?? [], $field['options']),
                'radio' => array_key_exists(strtolower(trim((string) $raw)), $field['options']) ? strtolower(trim((string) $raw)) : '',
                'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $raw)) === 1 ? trim((string) $raw) : '',
                'rows' => $this->validatedRows($raw, array_keys($field['columns'])),
                'timeline' => $this->validatedTimeline($raw),
                default => mb_substr(trim((string) $raw), 0, self::MAX_TEXT),
            };
            if (($field['from_research'] ?? null) === 'title' && $linked !== null) {
                $value = $linked['title'];
            }

            if (!empty($field['required']) && ($value === '' || $value === [])) {
                $errors[] = $field['label'] . ' is required.';
            }

            if (isset($field['column'])) {
                $columns[$field['column']] = mb_substr((string) $value, 0, 255);
            } else {
                $summary[$name] = $value;
            }
        }

        if (!empty($definition['contact'])) {
            // Contact person: the program/project leader's profile.
            $owner = \App\Models\User::findById($ownerId) ?? [];
            $summary['contact_name'] = ProponentProfile::displayName($owner);
            $summary['contact_mobile'] = (string) ($owner['contact_number'] ?? '');
            $summary['contact_email'] = (string) ($owner['email'] ?? '');
        }
        if (isset($_POST['plan_year_from']) || isset($_POST['plan_year_to'])) {
            foreach (['plan_year_from', 'plan_year_to'] as $key) {
                $year = trim((string) ($_POST[$key] ?? ''));
                $summary[$key] = preg_match('/^\d{4}$/', $year) === 1 ? $year : '';
            }
        }

        return [[
            'title' => $columns['title'],
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '',
            'project_type' => 'research',
            'funding_source' => $columns['funding_source'],
            'risk_level' => 'low',
            'ethics_required' => false,
        ], $summary, $errors];
    }

    /**
     * @param list<string> $columns
     * @return list<array<string, string>>
     */
    private function validatedRows(mixed $rows, array $columns): array
    {
        $result = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || count($result) >= self::MAX_ROWS) {
                continue;
            }
            $entry = [];
            foreach ($columns as $column) {
                $entry[$column] = mb_substr(trim((string) ($row[$column] ?? '')), 0, 2000);
            }
            if (implode('', $entry) !== '') {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /** @return list<array{activity: string, months: list<string>}> */
    private function validatedTimeline(mixed $rows): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $result = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || count($result) >= self::MAX_ROWS) {
                continue;
            }
            $activity = mb_substr(trim((string) ($row['activity'] ?? '')), 0, 2000);
            $picked = array_values(array_intersect($months, array_map(
                static fn ($m): string => ucfirst(strtolower((string) $m)),
                is_array($row['months'] ?? null) ? $row['months'] : []
            )));
            if ($activity !== '') {
                $result[] = ['activity' => $activity, 'months' => $picked];
            }
        }

        return $result;
    }

    private function formTypeOr404(string $slug): ?string
    {
        $formType = ResearchRequestForms::fromSlug($slug);
        if ($formType === null) {
            http_response_code(404);
            view('errors.404');
        }

        return $formType;
    }

    /** @return array<string, mixed>|null a request form the current user owns and may still edit */
    private function editableOr404(int $id): ?array
    {
        if (!$this->allowAccess()) {
            return null;
        }
        $proposal = Proposal::find($id);
        $user = Auth::user() ?? [];
        if ($proposal === null
            || ResearchRequestForms::typeOf($proposal) === null
            || (int) $proposal['user_id'] !== (int) ($user['id'] ?? 0)
            || !in_array((string) $proposal['status'], ['draft', 'returned'], true)) {
            http_response_code(404);
            view('errors.404');
            return null;
        }

        return $proposal;
    }

    private function allowAccess(): bool
    {
        if (AccessControl::canFileResearchRequests()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }
}
