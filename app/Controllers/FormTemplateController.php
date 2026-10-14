<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\FormTemplate;
use App\Support\MonitoringRoles;

final class FormTemplateController
{
    /** Downloadable forms visible to the signed-in user. */
    public function index(): void
    {
        view('forms.index', [
            'templates' => FormTemplate::activeFor(self::visibleAudiences()),
            'pageTitle' => 'Downloadable Forms — RIDE IMS',
        ]);
    }

    public function download(int $id): void
    {
        $template = FormTemplate::find($id);
        $isAdmin = Auth::hasRole('ride_admin');
        if ($template === null
            || (!$isAdmin && (!(bool) $template['is_active'] || !in_array($template['audience'], self::visibleAudiences(), true)))) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $path = FormTemplate::filePath($template);
        if (!is_file($path)) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $name = (string) $template['original_name'];
        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'form-template';
        header('Content-Type: ' . ((string) ($template['mime_type'] ?? '') ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function adminIndex(): void
    {
        view('admin.form-templates.index', [
            'templates' => FormTemplate::all(),
        ]);
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/form-templates');
        }

        [$title, $description, $audience] = $this->validatedMeta();
        $file = $_FILES['file'] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            set_flash('error', 'Please choose a file to upload.');
            redirect('admin/form-templates');
        }

        try {
            $id = FormTemplate::create($file, $title, $description, $audience, (int) (Auth::user()['id'] ?? 0) ?: null);
        } catch (\RuntimeException $e) {
            set_flash('error', $e->getMessage());
            redirect('admin/form-templates');
        }

        AuditLog::record('form_template', $id, 'create');
        set_flash('success', 'Form template uploaded.');
        redirect('admin/form-templates');
    }

    public function update(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/form-templates');
        }

        if (FormTemplate::find($id) === null) {
            set_flash('error', 'Form template not found.');
            redirect('admin/form-templates');
        }

        [$title, $description, $audience] = $this->validatedMeta();
        FormTemplate::update($id, $title, $description, $audience, isset($_POST['is_active']));

        $file = $_FILES['file'] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                FormTemplate::replaceFile($id, $file);
            } catch (\RuntimeException $e) {
                set_flash('error', 'Details saved, but the file was not replaced: ' . $e->getMessage());
                redirect('admin/form-templates');
            }
        }

        AuditLog::record('form_template', $id, 'update');
        set_flash('success', 'Form template updated.');
        redirect('admin/form-templates');
    }

    public function move(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/form-templates');
        }

        $direction = (int) ($_POST['direction'] ?? 0);
        if ($direction === -1 || $direction === 1) {
            FormTemplate::move($id, $direction);
            AuditLog::record('form_template', $id, 'reorder');
        }

        redirect('admin/form-templates');
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/form-templates');
        }

        FormTemplate::delete($id);
        AuditLog::record('form_template', $id, 'delete');
        set_flash('success', 'Form template deleted.');
        redirect('admin/form-templates');
    }

    /** @return list<string> */
    public static function visibleAudiences(): array
    {
        return match (MonitoringRoles::proposalScopeType()) {
            'extension' => [FormTemplate::AUDIENCE_EXTENSION, FormTemplate::AUDIENCE_ALL],
            'research' => [FormTemplate::AUDIENCE_RESEARCH, FormTemplate::AUDIENCE_ALL],
            default => array_keys(FormTemplate::AUDIENCE_LABELS),
        };
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function validatedMeta(): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        // POST values are title-cased globally in bootstrap; option keys are lowercase.
        $audience = strtolower(trim((string) ($_POST['audience'] ?? '')));

        if ($title === '' || mb_strlen($title) > 200) {
            set_flash('error', 'Title is required and must be 200 characters or fewer.');
            redirect('admin/form-templates');
        }
        if (!array_key_exists($audience, FormTemplate::AUDIENCE_LABELS)) {
            set_flash('error', 'Choose who can download this form.');
            redirect('admin/form-templates');
        }

        return [$title, $description, $audience];
    }
}
