<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\HighlightSlide;

final class HighlightController
{
    public const DEFAULT_VISION = 'A leading university research and extension ecosystem that drives innovation, community development, and evidence-based solutions for Western Philippines and beyond.';
    public const DEFAULT_MISSION = 'To monitor, support, and streamline research and extension workflows across Western Philippines University, empowering faculty and partners through transparent approval processes and accountable reporting.';

    public function index(): void
    {
        $this->assertAdmin();

        view('admin.highlights.index', [
            'slides' => HighlightSlide::all(),
            'vision' => AppSetting::get('portal_vision', self::DEFAULT_VISION),
            'mission' => AppSetting::get('portal_mission', self::DEFAULT_MISSION),
        ]);
    }

    public function updateSettings(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/highlights');
        }

        $vision = trim((string) ($_POST['vision'] ?? ''));
        $mission = trim((string) ($_POST['mission'] ?? ''));

        if ($vision === '' || $mission === '') {
            set_flash('error', 'Vision and mission are required.');
            redirect('admin/highlights');
        }

        $userId = (int) (Auth::user()['id'] ?? 0) ?: null;
        AppSetting::put('portal_vision', $vision, $userId);
        AppSetting::put('portal_mission', $mission, $userId);
        AuditLog::record('portal_settings', null, 'update');

        set_flash('success', 'Vision and mission updated.');
        redirect('admin/highlights');
    }

    public function store(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/highlights');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $caption = trim((string) ($_POST['caption'] ?? ''));

        if ($title !== '' && mb_strlen($title) > 200) {
            set_flash('error', 'Title must be 200 characters or fewer.');
            redirect('admin/highlights');
        }

        $files = $this->normalizeUploadedFiles($_FILES['images'] ?? $_FILES['image'] ?? []);
        if ($files === []) {
            set_flash('error', 'Please select at least one image to upload.');
            redirect('admin/highlights');
        }

        $createdBy = (int) (Auth::user()['id'] ?? 0) ?: null;
        $uploaded = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $id = HighlightSlide::create($file, $title, $caption, $createdBy);
                AuditLog::record('highlight_slide', $id, 'create');
                $uploaded++;
            } catch (\Throwable $e) {
                $label = (string) ($file['name'] ?? ('Image ' . ($index + 1)));
                $errors[] = $label . ': ' . $e->getMessage();
            }
        }

        if ($uploaded > 0 && $errors === []) {
            set_flash(
                'success',
                $uploaded === 1 ? 'Highlight slide uploaded.' : $uploaded . ' highlight slides uploaded.'
            );
        } elseif ($uploaded > 0) {
            set_flash(
                'success',
                $uploaded . ' of ' . count($files) . ' slides uploaded. ' . implode(' ', $errors)
            );
        } else {
            set_flash('error', implode(' ', $errors) ?: 'Image upload failed.');
        }

        redirect('admin/highlights');
    }

    /**
     * Normalize single or multi-file $_FILES payloads into a list of file arrays.
     *
     * @param array<string, mixed> $filesField
     * @return list<array<string, mixed>>
     */
    private function normalizeUploadedFiles(array $filesField): array
    {
        if ($filesField === []) {
            return [];
        }

        // Single file: ['name' => '...', 'tmp_name' => '...', ...]
        if (!isset($filesField['name']) || !is_array($filesField['name'])) {
            $error = (int) ($filesField['error'] ?? UPLOAD_ERR_NO_FILE);
            return $error === UPLOAD_ERR_NO_FILE ? [] : [$filesField];
        }

        $normalized = [];
        $count = count($filesField['name']);
        for ($i = 0; $i < $count; $i++) {
            $error = (int) ($filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = [
                'name' => (string) ($filesField['name'][$i] ?? ''),
                'type' => (string) ($filesField['type'][$i] ?? ''),
                'tmp_name' => (string) ($filesField['tmp_name'][$i] ?? ''),
                'error' => $error,
                'size' => (int) ($filesField['size'][$i] ?? 0),
            ];
        }

        return $normalized;
    }

    public function update(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/highlights');
        }

        $slide = HighlightSlide::find($id);
        if ($slide === null) {
            set_flash('error', 'Slide not found.');
            redirect('admin/highlights');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $caption = trim((string) ($_POST['caption'] ?? ''));
        $isActive = isset($_POST['is_active']);

        if ($title !== '' && mb_strlen($title) > 200) {
            set_flash('error', 'Title must be 200 characters or fewer.');
            redirect('admin/highlights');
        }

        HighlightSlide::update($id, $title, $caption, $isActive);
        AuditLog::record('highlight_slide', $id, 'update');
        set_flash('success', 'Highlight slide updated.');
        redirect('admin/highlights');
    }

    public function move(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/highlights');
        }

        $direction = (int) ($_POST['direction'] ?? 0);
        if ($direction === -1 || $direction === 1) {
            HighlightSlide::move($id, $direction);
            AuditLog::record('highlight_slide', $id, 'reorder');
            set_flash('success', 'Slide order updated.');
        }

        redirect('admin/highlights');
    }

    public function destroy(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/highlights');
        }

        $slide = HighlightSlide::find($id);
        if ($slide === null) {
            set_flash('error', 'Slide not found.');
            redirect('admin/highlights');
        }

        HighlightSlide::delete($id);
        AuditLog::record('highlight_slide', $id, 'delete');
        set_flash('success', 'Highlight slide deleted.');
        redirect('admin/highlights');
    }

    private function assertAdmin(): void
    {
        if (!Auth::hasRole('ride_admin')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }
    }
}
