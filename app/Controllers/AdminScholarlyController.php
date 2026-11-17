<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\PaperPresentation;
use App\Models\PublishedPaper;
use App\Models\ScholarlyAttachment;
use App\Models\User;

final class AdminScholarlyController
{
    public function index(): void
    {
        $this->assertAdmin();

        $collegeId = (int) ($_GET['college_id'] ?? 0) ?: null;
        $papers = PublishedPaper::forMonitoring($collegeId);
        $presentations = PaperPresentation::forMonitoring($collegeId);

        view('admin.scholarly.index', [
            'colleges' => College::all(),
            'collegeId' => $collegeId,
            'faculty' => User::allFaculty(),
            'paperStats' => PublishedPaper::stats($collegeId),
            'presentationStats' => PaperPresentation::stats($collegeId),
            'papers' => $papers,
            'presentations' => $presentations,
            'facultySummary' => $this->facultySummary($collegeId),
            'paperAttachments' => ScholarlyAttachment::groupedForRecords(
                ScholarlyAttachment::TYPE_PAPER,
                array_column($papers, 'id')
            ),
            'presentationAttachments' => ScholarlyAttachment::groupedForRecords(
                ScholarlyAttachment::TYPE_PRESENTATION,
                array_column($presentations, 'id')
            ),
        ]);
    }

    public function storePaper(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/scholarly');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $data = $this->paperInput();

        if ($userId <= 0 || $data['title'] === '' || $data['journal_name'] === '') {
            set_flash('error', 'Faculty, paper title, and journal name are required.');
            redirect('admin/scholarly');
        }

        if (User::findById($userId) === null) {
            set_flash('error', 'Selected faculty account was not found.');
            redirect('admin/scholarly');
        }

        $id = PublishedPaper::create($userId, $data);
        $this->storeAttachments(ScholarlyAttachment::TYPE_PAPER, $id, $userId, 'supporting_documents');

        AuditLog::record('published_paper', $id, 'create', [
            'user_id' => $userId,
            'title' => $data['title'],
            'by_admin' => true,
        ]);

        set_flash('success', 'Published paper recorded.');
        redirect('admin/scholarly');
    }

    public function storePresentation(): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/scholarly');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $data = $this->presentationInput();

        if ($userId <= 0 || $data['title'] === '' || $data['conference_name'] === '') {
            set_flash('error', 'Faculty, presentation title, and conference name are required.');
            redirect('admin/scholarly');
        }

        if (User::findById($userId) === null) {
            set_flash('error', 'Selected faculty account was not found.');
            redirect('admin/scholarly');
        }

        $id = PaperPresentation::create($userId, $data);
        $this->storeAttachments(ScholarlyAttachment::TYPE_PRESENTATION, $id, $userId, 'supporting_documents');

        AuditLog::record('paper_presentation', $id, 'create', [
            'user_id' => $userId,
            'title' => $data['title'],
            'by_admin' => true,
        ]);

        set_flash('success', 'Paper presentation recorded.');
        redirect('admin/scholarly');
    }

    public function downloadAttachment(int $id): void
    {
        $this->assertAdmin();

        $attachment = ScholarlyAttachment::find($id);
        if ($attachment === null) {
            http_response_code(404);
            exit;
        }

        $path = ScholarlyAttachment::filePath($attachment);
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }

        send_stored_file($path, (string) $attachment['original_name']);
    }

    public function destroyPaper(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/scholarly');
        }

        $paper = PublishedPaper::find($id);
        if ($paper === null) {
            set_flash('error', 'Published paper not found.');
            redirect('admin/scholarly');
        }

        PublishedPaper::delete($id);

        AuditLog::record('published_paper', $id, 'delete', [
            'title' => $paper['title'],
        ]);

        set_flash('success', 'Published paper removed.');
        redirect('admin/scholarly');
    }

    public function destroyPresentation(int $id): void
    {
        $this->assertAdmin();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('admin/scholarly');
        }

        $presentation = PaperPresentation::find($id);
        if ($presentation === null) {
            set_flash('error', 'Paper presentation not found.');
            redirect('admin/scholarly');
        }

        PaperPresentation::delete($id);

        AuditLog::record('paper_presentation', $id, 'delete', [
            'title' => $presentation['title'],
        ]);

        set_flash('success', 'Paper presentation removed.');
        redirect('admin/scholarly');
    }

    /** @return list<array<string, mixed>> */
    private function facultySummary(?int $collegeId): array
    {
        $faculty = User::allFaculty();
        $papers = PublishedPaper::forMonitoring($collegeId);
        $presentations = PaperPresentation::forMonitoring($collegeId);

        $paperCounts = [];
        foreach ($papers as $paper) {
            $uid = (int) $paper['user_id'];
            $paperCounts[$uid] = ($paperCounts[$uid] ?? 0) + 1;
        }

        $presentationCounts = [];
        foreach ($presentations as $presentation) {
            $uid = (int) $presentation['user_id'];
            $presentationCounts[$uid] = ($presentationCounts[$uid] ?? 0) + 1;
        }

        $summary = [];
        foreach ($faculty as $member) {
            $id = (int) $member['id'];
            if ($collegeId !== null && (int) ($member['college_id'] ?? 0) !== $collegeId) {
                continue;
            }

            $paperCount = $paperCounts[$id] ?? 0;
            $presentationCount = $presentationCounts[$id] ?? 0;
            if ($paperCount === 0 && $presentationCount === 0) {
                continue;
            }

            $summary[] = [
                'id' => $id,
                'name' => trim($member['first_name'] . ' ' . $member['last_name']),
                'email' => $member['email'],
                'college_name' => $member['college_name'] ?? '-',
                'program' => $member['program'] ?? '',
                'papers' => $paperCount,
                'presentations' => $presentationCount,
            ];
        }

        usort($summary, static function (array $a, array $b): int {
            return ($b['papers'] + $b['presentations']) <=> ($a['papers'] + $a['presentations']);
        });

        return $summary;
    }

    /** @return array<string, mixed> */
    private function paperInput(): array
    {
        $year = (int) ($_POST['publication_year'] ?? 0) ?: null;

        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'authors' => trim((string) ($_POST['authors'] ?? '')),
            'journal_name' => trim((string) ($_POST['journal_name'] ?? '')),
            'publication_date' => trim((string) ($_POST['publication_date'] ?? '')),
            'publication_year' => $year,
            'doi' => trim((string) ($_POST['doi'] ?? '')),
            'indexing' => trim((string) ($_POST['indexing'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'published'),
            'link' => trim((string) ($_POST['link'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function presentationInput(): array
    {
        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'conference_name' => trim((string) ($_POST['conference_name'] ?? '')),
            'presentation_type' => (string) ($_POST['presentation_type'] ?? 'oral'),
            'presentation_date' => trim((string) ($_POST['presentation_date'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'is_international' => !empty($_POST['is_international']),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function storeAttachments(string $recordType, int $recordId, int $userId, string $field): void
    {
        $files = $_FILES[$field] ?? [];
        if ($files === [] || ScholarlyAttachment::normalizeUploadedFiles($files) === []) {
            return;
        }

        $result = ScholarlyAttachment::storeMany($recordType, $recordId, $userId, $files);
        if ($result['errors'] !== []) {
            set_flash('error', 'Some supporting documents could not be uploaded: ' . implode(' ', $result['errors']));
        }
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
