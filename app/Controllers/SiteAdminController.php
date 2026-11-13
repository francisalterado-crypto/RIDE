<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\SitePost;
use App\Support\SiteContent;

/** RIDE admin: posts and texts of the public site. */
final class SiteAdminController
{
    public function index(): void
    {
        $kind = strtolower((string) ($_GET['kind'] ?? ''));
        $posts = SitePost::all();
        if (isset(SitePost::KINDS[$kind])) {
            $posts = array_values(array_filter($posts, static fn (array $p): bool => $p['kind'] === $kind));
        }
        view('admin.site.index', [
            'pageTitle' => 'Public Site | RIDE Hub',
            'pageHeading' => 'Public Site',
            'pageSubtitle' => 'News, The Palawan Scientist and conference posts shown on the public RIDE site.',
            'posts' => $posts,
            'kind' => $kind,
            'content' => SiteContent::all(),
        ]);
    }

    public function create(): void
    {
        $kind = strtolower((string) ($_GET['kind'] ?? 'news'));
        $series = strtolower((string) ($_GET['series'] ?? ''));
        $this->renderForm(null, [
            'kind' => isset(SitePost::KINDS[$kind]) ? $kind : 'news',
            'series' => isset(SitePost::SERIES[$series]) ? $series : null,
            'is_published' => true,
        ]);
    }

    public function edit(int $id): void
    {
        $post = SitePost::find($id);
        if ($post === null) {
            set_flash('error', 'Post not found.');
            redirect('admin/public-site');
        }
        $this->renderForm($post, $post);
    }

    public function store(): void
    {
        $this->guard('admin/public-site/posts/new');
        [$data, $errors] = $this->input();
        $image = $this->uploadedImage();
        if ($errors !== []) {
            $this->back(null, $errors);
        }
        try {
            $id = SitePost::create($data, $image, (int) (Auth::user()['id'] ?? 0) ?: null);
        } catch (\RuntimeException $e) {
            $this->back(null, [$e->getMessage()]);
        }
        AuditLog::record('site_post', $id, 'create');
        set_flash('success', $data['is_published'] ? 'Post published.' : 'Draft saved.');
        redirect('admin/public-site');
    }

    public function update(int $id): void
    {
        $this->guard('admin/public-site/posts/' . $id . '/edit');
        if (SitePost::find($id) === null) {
            set_flash('error', 'Post not found.');
            redirect('admin/public-site');
        }
        [$data, $errors] = $this->input();
        if ($errors !== []) {
            $this->back($id, $errors);
        }
        try {
            SitePost::update($id, $data, $this->uploadedImage(), !empty($_POST['remove_image']));
        } catch (\RuntimeException $e) {
            $this->back($id, [$e->getMessage()]);
        }
        AuditLog::record('site_post', $id, 'update');
        set_flash('success', 'Post saved.');
        redirect('admin/public-site');
    }

    public function destroy(int $id): void
    {
        $this->guard('admin/public-site');
        SitePost::delete($id);
        AuditLog::record('site_post', $id, 'delete');
        set_flash('success', 'Post deleted.');
        redirect('admin/public-site');
    }

    public function updateContent(): void
    {
        $this->guard('admin/public-site');
        $userId = (int) (Auth::user()['id'] ?? 0) ?: null;
        foreach (SiteContent::FIELDS as $field => [, , , $type]) {
            $value = trim((string) ($_POST[$field] ?? ''));
            if ($type === 'url') {
                $value = SiteContent::cleanUrl($value);
            } elseif ($type === 'email') {
                $value = filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : '';
            }
            SiteContent::put($field, mb_substr($value, 0, 2000), $userId);
        }
        set_flash('success', 'Site texts saved.');
        redirect('admin/public-site#site-texts');
    }

    private function renderForm(?array $post, array $values): void
    {
        $old = $_SESSION['_site_post_old'] ?? null;
        unset($_SESSION['_site_post_old']);
        view('admin.site.form', [
            'pageTitle' => ($post ? 'Edit' : 'New') . ' Post | RIDE Hub',
            'pageHeading' => $post ? 'Edit Post' : 'New Post',
            'pageSubtitle' => 'Posts appear on the public RIDE site once published.',
            'post' => $post,
            'values' => is_array($old) ? array_merge($values, $old) : $values,
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    private function input(): array
    {
        $kind = strtolower(trim((string) ($_POST['kind'] ?? '')));
        $series = strtolower(trim((string) ($_POST['series'] ?? '')));
        $text = static fn (string $key, int $max): ?string => ($v = mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max)) !== '' ? $v : null;
        $date = static function (string $key): ?string {
            $v = trim((string) ($_POST[$key] ?? ''));
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 ? $v : null;
        };
        $link = SiteContent::cleanUrl((string) ($_POST['link_url'] ?? ''));
        $data = [
            'kind' => $kind,
            'series' => $kind === 'conference' && isset(SitePost::SERIES[$series]) ? $series : null,
            'title' => $text('title', 255),
            'summary' => $text('summary', 600),
            'body' => $text('body', 20000),
            'link_url' => $link !== '' ? $link : null,
            'link_label' => $text('link_label', 80),
            'event_date' => $date('event_date'),
            'event_end_date' => $date('event_end_date'),
            'location' => $text('location', 255),
            // POST values arrive title-cased (normalize_request_post_text_fields), so compare lower-case.
            'is_published' => strtolower((string) ($_POST['status'] ?? '')) === 'published',
        ];
        $errors = [];
        if (!isset(SitePost::KINDS[$kind])) {
            $errors[] = 'Choose where the post goes.';
        }
        if ($kind === 'conference' && $data['series'] === null) {
            $errors[] = 'Choose the conference.';
        }
        if ($data['title'] === null) {
            $errors[] = 'Title is required.';
        }
        if (trim((string) ($_POST['link_url'] ?? '')) !== '' && $link === '') {
            $errors[] = 'The link must be a valid web address.';
        }

        return [$data, $errors];
    }

    private function uploadedImage(): ?array
    {
        $file = $_FILES['image'] ?? null;

        return is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }

    private function guard(string $backTo): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect($backTo);
        }
    }

    /** @param list<string> $errors */
    private function back(?int $id, array $errors): never
    {
        $_SESSION['_site_post_old'] = array_intersect_key($_POST, array_flip(['kind', 'series', 'title', 'summary', 'body', 'link_url', 'link_label', 'event_date', 'event_end_date', 'location']));
        set_flash('error', implode(' ', $errors));
        redirect($id !== null ? 'admin/public-site/posts/' . $id . '/edit' : 'admin/public-site/posts/new');
    }
}
