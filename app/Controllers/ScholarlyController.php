<?php



declare(strict_types=1);



namespace App\Controllers;



use App\Models\AuditLog;

use App\Models\PaperPresentation;

use App\Models\PublishedPaper;

use App\Models\ScholarlyAttachment;



final class ScholarlyController

{

    public function index(): void

    {

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $papers = PublishedPaper::forUser($userId);

        $presentations = PaperPresentation::forUser($userId);



        view('scholarly.index', [

            'profileUser' => \App\Core\Auth::user() ?? [],

            'papers' => $papers,

            'presentations' => $presentations,

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

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $data = $this->paperInput();



        if ($data['title'] === '' || $data['journal_name'] === '') {

            set_flash('error', 'Paper title and journal name are required.');

            redirect('scholarly');

        }



        $id = PublishedPaper::create($userId, $data);

        $this->storeAttachments(ScholarlyAttachment::TYPE_PAPER, $id, $userId, 'supporting_documents');



        AuditLog::record('published_paper', $id, 'create', [

            'title' => $data['title'],

        ]);



        set_flash('success', 'Published paper added.');

        redirect('scholarly');

    }



    public function storePresentation(): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $data = $this->presentationInput();



        if ($data['title'] === '' || $data['conference_name'] === '') {

            set_flash('error', 'Presentation title and conference name are required.');

            redirect('scholarly');

        }



        $id = PaperPresentation::create($userId, $data);

        $this->storeAttachments(ScholarlyAttachment::TYPE_PRESENTATION, $id, $userId, 'supporting_documents');



        AuditLog::record('paper_presentation', $id, 'create', [

            'title' => $data['title'],

        ]);



        set_flash('success', 'Paper presentation added.');

        redirect('scholarly');

    }



    public function uploadPaperAttachments(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $paper = PublishedPaper::find($id);



        if ($paper === null || (int) $paper['user_id'] !== $userId) {

            set_flash('error', 'Published paper not found.');

            redirect('scholarly');

        }



        $this->storeAttachments(ScholarlyAttachment::TYPE_PAPER, $id, $userId, 'supporting_documents');

        set_flash('success', 'Supporting documents uploaded.');

        redirect('scholarly');

    }



    public function uploadPresentationAttachments(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $presentation = PaperPresentation::find($id);



        if ($presentation === null || (int) $presentation['user_id'] !== $userId) {

            set_flash('error', 'Paper presentation not found.');

            redirect('scholarly');

        }



        $this->storeAttachments(ScholarlyAttachment::TYPE_PRESENTATION, $id, $userId, 'supporting_documents');

        set_flash('success', 'Supporting documents uploaded.');

        redirect('scholarly');

    }



    public function downloadAttachment(int $id): void

    {

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $attachment = ScholarlyAttachment::find($id);



        if ($attachment === null || !$this->canAccessAttachment($attachment, $userId)) {

            http_response_code(404);

            exit;

        }



        $this->sendAttachment($attachment);

    }



    public function destroyAttachment(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $attachment = ScholarlyAttachment::find($id);



        if ($attachment === null || !$this->canAccessAttachment($attachment, $userId)) {

            set_flash('error', 'Attachment not found.');

            redirect('scholarly');

        }



        ScholarlyAttachment::delete($id);

        set_flash('success', 'Supporting document removed.');

        redirect('scholarly');

    }



    public function updatePaper(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $paper = PublishedPaper::find($id);

        if ($paper === null || (int) $paper['user_id'] !== $userId) {

            set_flash('error', 'Published paper not found.');

            redirect('scholarly');

        }

        $data = $this->paperInput();

        if ($data['title'] === '' || $data['journal_name'] === '') {

            set_flash('error', 'Paper title and journal name are required.');

            redirect('scholarly#paper-' . $id);

        }

        PublishedPaper::update($id, $data);

        $this->storeAttachments(ScholarlyAttachment::TYPE_PAPER, $id, $userId, 'supporting_documents');

        AuditLog::record('published_paper', $id, 'update', [

            'title' => $data['title'],

        ]);

        set_flash('success', 'Published paper updated.');

        redirect('scholarly#paper-' . $id);

    }

    public function updatePresentation(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $presentation = PaperPresentation::find($id);

        if ($presentation === null || (int) $presentation['user_id'] !== $userId) {

            set_flash('error', 'Paper presentation not found.');

            redirect('scholarly');

        }

        $data = $this->presentationInput();

        if ($data['title'] === '' || $data['conference_name'] === '') {

            set_flash('error', 'Presentation title and conference name are required.');

            redirect('scholarly#presentation-' . $id);

        }

        PaperPresentation::update($id, $data);

        $this->storeAttachments(ScholarlyAttachment::TYPE_PRESENTATION, $id, $userId, 'supporting_documents');

        AuditLog::record('paper_presentation', $id, 'update', [

            'title' => $data['title'],

        ]);

        set_flash('success', 'Paper presentation updated.');

        redirect('scholarly#presentation-' . $id);

    }

    public function destroyPaper(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $paper = PublishedPaper::find($id);



        if ($paper === null || (int) $paper['user_id'] !== $userId) {

            set_flash('error', 'Published paper not found.');

            redirect('scholarly');

        }



        PublishedPaper::delete($id);



        AuditLog::record('published_paper', $id, 'delete', [

            'title' => $paper['title'],

        ]);



        set_flash('success', 'Published paper removed.');

        redirect('scholarly');

    }



    public function destroyPresentation(int $id): void

    {

        if (!verify_csrf()) {

            set_flash('error', 'Invalid session. Please try again.');

            redirect('scholarly');

        }



        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $presentation = PaperPresentation::find($id);



        if ($presentation === null || (int) $presentation['user_id'] !== $userId) {

            set_flash('error', 'Paper presentation not found.');

            redirect('scholarly');

        }



        PaperPresentation::delete($id);



        AuditLog::record('paper_presentation', $id, 'delete', [

            'title' => $presentation['title'],

        ]);



        set_flash('success', 'Paper presentation removed.');

        redirect('scholarly');

    }



    /** @return array<string, mixed> */

    private function paperInput(): array

    {

        $year = (int) ($_POST['publication_year'] ?? 0) ?: null;

        if ($year !== null && ($year < 1900 || $year > 2100)) {

            $year = null;

        }

        $status = strtolower(trim((string) ($_POST['status'] ?? 'published')));

        if (!in_array($status, ['published', 'accepted', 'in_press'], true)) {

            $status = 'published';

        }



        $link = trim((string) ($_POST['link'] ?? ''));

        if ($link !== '' && !preg_match('#^https?://#i', $link)) {

            $link = 'https://' . $link;

        }

        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {

            $link = '';

        }

        return [

            'title' => trim((string) ($_POST['title'] ?? '')),

            'authors' => trim((string) ($_POST['authors'] ?? '')),

            'journal_name' => trim((string) ($_POST['journal_name'] ?? '')),

            'publication_date' => trim((string) ($_POST['publication_date'] ?? '')),

            'publication_year' => $year,

            'doi' => trim((string) ($_POST['doi'] ?? '')),

            'indexing' => trim((string) ($_POST['indexing'] ?? '')),

            'status' => $status,

            'link' => $link,

            'notes' => trim((string) ($_POST['notes'] ?? '')),

        ];

    }



    /** @return array<string, mixed> */

    private function presentationInput(): array

    {

        $type = strtolower(trim((string) ($_POST['presentation_type'] ?? 'oral')));

        if (!in_array($type, ['oral', 'poster', 'virtual', 'other'], true)) {

            $type = 'oral';

        }



        return [

            'title' => trim((string) ($_POST['title'] ?? '')),

            'conference_name' => trim((string) ($_POST['conference_name'] ?? '')),

            'presentation_type' => $type,

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



    /** @param array<string, mixed> $attachment */

    private function canAccessAttachment(array $attachment, int $userId): bool

    {

        if ((int) $attachment['user_id'] === $userId) {

            return true;

        }



        if ($attachment['record_type'] === ScholarlyAttachment::TYPE_PAPER) {

            $paper = PublishedPaper::find((int) $attachment['record_id']);



            return $paper !== null && (int) $paper['user_id'] === $userId;

        }



        $presentation = PaperPresentation::find((int) $attachment['record_id']);



        return $presentation !== null && (int) $presentation['user_id'] === $userId;

    }



    /** @param array<string, mixed> $attachment */

    private function sendAttachment(array $attachment): void

    {

        $path = ScholarlyAttachment::filePath($attachment);

        if (!is_file($path)) {

            http_response_code(404);

            exit;

        }



        $name = basename((string) $attachment['original_name']);

        $asciiName = preg_replace('/[^\x20-\x7E]|["\\\\]/', '_', $name) ?? 'document';

        header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));

        header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));

        header('X-Content-Type-Options: nosniff');

        readfile($path);

        exit;

    }

}


