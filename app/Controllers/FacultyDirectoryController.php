<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\College;
use App\Models\User;
use App\Models\UserAvatar;
use App\Models\UserSpecialization;

/** Searchable, paginated list of faculty with their specializations and research profiles. */
final class FacultyDirectoryController
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        $faculty = User::allFaculty();
        $specializations = UserSpecialization::forUsers(array_map(static fn (array $row): int => (int) $row['id'], $faculty));

        $query = trim((string) ($_GET['q'] ?? ''));
        $selectedSpecialization = trim((string) ($_GET['specialization'] ?? ''));
        $selectedCollege = (int) ($_GET['college'] ?? 0);
        $needle = mb_strtolower($query);

        $matches = [];
        foreach ($faculty as $row) {
            $id = (int) $row['id'];
            $row['specializations'] = $specializations[$id] ?? [];
            if ($selectedCollege > 0 && (int) ($row['college_id'] ?? 0) !== $selectedCollege) {
                continue;
            }
            if ($selectedSpecialization !== '' && !in_array(
                mb_strtolower($selectedSpecialization),
                array_map('mb_strtolower', $row['specializations']),
                true
            )) {
                continue;
            }
            if ($needle !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $row['first_name'] ?? '',
                    $row['last_name'] ?? '',
                    $row['email'] ?? '',
                    $row['program'] ?? '',
                    $row['college_name'] ?? '',
                    implode(' ', $row['specializations']),
                ]));
                if (!str_contains($haystack, $needle)) {
                    continue;
                }
            }
            $matches[] = $row;
        }

        $total = count($matches);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $members = array_slice($matches, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        foreach ($members as $i => $member) {
            $members[$i]['avatar_url'] = UserAvatar::url((int) $member['id']);
        }

        view('faculty-directory.index', [
            'members' => $members,
            'matchCount' => $total,
            'totalFaculty' => count($faculty),
            'page' => $page,
            'pages' => $pages,
            'perPage' => self::PER_PAGE,
            'colleges' => College::all(),
            'popularSpecializations' => array_slice(UserSpecialization::allInUse(), 0, 24),
            'query' => $query,
            'selectedSpecialization' => $selectedSpecialization,
            'selectedCollege' => $selectedCollege,
        ]);
    }
}
