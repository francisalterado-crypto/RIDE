<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\HighlightSlide;
use App\Services\JournalIssueFeed;
use App\Models\SitePost;
use App\Support\SiteContent;

/** Public site: open to everyone. Never links into the internal portal. */
final class SiteController
{
    public function home(): void
    {
        $content = SiteContent::all();
        view_public('site.home', [
            'pageTitle' => 'RIDE | Western Philippines University',
            'content' => $content,
            'aims' => SiteContent::visionMission(),
            'highlights' => HighlightSlide::activeList(),
            'news' => SitePost::published('news', 7),
            'journalPosts' => SitePost::published('journal', 4),
            'issue' => JournalIssueFeed::current($content['journal_url']),
            'conferences' => $this->conferenceList($content, 3),
        ]);
    }

    public function news(): void
    {
        view_public('site.news', [
            'pageTitle' => 'News | RIDE',
            'posts' => SitePost::published('news', 200),
        ]);
    }

    public function journal(): void
    {
        $content = SiteContent::all();
        view_public('site.journal', [
            'pageTitle' => 'The Palawan Scientist | RIDE',
            'content' => $content,
            'issue' => JournalIssueFeed::current($content['journal_url']),
            'posts' => SitePost::published('journal', 200),
        ]);
    }

    public function conferences(): void
    {
        view_public('site.conferences', [
            'pageTitle' => 'Conferences | RIDE',
            'conferences' => $this->conferenceList(SiteContent::all(), 200),
        ]);
    }

    public function show(int|string $id): void
    {
        $post = is_int($id) ? SitePost::find($id) : null;
        if ($post === null || !$post['is_published']) {
            http_response_code(404);
            view_public('site.not-found', ['pageTitle' => 'Not Found | RIDE']);
            return;
        }
        view_public('site.article', [
            'pageTitle' => $post['title'] . ' | RIDE',
            'post' => $post,
            'related' => array_values(array_filter(
                SitePost::published($post['kind'], 4, $post['kind'] === 'conference' ? $post['series'] : null),
                static fn (array $p): bool => $p['id'] !== $post['id']
            )),
        ]);
    }

    /** @return array<string, array{name: string, text: string, url: string, posts: list<array<string, mixed>>}> */
    private function conferenceList(array $content, int $limit): array
    {
        return [
            'icwps' => [
                'name' => $content['icwps_name'],
                'text' => $content['icwps_description'],
                'details' => SiteContent::details($content['icwps_details']),
                'short' => 'ICWPS',
                'url' => $content['icwps_url'],
                'posts' => SitePost::published('conference', $limit, 'icwps'),
            ],
            'racp' => [
                'name' => 'RIDE Conference Proceedings',
                'text' => $content['racp_description'],
                'details' => SiteContent::details($content['racp_details']),
                'short' => 'RCP',
                'url' => $content['racp_url'],
                'posts' => SitePost::published('conference', $limit, 'racp'),
            ],
        ];
    }
}
