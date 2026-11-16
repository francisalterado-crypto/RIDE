<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\ExtensionActivity;
use App\Models\Proposal;
use App\Services\QuarterlyReportFile;
use App\Support\ExtensionActivityTypes;
use App\Support\AccessControl;
use App\Support\QuarterlyReporting;

/**
 * The Quarterly Report page of one report type (e.g. Trainings Conducted): pick a reporting period, see how many
 * entries it has and its deadline, generate the report (or open the one already generated for that period), and
 * list the reports generated so far. Entries themselves are recorded under Conducted Activities / Research
 * Accomplishments.
 */
final class QuarterlyReportController
{
    public function index(string $slug): void
    {
        $type = ExtensionActivityTypes::fromSlug($slug);
        if ($type === null) {
            http_response_code(404);
            view('errors.404');
            return;
        }
        $office = ExtensionActivityTypes::officeOf($type);
        $canPrepare = $office === 'rdo'
            ? AccessControl::canPrepareResearchQuarterlyReports()
            : AccessControl::canPrepareExtensionQuarterlyReports();
        if (!$canPrepare) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        $user = Auth::user() ?? [];
        $requested = trim((string) ($_GET['period'] ?? ''));
        $period = QuarterlyReporting::isPeriodKeyValid($requested) ? $requested : (QuarterlyReporting::currentPeriodKey() ?? '');
        // One report per college of the coordinator's clusters; the college is picked on the page.
        $collegeId = quarterly_report_college_id($user);
        $collegeOptions = [];
        $clusterColleges = AccessControl::rideCoordinatorCollegeIds();
        foreach (\App\Models\College::all() as $college) {
            if (in_array((int) $college['id'], $clusterColleges, true)) {
                $collegeOptions[(int) $college['id']] = (string) $college['name'];
            }
        }
        $scope = ExtensionActivityController::reportScope($user, $office, $collegeId);
        $entryCount = $period !== '' ? (int) ExtensionActivity::reportSummary($type, $period, $scope)['count'] : 0;

        // Reports of this type already generated for the college (or by the user, outside a college).
        $candidates = $collegeId > 0 ? Proposal::forCollege($collegeId) : Proposal::forUser((int) ($user['id'] ?? 0));
        $reports = [];
        foreach ($candidates as $row) {
            if (proposal_form_type($row) !== $type) {
                continue;
            }
            $summary = json_decode((string) ($row['summary'] ?? ''), true);
            $row['period_key'] = is_array($summary) ? QuarterlyReporting::periodFromSummary($summary) : '';
            $reports[] = $row;
        }
        usort($reports, static fn (array $a, array $b): int => strcmp((string) $b['period_key'], (string) $a['period_key']));
        $existing = null;
        foreach ($reports as $row) {
            if ($row['period_key'] === $period) {
                $existing = $row;
                break;
            }
        }

        $definition = ExtensionActivityTypes::get($type) ?? [];
        view('quarterly-reports.index', [
            'pageTitle' => $definition['label'] . ' | RIDE Hub',
            'type' => $type,
            'definition' => $definition,
            'office' => $office,
            'period' => $period,
            'periodOptions' => QuarterlyReporting::periodOptions(1, 1, $type),
            'collegeId' => $collegeId,
            'collegeOptions' => $collegeOptions,
            'entryCount' => $entryCount,
            'existing' => $existing,
            'reports' => $reports,
            'canPrint' => QuarterlyReportFile::available($type),
        ]);
    }
}
