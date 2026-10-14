<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\FacultyFormDeadlines;

final class FacultyDeadlineSettingsController
{
    public function index(): void
    {
        FacultyFormDeadlines::requireManager();

        $config = FacultyFormDeadlines::config();

        view('settings.faculty-deadlines', [
            'config' => $config,
            'deadlineMonths' => $config['months'],
            'activatedGroups' => $config['activated_groups'],
            'deadlineEnabled' => $config['deadline_enabled'],
            'submissionOpenDate' => $config['open_date'],
            'monthOptions' => FacultyFormDeadlines::monthOptions(),
            'formGroupOptions' => FacultyFormDeadlines::FORM_GROUP_LABELS,
            'deadlineSummary' => FacultyFormDeadlines::deadlineSummaryText(),
            'pageTitle' => 'Faculty Form Deadlines — RIDE IMS',
            'pageHeading' => 'Faculty Form Deadlines',
            'pageSubtitle' => 'Activate quarterly deadlines for faculty monitoring forms (VPRIDE and RDO Director).',
        ]);
    }

    public function update(): void
    {
        FacultyFormDeadlines::requireManager();

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('settings/faculty-deadlines');
        }

        $openDate = trim((string) ($_POST['submission_open_date'] ?? ''));

        $postedGroups = $_POST['activated_form_groups'] ?? [];
        if (!is_array($postedGroups)) {
            $postedGroups = [];
        }
        $activatedGroups = [];
        foreach ($postedGroups as $group) {
            $key = (string) $group;
            if (array_key_exists($key, FacultyFormDeadlines::FORM_GROUP_LABELS)) {
                $activatedGroups[] = $key;
            }
        }

        $postedMonths = $_POST['deadline_months'] ?? [];
        if (!is_array($postedMonths)) {
            $postedMonths = [];
        }
        $closingMonths = [];
        foreach ($postedMonths as $month) {
            $closingMonths[] = (int) $month;
        }

        $previous = FacultyFormDeadlines::config();
        $storedMonths = $closingMonths !== [] ? $closingMonths : $previous['months'];
        $deadlineEnabled = $activatedGroups !== [] && $closingMonths !== [];

        FacultyFormDeadlines::saveConfig([
            'deadline_enabled' => $deadlineEnabled,
            'months' => $storedMonths,
            'open_date' => $openDate,
            'activated_groups' => $activatedGroups,
        ], FacultyFormDeadlines::currentManagerId());

        set_flash('success', 'Faculty form deadline settings saved.');
        redirect('settings/faculty-deadlines');
    }
}
