<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\ExtensionActivity;
use App\Models\ExtensionActivityDocument;
use App\Support\ExtensionActivityTypes;
use App\Support\MonitoringRoles;
use App\Support\QuarterlyReporting;

/**
 * Entries that feed the quarterly reports, entered one at a time: Conducted Activities (ESO, /activities)
 * and Research Accomplishments (RDO, /research-accomplishments). Both share this controller; an entry's
 * office follows from its type.
 */
final class ExtensionActivityController
{
    private const MAX_TEXT = 2000;

    public function index(): void
    {
        $this->listPage('eso');
    }

    public function researchIndex(): void
    {
        $this->listPage('rdo');
    }

    public function create(string $slug): void
    {
        $type = $this->typeFromSlugOr404($slug);
        if ($type === null || !$this->allowAccess(ExtensionActivityTypes::officeOf($type))) {
            return;
        }

        $this->renderForm($type, null, '', []);
    }

    public function store(string $slug): void
    {
        $type = $this->typeFromSlugOr404($slug);
        if ($type === null || !$this->allowAccess(ExtensionActivityTypes::officeOf($type))) {
            return;
        }
        $base = self::basePathFor($type);
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect($base . '/new/' . $slug);
        }

        [$section, $data, $errors] = $this->validatedInput($type);
        if ($errors !== []) {
            $this->renderForm($type, null, $section, $data, $errors);
            return;
        }

        $user = Auth::user() ?? [];
        $collegeId = (int) ($user['college_id'] ?? 0);
        $id = ExtensionActivity::create((int) $user['id'], $collegeId > 0 ? $collegeId : null, $type, $section, $data);
        AuditLog::record('extension_activity', $id, 'created');
        $uploadErrors = $this->storeUploadedDocuments($id, $type);

        $label = (string) ExtensionActivityTypes::get($type)['label'];
        $terms = ExtensionActivityTypes::officeTerms(ExtensionActivityTypes::officeOf($type));
        set_flash('success', $label . ' ' . $terms['entry'] . ' saved.' . $this->uploadErrorText($uploadErrors));
        if ($uploadErrors !== []) {
            redirect($base . '/' . $id . '/edit');
        }
        if (isset($_POST['save_and_add'])) {
            redirect($base . '/new/' . $slug);
        }
        redirect($base . '?type=' . $slug . '&period=' . $this->periodForDate($data, $type, $section));
    }

    public function edit(int $id): void
    {
        $entry = $this->ownedEntryOr404($id);
        if ($entry === null) {
            return;
        }

        $this->renderForm((string) $entry['activity_type'], $entry, (string) $entry['section'], $entry['data']);
    }

    public function update(int $id): void
    {
        $entry = $this->ownedEntryOr404($id);
        if ($entry === null) {
            return;
        }
        $type = (string) $entry['activity_type'];
        $base = self::basePathFor($type);
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect($base . '/' . $id . '/edit');
        }

        [$section, $data, $errors] = $this->validatedInput($type);
        if ($errors !== []) {
            $this->renderForm($type, $entry, $section, $data, $errors);
            return;
        }

        ExtensionActivity::update($id, $section, $data);
        AuditLog::record('extension_activity', $id, 'updated');
        $uploadErrors = $this->storeUploadedDocuments($id, $type);
        $terms = ExtensionActivityTypes::officeTerms(ExtensionActivityTypes::officeOf($type));
        set_flash('success', ucfirst($terms['entry']) . ' updated.' . $this->uploadErrorText($uploadErrors));
        if ($uploadErrors !== []) {
            redirect($base . '/' . $id . '/edit');
        }
        $slug = (string) ExtensionActivityTypes::get($type)['slug'];
        redirect($base . '?type=' . $slug . '&period=' . $this->periodForDate($data, $type, $section));
    }

    public function destroy(int $id): void
    {
        $entry = $this->ownedEntryOr404($id);
        if ($entry === null) {
            return;
        }
        $type = (string) $entry['activity_type'];
        $base = self::basePathFor($type);
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect($base);
        }

        ExtensionActivityDocument::deleteFilesForActivity($id);
        ExtensionActivity::delete($id);
        AuditLog::record('extension_activity', $id, 'deleted');
        $terms = ExtensionActivityTypes::officeTerms(ExtensionActivityTypes::officeOf($type));
        set_flash('success', ucfirst($terms['entry']) . ' deleted.');
        redirect($base);
    }

    public function show(int $id): void
    {
        $entry = ExtensionActivity::find($id);
        if ($entry === null || !self::canView($entry)) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $type = (string) $entry['activity_type'];
        view('activities.show', [
            'pageTitle' => ExtensionActivityTypes::get($type)['label'] . ' | RIDE Hub',
            'entry' => $entry,
            'definition' => ExtensionActivityTypes::get($type),
            'checklist' => ExtensionActivityTypes::documentChecklist($type),
            'documents' => ExtensionActivityDocument::forActivity($id),
            'isOwner' => (int) $entry['user_id'] === (int) (Auth::user()['id'] ?? 0),
        ]);
    }

    public function downloadDocument(int $id, int $documentId): void
    {
        $entry = ExtensionActivity::find($id);
        $document = ExtensionActivityDocument::find($documentId);
        if ($entry === null || $document === null || (int) $document['activity_id'] !== $id || !self::canView($entry)) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $path = ExtensionActivityDocument::filePath($document);
        if (!is_file($path)) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $name = (string) $document['original_name'];
        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'document';
        $inline = preg_match('/\.(pdf|jpe?g|png)$/i', $name) === 1;
        header('Content-Type: ' . ((string) ($document['mime_type'] ?? '') ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function destroyDocument(int $id, int $documentId): void
    {
        $entry = $this->ownedEntryOr404($id);
        if ($entry === null) {
            return;
        }
        $base = self::basePathFor((string) $entry['activity_type']);
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect($base . '/' . $id . '/edit');
        }

        $document = ExtensionActivityDocument::find($documentId);
        if ($document !== null && (int) $document['activity_id'] === $id) {
            ExtensionActivityDocument::delete($documentId);
            AuditLog::record('extension_activity', $id, 'document_deleted', ['document' => (string) $document['original_name']]);
            set_flash('success', 'File removed.');
        }
        redirect($base . '/' . $id . '/edit');
    }

    /** URL prefix for an entry's pages (activities or research-accomplishments). */
    public static function basePathFor(string $type): string
    {
        return ExtensionActivityTypes::basePath(ExtensionActivityTypes::officeOf($type));
    }

    /**
     * Who may open an entry and its files: the person who entered it, people of the same college, and the
     * university-wide reviewers of that office's quarterly reports (its Director, VPRIDE, RIDE Admin).
     *
     * @param array<string, mixed> $entry
     */
    public static function canView(array $entry): bool
    {
        $user = Auth::user() ?? [];
        if ((int) $entry['user_id'] === (int) ($user['id'] ?? 0)) {
            return true;
        }
        $isResearch = ExtensionActivityTypes::officeOf((string) $entry['activity_type']) === 'rdo';
        $isDirector = $isResearch ? MonitoringRoles::isDirectorResearch() : MonitoringRoles::isDirectorExtension();
        if ($isDirector || MonitoringRoles::isVpride() || Auth::hasRole('ride_admin')) {
            return true;
        }
        $collegeId = (int) ($user['college_id'] ?? 0);

        return $collegeId > 0 && $collegeId === (int) ($entry['college_id'] ?? 0);
    }

    /**
     * Whose entries a user sees and compiles into reports: the office's report preparer (Field
     * Extensionist for ESO, R&D Coordinator for RDO) covers their whole college, everyone else only their
     * own entries.
     *
     * @param array<string, mixed> $user
     * @return array{user_id?: int, college_id?: int}
     */
    public static function reportScope(array $user, string $office = 'eso'): array
    {
        $collegeId = (int) ($user['college_id'] ?? 0);
        $isPreparer = $office === 'rdo' ? MonitoringRoles::isCoordinatorResearch() : MonitoringRoles::isCoordinatorExtension();
        if ($collegeId > 0 && $isPreparer) {
            return ['college_id' => $collegeId];
        }

        return ['user_id' => (int) ($user['id'] ?? 0)];
    }

    private function listPage(string $office): void
    {
        if (!$this->allowAccess($office)) {
            return;
        }

        $user = Auth::user() ?? [];
        $type = ExtensionActivityTypes::fromSlug(trim((string) ($_GET['type'] ?? ''))) ?? '';
        if ($type !== '' && ExtensionActivityTypes::officeOf($type) !== $office) {
            $type = '';
        }
        $period = trim((string) ($_GET['period'] ?? ''));
        if ($period !== 'all' && !QuarterlyReporting::isPeriodKeyValid($period)) {
            $period = QuarterlyReporting::currentPeriodKey() ?? 'all';
        }
        $range = $period !== 'all' ? ExtensionActivityTypes::periodDateRange($period) : null;
        $scope = self::reportScope($user, $office);
        $entries = ExtensionActivity::list($scope, $type !== '' ? $type : null, $range[0] ?? null, $range[1] ?? null);
        if ($type === '') {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => ExtensionActivityTypes::officeOf((string) $entry['activity_type']) === $office
            ));
        }

        $terms = ExtensionActivityTypes::officeTerms($office);
        view('activities.index', [
            'pageTitle' => $terms['page'] . ' | RIDE Hub',
            'office' => $office,
            'entries' => $entries,
            'selectedType' => $type,
            'selectedPeriod' => $period,
            'periodRange' => $range,
            'isCollegeScope' => isset($scope['college_id']),
            'currentUserId' => (int) ($user['id'] ?? 0),
            'canGenerateReports' => $office === 'rdo'
                ? MonitoringRoles::canPrepareResearchQuarterlyReports()
                : MonitoringRoles::canAccessTrainingsConducted(),
        ]);
    }

    /**
     * @param array<string, mixed>|null $entry
     * @param array<string, mixed> $values
     * @param list<string> $errors
     */
    private function renderForm(string $type, ?array $entry, string $section, array $values, array $errors = []): void
    {
        $definition = ExtensionActivityTypes::get($type);
        if ($section === '' || !isset($definition['sections'][$section])) {
            $section = (string) array_key_first($definition['sections']);
        }

        view('activities.form', [
            'documents' => $entry !== null ? ExtensionActivityDocument::forActivity((int) $entry['id']) : [],
            'checklist' => ExtensionActivityTypes::documentChecklist($type),
            'pageTitle' => ($entry === null ? 'New ' : 'Edit ') . $definition['label'] . ' Entry | RIDE Hub',
            'type' => $type,
            'definition' => $definition,
            'entry' => $entry,
            'section' => $section,
            'values' => $values,
            'errors' => $errors,
        ]);
    }

    /** @return array{0: string, 1: array<string, string>, 2: list<string>} */
    private function validatedInput(string $type): array
    {
        $definition = ExtensionActivityTypes::get($type);
        $section = strtolower(trim((string) ($_POST['section'] ?? '')));
        if (!isset($definition['sections'][$section])) {
            $section = (string) array_key_first($definition['sections']);
        }

        $posted = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
        $data = [];
        $errors = [];
        $fields = ExtensionActivityTypes::fieldsFor($type, $section);
        foreach ($fields as $name => $field) {
            $raw = trim((string) ($posted[$name] ?? ''));
            $value = match ($field['type']) {
                'number' => $raw === '' ? '' : (string) max(0, (int) $raw),
                'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $raw : '',
                'select' => array_key_exists(strtolower($raw), $field['options'] ?? []) ? strtolower($raw) : '',
                default => mb_substr($raw, 0, self::MAX_TEXT),
            };
            $data[$name] = $value;

            if (!empty($field['required']) && $value === '') {
                $errors[] = self::fieldLabel($field) . ' is required.';
            }
        }

        // The date that places the entry in a reporting quarter is always needed, even where the field is
        // optional for other categories of the same type.
        $dateField = ExtensionActivityTypes::dateFieldFor($type, $section);
        if ($dateField !== '' && isset($fields[$dateField]) && empty($fields[$dateField]['required']) && $data[$dateField] === '') {
            $errors[] = self::fieldLabel($fields[$dateField]) . ' is required for this category.';
        }

        return [$section, $data, $errors];
    }

    /** @param array<string, mixed> $field */
    private static function fieldLabel(array $field): string
    {
        return isset($field['group']) ? $field['group'] . ' - ' . $field['label'] : (string) $field['label'];
    }

    /**
     * Saves the files picked for the type's checklist (input name documents[category][]).
     *
     * @return list<string> one message per file that could not be saved
     */
    private function storeUploadedDocuments(int $activityId, string $type): array
    {
        $categories = ExtensionActivityTypes::documentCategories($type);
        $files = $_FILES['documents'] ?? null;
        if ($categories === [] || !is_array($files) || !is_array($files['name'] ?? null)) {
            return [];
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        $errors = [];
        foreach ($files['name'] as $category => $names) {
            if (!isset($categories[$category]) || !is_array($names)) {
                continue;
            }
            foreach ($names as $i => $name) {
                $error = (int) ($files['error'][$category][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    ExtensionActivityDocument::store($activityId, $userId, (string) $category, [
                        'name' => (string) $name,
                        'type' => (string) ($files['type'][$category][$i] ?? ''),
                        'tmp_name' => (string) ($files['tmp_name'][$category][$i] ?? ''),
                        'error' => $error,
                        'size' => (int) ($files['size'][$category][$i] ?? 0),
                    ]);
                } catch (\RuntimeException $e) {
                    $errors[] = $name . ': ' . $e->getMessage();
                }
            }
        }

        return $errors;
    }

    /** @param list<string> $errors */
    private function uploadErrorText(array $errors): string
    {
        return $errors === [] ? '' : ' Some files were not uploaded: ' . implode(' ', $errors);
    }

    /** @param array<string, string> $data */
    private function periodForDate(array $data, string $type, string $section): string
    {
        $date = (string) ($data[ExtensionActivityTypes::dateFieldFor($type, $section)] ?? '');
        if ($date !== '') {
            foreach (array_keys(QuarterlyReporting::periodOptions(2, 1)) as $key) {
                $range = ExtensionActivityTypes::periodDateRange($key);
                if ($range !== null && $date >= $range[0] && $date <= $range[1]) {
                    return $key;
                }
            }
        }

        return 'all';
    }

    private function typeFromSlugOr404(string $slug): ?string
    {
        $type = ExtensionActivityTypes::fromSlug($slug);
        if ($type === null) {
            http_response_code(404);
            view('errors.404');
        }

        return $type;
    }

    /** @return array<string, mixed>|null */
    private function ownedEntryOr404(int $id): ?array
    {
        $entry = ExtensionActivity::find($id);
        if ($entry !== null && !$this->allowAccess(ExtensionActivityTypes::officeOf((string) $entry['activity_type']))) {
            return null;
        }
        if ($entry === null || (int) $entry['user_id'] !== (int) (Auth::user()['id'] ?? 0)) {
            http_response_code(404);
            view('errors.404');
            return null;
        }

        return $entry;
    }

    private function allowAccess(string $office): bool
    {
        $allowed = $office === 'rdo'
            ? MonitoringRoles::canEnterResearchAccomplishments()
            : MonitoringRoles::canEnterConductedActivities();
        if ($allowed) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }
}
