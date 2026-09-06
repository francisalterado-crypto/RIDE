<?php

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require APP_PATH . '/config/config.php';
    }
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function is_vercel(): bool
{
    $value = $_ENV['VERCEL'] ?? $_SERVER['VERCEL'] ?? getenv('VERCEL');

    return $value === '1' || $value === 'true';
}

/** @param array{host?: string, user?: string, pass?: string} $database */
function database_configured(array $database): bool
{
    $host = (string) ($database['host'] ?? '');
    $user = (string) ($database['user'] ?? '');
    $pass = (string) ($database['pass'] ?? '');

    if (($host === '127.0.0.1' || $host === 'localhost') && $user === 'root' && $pass === '') {
        return false;
    }

    return $host !== '' && $user !== '';
}

function app_base_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    if (is_vercel()) {
        $proto = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https');
        if (str_contains($proto, ',')) {
            $proto = trim(explode(',', $proto)[0]);
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? getenv('VERCEL_URL') ?: '');
        if ($host !== '') {
            $cached = $proto . '://' . $host;

            return $cached;
        }
    }

    $cached = rtrim((string) config('app.url', ''), '/');

    return $cached;
}

function base_url(string $path = ''): string
{
    $base = app_base_url();
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function request_path(): string
{
    $requestPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
    $basePath = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');

    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $requestPath = trim(substr($requestPath, strlen($basePath)), '/');
    }

    return $requestPath;
}

function nav_active(string $path): bool
{
    $current = request_path();

    if ($path === 'dashboard') {
        return $current === '' || $current === 'dashboard';
    }

    return $current === $path || str_starts_with($current, $path . '/');
}

/** @return 'research'|'extension'|null Null when nav scope does not apply (non-faculty). */
function proposal_nav_scope(): ?string
{
    if (!\App\Core\Auth::hasRole('faculty')) {
        return null;
    }

    $scope = trim((string) ($_GET['scope'] ?? $_POST['nav_scope'] ?? ''));
    if ($scope === 'extension' || $scope === 'research') {
        return $scope;
    }

    $path = request_path();
    foreach ([
        'proposals/create/trainings-conducted',
        'proposals/create/technical-advisory',
        'proposals/create/extension-linkages',
        'proposals/create/outreach-activities',
        'proposals/create/technology-adoption',
        'proposals/create/accomplishment-report',
        'proposals/create/technical-advisory-ar',
        'proposals/create/outreach-activities-ar',
        'proposals/create/ebalwasyon-ng-gawain',
        'proposals/create/attendance-sheet',
    ] as $extensionPath) {
        if ($path === $extensionPath || str_starts_with($path, $extensionPath . '/')) {
            return 'extension';
        }
    }

    return 'research';
}

function proposal_nav_scope_query(string $menuScope): string
{
    if (!\App\Core\Auth::hasRole('faculty')) {
        return '';
    }

    return '?scope=' . rawurlencode($menuScope);
}

function proposal_nav_menu_scope_active(string $menuScope): bool
{
    if (!\App\Core\Auth::hasRole('faculty')) {
        return true;
    }

    return proposal_nav_scope() === $menuScope;
}

function proposal_nav_scoped_active(bool $pathActive, string $menuScope): bool
{
    return $pathActive && proposal_nav_menu_scope_active($menuScope);
}

function proposal_nav_redirect_suffix(?string $scope = null): string
{
    if (!\App\Core\Auth::hasRole('faculty')) {
        return '';
    }

    $scope = $scope ?? proposal_nav_scope() ?? 'research';

    return proposal_nav_scope_query($scope);
}

function proposal_nav_group_label(): string
{
    if (\App\Support\MonitoringRoles::isCoordinatorExtension()) {
        return 'Extension Requirements';
    }

    return 'Research Proposal';
}

function proposal_nav_submenu_label(string $label): string
{
    if (!\App\Support\MonitoringRoles::isCoordinatorResearch()) {
        return $label;
    }

    return match ($label) {
        'Progress Report' => 'Consolidated Progress Report',
        'Terminal Report' => 'Consolidated Terminal Report',
        'Terminal Report Assessment Form' => 'Terminal Report Assessment Registry',
        'OBR Matrix' => 'Consolidated OBR Matrix',
        'Consolidated Completed Researches' => 'Consolidated Completed Researches',
        'Consolidated Research Output Published' => 'Consolidated Research Output Published',
        default => $label,
    };
}

/** @return 'research'|'extension'|null Scope for VPRIDE monitoring nav; null when not scoped. */
function monitoring_nav_scope(): ?string
{
    if (!\App\Support\MonitoringRoles::isVpride()) {
        return null;
    }

    $scope = trim((string) ($_GET['scope'] ?? ''));

    return in_array($scope, ['research', 'extension'], true) ? $scope : null;
}

function monitoring_nav_active(string $scope): bool
{
    if (!nav_active('monitoring')) {
        return false;
    }

    if (!\App\Support\MonitoringRoles::isVpride()) {
        return true;
    }

    return monitoring_nav_scope() === $scope;
}

function user_initials(array $user): string
{
    $first = trim((string) ($user['first_name'] ?? ''));
    $last = trim((string) ($user['last_name'] ?? ''));

    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

function user_has_avatar(array $user): bool
{
    return user_avatar_url($user) !== null;
}

function user_avatar_url(array $user): ?string
{
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    return \App\Models\UserAvatar::url($userId);
}

function user_role_label(): string
{
    if (\App\Support\MonitoringRoles::isVpride()) {
        return 'VPRIDE Administrator';
    }
    if (\App\Core\Auth::hasRole('ride_admin')) {
        return 'RIDE Administrator';
    }
    if (\App\Support\MonitoringRoles::isDirectorResearch()) {
        return 'Director of Research';
    }
    if (\App\Support\MonitoringRoles::isDirectorExtension()) {
        return 'Director of Extension';
    }
    if (\App\Support\MonitoringRoles::isCoordinatorResearch()) {
        return 'Coordinator of Research';
    }
    if (\App\Support\MonitoringRoles::isCoordinatorExtension()) {
        return 'Coordinator of Extension';
    }
    if (\App\Core\Auth::hasRole('dean')) {
        return 'College Dean';
    }
    if (\App\Core\Auth::hasRole('faculty')) {
        return 'Faculty';
    }
    if (\App\Core\Auth::hasRole('ride_reporter')) {
        return 'Reporter';
    }
    if (\App\Core\Auth::hasRole('external_partner')) {
        return 'External Partner';
    }

    return 'User';
}

function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function title_case_words(string $value): string
{
    if ($value === '') {
        return $value;
    }

    return (string) preg_replace_callback(
        '/(^|[\s\'\-])([a-z])/u',
        static fn (array $matches): string => $matches[1] . strtoupper($matches[2]),
        $value
    );
}

function should_title_case_post_field(string $key): bool
{
    $key = strtolower($key);

    if ($key === '_csrf') {
        return false;
    }

    if (str_contains($key, 'password')) {
        return false;
    }

    if ($key === 'email' || str_ends_with($key, '_email')) {
        return false;
    }

    if ($key === 'url' || str_contains($key, '_url') || str_contains($key, '_link')) {
        return false;
    }

    return true;
}

/** @param array<string|int, mixed> $data */
function normalize_request_post_text_fields(array &$data): void
{
    foreach ($data as $key => &$value) {
        if (!is_string($key)) {
            continue;
        }

        if (is_array($value)) {
            normalize_request_post_text_fields($value);
            continue;
        }

        if (!is_string($value) || !should_title_case_post_field($key)) {
            continue;
        }

        $value = title_case_words($value);
    }
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $contentView = APP_PATH . '/views/' . str_replace('.', '/', $name) . '.php';
    if (!is_file($contentView)) {
        http_response_code(500);
        echo 'View not found: ' . htmlspecialchars($name);
        return;
    }
    $pageTitle = $pageTitle ?? config('app.name');
    ob_start();
    require $contentView;
    $pageContent = ob_get_clean();
    $pageTitle = $pageTitle ?? config('app.name');
    require APP_PATH . '/views/layouts/main.php';
}

function old(string $key, string $default = ''): string
{
    return htmlspecialchars((string) ($_SESSION['_old'][$key] ?? $default));
}

function flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_csrf'] ?? '';
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function signature_url(int $userId): ?string
{
    if (\App\Models\UserSignature::filePath($userId) === null) {
        return null;
    }

    return base_url('signatures/' . $userId);
}

function proposal_applicant_signature_date(?array $proposal): string
{
    $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
    $submittedAt = is_array($proposal) ? ($proposal['submitted_at'] ?? null) : null;
    $status = is_array($proposal) ? (string) ($proposal['status'] ?? 'draft') : 'draft';

    if (is_string($submittedAt) && $submittedAt !== '' && !in_array($status, ['draft', 'returned'], true)) {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $submittedAt, $timezone)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d', $submittedAt, $timezone);

        if ($date instanceof \DateTimeImmutable) {
            return $date->format('F j, Y');
        }
    }

    return (new \DateTimeImmutable('now', $timezone))->format('F j, Y');
}

function proposal_is_wpu_funded_extension(?array $proposal): bool
{
    if (!is_array($proposal) || ($proposal['project_type'] ?? '') !== 'extension') {
        return false;
    }

    $formType = proposal_form_type($proposal);
    if ($formType !== '' && !in_array($formType, ['wpu_funded_extension', 'extension_program_proposal'], true)) {
        return false;
    }

    return !proposal_is_extension_linkages($proposal)
        && !proposal_is_trainings_conducted($proposal)
        && !proposal_is_technical_advisory($proposal)
        && !proposal_is_outreach_activities($proposal)
        && !proposal_is_technology_adoption($proposal)
        && !proposal_is_accomplishment_report($proposal)
        && !proposal_is_technical_advisory_ar($proposal)
        && !proposal_is_outreach_activities_ar($proposal)
        && !proposal_is_ebalwasyon_ng_gawain($proposal)
        && !proposal_is_attendance_sheet($proposal)
        && !proposal_is_resulted_in_extension($proposal);
}

function proposal_is_manuscript(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'manuscript';
}

function proposal_is_completed_researches(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'completed_researches';
}

function proposal_is_ongoing_researches(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'ongoing_researches';
}

function proposal_is_research_output_published(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'research_output_published';
}

function proposal_is_research_output_presented(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'research_output_presented';
}

function proposal_is_commercialized(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'commercialized';
}

function proposal_is_resulted_in_extension(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'resulted_in_extension';
}

function proposal_is_journal_citation(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'journal_citation';
}

function proposal_is_book_citation(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'book_citation';
}

function proposal_is_inventions_um_copyrights(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'inventions_um_copyrights';
}

function proposal_is_linkages(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'linkages';
}

function proposal_is_consolidated_completed_researches(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_completed_researches';
}

function proposal_is_consolidated_ongoing_researches(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_ongoing_researches';
}

/** @return 'college'|'vpride' */
function proposal_consolidation_scope(?array $proposal): string
{
    if (!is_array($proposal) || empty($proposal['summary'])) {
        return 'college';
    }

    $decoded = json_decode((string) $proposal['summary'], true);
    if (!is_array($decoded)) {
        return 'college';
    }

    $scope = $decoded['consolidation_scope'] ?? 'college';

    return $scope === 'vpride' ? 'vpride' : 'college';
}

function proposal_is_vpride_consolidated_ongoing_researches(?array $proposal): bool
{
    return proposal_is_consolidated_ongoing_researches($proposal)
        && proposal_consolidation_scope($proposal) === 'vpride';
}

function proposal_is_consolidated_research_output_published(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_research_output_published';
}

function proposal_is_consolidated_research_output_presented(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_research_output_presented';
}

function proposal_is_consolidated_commercialized(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_commercialized';
}

function proposal_is_consolidated_resulted_in_extension(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_resulted_in_extension';
}

function proposal_is_consolidated_journal_citation(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_journal_citation';
}

function proposal_is_consolidated_book_citation(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_book_citation';
}

function proposal_is_consolidated_inventions_um_copyrights(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_inventions_um_copyrights';
}

function proposal_is_consolidated_linkages(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'consolidated_linkages';
}

function proposal_is_progress_report(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'progress_report';
}

function proposal_is_terminal_report(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'terminal_report';
}

function proposal_is_terminal_report_assessment_form(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'terminal_report_assessment_form';
}

function proposal_is_obr_matrix(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'obr_matrix';
}

function proposal_is_trainings_conducted(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'trainings_conducted';
}

function proposal_is_technical_advisory(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'technical_advisory';
}

function proposal_is_extension_linkages(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'extension_linkages';
}

function proposal_is_outreach_activities(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'outreach_activities';
}

function proposal_is_technology_adoption(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'technology_adoption';
}

function proposal_is_accomplishment_report(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'accomplishment_report';
}

function proposal_is_technical_advisory_ar(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'technical_advisory_ar';
}

function proposal_is_outreach_activities_ar(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'outreach_activities_ar';
}

function proposal_is_ebalwasyon_ng_gawain(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'ebalwasyon_ng_gawain';
}

function proposal_is_attendance_sheet(?array $proposal): bool
{
    return proposal_form_type($proposal) === 'attendance_sheet';
}

/** @return array<string, string> */
function attendance_sheet_sex_options(): array
{
    return [
        'female' => 'Female',
        'male' => 'Male',
    ];
}

/** @return array<int, string> */
function ebalwasyon_ng_gawain_scale(): array
{
    return [
        5 => 'Lubhang katangi-tangi',
        4 => 'Katangi-tangi',
        3 => 'Kasiya-siya',
        2 => 'Kainam',
        1 => 'Mahina',
    ];
}

/**
 * @return list<array{heading: string, items: array<string, string>}>
 */
function ebalwasyon_ng_gawain_sections(): array
{
    return [
        [
            'heading' => 'Layunin at nilalaman ng paksa/ gawain',
            'items' => [
                '1_1' => '1.1 Ang layunin ng paksa/gawain ay malinaw, naunawaan at nakamit',
                '1_2' => '1.2. Ang lawak ng paksa/gawain ay tumugon sa pangangailangan ng komunidad.',
                '1_3' => '1.3 Ang natutuhang kaalaman ay kapakipakinabang sa pang araw-araw na buhay',
            ],
        ],
        [
            'heading' => 'Proseso ng Pagtuturo/Pagsasanay/Gawain',
            'items' => [
                '2_1' => '2.1 Nagsasagawa ng konsultasyon sa mga benepisyaryo sa pagpaplano ng paksa/gawain.',
                '2_2' => '2.2 Ang mga benepisyaryo ay naging aktibo sa pakikilahok sa pag-aaral at pagsasanay.',
                '2_3' => '2.3 Madaling natutuhan ang paksa/gawain.',
            ],
        ],
        [
            'heading' => 'Tagapagturo, Tagapagsanay at mga Namamalakad',
            'items' => [
                '3_1' => '3.1 Masigla at kaayaaya ang paraan ng pagtuturo/pagsasanay.',
                '3_2' => '3.2 Malawak ang kaalaman ng mga tagapgturo/ tagpagsanay.',
                '3_3' => '3.3 Magalang at maayos makipag-usap ang mga namamalakad ng gawin/pagsasanay.',
            ],
        ],
        [
            'heading' => 'Pagsunod sa oras at Iskedyul',
            'items' => [
                '4_1' => '4.1 Nagsimula ang gawain/pagsasanay sa tamang oras.',
                '4_2' => '4.2 Naibigay ang programa ng gawain alinsunod sa Iskedyul.',
                '4_3' => '4.3 Natapos ang gawain/pagsasanay sa tamang oras.',
            ],
        ],
        [
            'heading' => 'Lugar ng Gawain at Kagamitan.',
            'items' => [
                '5_1' => '5.1 Ang lugar ay maayos at angkop sa gawain/pagsasanay.',
                '5_2' => '5.2 Ang kagamitan ay sapat para sa gawain/pagsasanay.',
                '5_3' => '5.3 Ang kagamitan ay tugma para sa gawain/pagsasanay.',
            ],
        ],
    ];
}

/** @return list<string> */
function ebalwasyon_ng_gawain_item_keys(): array
{
    $keys = [];
    foreach (ebalwasyon_ng_gawain_sections() as $section) {
        foreach (array_keys($section['items']) as $key) {
            $keys[] = $key;
        }
    }

    return $keys;
}

/** @param array<string, mixed> $ratings */
function ebalwasyon_ng_gawain_average(array $ratings): ?float
{
    $values = [];
    foreach (ebalwasyon_ng_gawain_item_keys() as $key) {
        $value = $ratings[$key] ?? null;
        if (is_int($value) || (is_string($value) && preg_match('/^[1-5]$/', $value) === 1)) {
            $values[] = (int) $value;
        }
    }

    if ($values === []) {
        return null;
    }

    return round(array_sum($values) / count($values), 2);
}

function ebalwasyon_ng_gawain_legend(?float $average): string
{
    if ($average === null) {
        return '';
    }

    if ($average >= 4.5) {
        return 'Best / Lubhang Katangi-tangi';
    }
    if ($average >= 3.5) {
        return 'Better / Katangi-tangi';
    }
    if ($average >= 2.5) {
        return 'Good / Kasiya-siya';
    }
    if ($average >= 1.5) {
        return 'Fair / Kainaman';
    }

    return 'Poor / Mahina';
}

/** @return array<string, float> */
function trainings_conducted_length_weights(): array
{
    return [
        'lt_8h' => 0.5,
        '8h_1d' => 1.0,
        '2d' => 1.25,
        '3_4d' => 1.5,
        '5d_plus' => 2.0,
    ];
}

function trainings_conducted_weight(string $trainingLength): float
{
    return trainings_conducted_length_weights()[$trainingLength] ?? 0.0;
}

function trainings_conducted_weighted_persons(int $personsTrained, string $trainingLength): float
{
    if ($personsTrained <= 0 || $trainingLength === '') {
        return 0.0;
    }

    return round($personsTrained * trainings_conducted_weight($trainingLength), 2);
}

function proposal_is_quarterly_researches_report(?array $proposal): bool
{
    return proposal_is_completed_researches($proposal)
        || proposal_is_ongoing_researches($proposal)
        || proposal_is_research_output_published($proposal)
        || proposal_is_research_output_presented($proposal)
        || proposal_is_commercialized($proposal)
        || proposal_is_resulted_in_extension($proposal)
        || proposal_is_journal_citation($proposal)
        || proposal_is_book_citation($proposal)
        || proposal_is_inventions_um_copyrights($proposal)
        || proposal_is_linkages($proposal)
        || proposal_is_consolidated_completed_researches($proposal)
        || proposal_is_consolidated_ongoing_researches($proposal)
        || proposal_is_consolidated_research_output_published($proposal)
        || proposal_is_consolidated_research_output_presented($proposal)
        || proposal_is_consolidated_commercialized($proposal)
        || proposal_is_consolidated_resulted_in_extension($proposal)
        || proposal_is_consolidated_journal_citation($proposal)
        || proposal_is_consolidated_book_citation($proposal)
        || proposal_is_consolidated_inventions_um_copyrights($proposal)
        || proposal_is_consolidated_linkages($proposal)
        || proposal_is_progress_report($proposal)
        || proposal_is_terminal_report($proposal)
        || proposal_is_terminal_report_assessment_form($proposal)
        || proposal_is_obr_matrix($proposal)
        || proposal_is_trainings_conducted($proposal)
        || proposal_is_technical_advisory($proposal)
        || proposal_is_extension_linkages($proposal)
        || proposal_is_outreach_activities($proposal)
        || proposal_is_technology_adoption($proposal);
}

/** Faculty-submitted project reports that follow the full research approval chain (coordinator → dean → director → VPRIDE). */
function proposal_uses_faculty_submitted_monitoring_workflow(?array $proposal): bool
{
    return proposal_is_progress_report($proposal)
        || proposal_is_terminal_report($proposal)
        || proposal_is_terminal_report_assessment_form($proposal)
        || proposal_is_obr_matrix($proposal);
}

function proposal_form_type(?array $proposal): string
{
    if (!is_array($proposal) || empty($proposal['summary'])) {
        return '';
    }

    $decoded = json_decode((string) $proposal['summary'], true);
    if (!is_array($decoded)) {
        return '';
    }

    $formType = $decoded['form_type'] ?? '';

    return is_string($formType) ? $formType : '';
}

/** Applicant's Information / standard research proposal (no separate required-file uploads). */
function proposal_is_research_application(?array $proposal): bool
{
    if (!is_array($proposal) || proposal_form_type($proposal) !== '') {
        return false;
    }

    if (empty($proposal['summary'])) {
        return false;
    }

    $decoded = json_decode((string) $proposal['summary'], true);
    if (!is_array($decoded)) {
        return false;
    }

    return array_key_exists('applicant_last_name', $decoded)
        || array_key_exists('abstract', $decoded);
}

/** @return array{signature_url: ?string, date: string, name: string, position: string, mobile: string, email: string}|null */
function proposal_step_endorsement(int $proposalId, string $step, string $position): ?array
{
    $approval = \App\Models\Proposal::approvalAtStep($proposalId, $step);
    if ($approval === null) {
        return null;
    }

    $userId = (int) ($approval['user_id'] ?? 0);
    $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
    $date = '';
    $createdAt = $approval['created_at'] ?? null;
    if (is_string($createdAt) && $createdAt !== '') {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $createdAt, $timezone)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d', $createdAt, $timezone);
        if ($parsed instanceof \DateTimeImmutable) {
            $date = $parsed->format('F j, Y');
        }
    }

    return [
        'signature_url' => signature_url($userId),
        'date' => $date,
        'name' => trim((string) ($approval['first_name'] ?? '') . ' ' . (string) ($approval['last_name'] ?? '')),
        'position' => $position,
        'mobile' => '',
        'email' => (string) ($approval['email'] ?? ''),
    ];
}

/** @return array{signature_url: ?string, date: string, name: string, position: string, mobile: string, email: string}|null */
function proposal_coordinator_endorsement(int $proposalId, string $projectType = 'research'): ?array
{
    $step = \App\Support\MonitoringRoles::coordinatorStepForType($projectType);
    $label = $projectType === 'extension' ? 'Coordinator of Extension' : 'Coordinator of Research';

    return proposal_step_endorsement($proposalId, $step, $label);
}

/** @return array{signature_url: ?string, date: string, name: string, role: string} */
function proposal_manuscript_college_signatory(
    int $collegeId,
    string $collegeName,
    string $roleSlug,
    string $positionLabel,
    ?array $endorsement
): array {
    $name = is_array($endorsement) ? trim((string) ($endorsement['name'] ?? '')) : '';
    $signatureUrl = is_array($endorsement) ? ($endorsement['signature_url'] ?? null) : null;
    $date = is_array($endorsement) ? (string) ($endorsement['date'] ?? '') : '';

    if ($collegeId > 0 && ($name === '' || $signatureUrl === null)) {
        $staff = \App\Models\User::findActiveByRoleAndCollege($roleSlug, $collegeId);
        if ($staff !== null) {
            if ($name === '') {
                $name = trim((string) ($staff['first_name'] ?? '') . ' ' . (string) ($staff['last_name'] ?? ''));
            }
            if ($signatureUrl === null) {
                $signatureUrl = signature_url((int) $staff['id']);
            }
        }
    }

    $role = $positionLabel;
    if ($collegeName !== '') {
        $role .= ', ' . $collegeName;
    }

    return [
        'signature_url' => $signatureUrl,
        'date' => $date,
        'name' => $name,
        'role' => $role,
    ];
}

/** @return array{signature_url: ?string, date: string, name: string, role: string} */
function proposal_manuscript_role_signatory(
    string $roleSlug,
    string $roleLabel,
    ?array $endorsement,
    string $defaultName = ''
): array {
    $name = is_array($endorsement) ? trim((string) ($endorsement['name'] ?? '')) : '';
    $signatureUrl = is_array($endorsement) ? ($endorsement['signature_url'] ?? null) : null;
    $date = is_array($endorsement) ? (string) ($endorsement['date'] ?? '') : '';

    if ($name === '' && $defaultName !== '') {
        $name = $defaultName;
    }

    if ($signatureUrl === null) {
        $staff = \App\Models\User::findActiveByRole($roleSlug);
        if ($staff !== null) {
            if ($name === '') {
                $name = trim((string) ($staff['first_name'] ?? '') . ' ' . (string) ($staff['last_name'] ?? ''));
            }
            $signatureUrl = signature_url((int) $staff['id']);
        }
    }

    return [
        'signature_url' => $signatureUrl,
        'date' => $date,
        'name' => $name,
        'role' => $roleLabel,
    ];
}

function proposal_format_workflow_date(?string $value): string
{
    if (!is_string($value) || $value === '') {
        return '';
    }

    $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
    $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $timezone)
        ?: \DateTimeImmutable::createFromFormat('Y-m-d', $value, $timezone);

    return $parsed instanceof \DateTimeImmutable ? $parsed->format('M j, Y') : '';
}

/**
 * @return list<array{
 *     key: string,
 *     title: string,
 *     description: string,
 *     status: string,
 *     status_label: string,
 *     actor_name: string,
 *     acted_at: string
 * }>
 */
function proposal_workflow_steps(?array $proposal): array
{
    $projectType = is_array($proposal) ? (string) ($proposal['project_type'] ?? 'research') : 'research';
    $isExtension = $projectType === 'extension';
    $isManuscript = proposal_is_manuscript($proposal);
    $isProgressReport = proposal_is_progress_report($proposal);
    $isTerminalReport = proposal_is_terminal_report($proposal);
    $isTerminalReportAssessment = proposal_is_terminal_report_assessment_form($proposal);
    $isObrMatrix = proposal_is_obr_matrix($proposal);
    $isCoordinatorSubmittedReport = proposal_is_quarterly_researches_report($proposal)
        && !proposal_uses_faculty_submitted_monitoring_workflow($proposal);
    $reviewSteps = $isCoordinatorSubmittedReport
        ? [
            \App\Support\MonitoringRoles::DEAN,
            \App\Support\MonitoringRoles::directorStepForType($projectType),
            \App\Support\MonitoringRoles::VPRIDE,
        ]
        : [
            \App\Support\MonitoringRoles::coordinatorStepForType($projectType),
            \App\Support\MonitoringRoles::DEAN,
            \App\Support\MonitoringRoles::directorStepForType($projectType),
            \App\Support\MonitoringRoles::VPRIDE,
        ];

    $status = is_array($proposal) ? (string) ($proposal['status'] ?? 'draft') : 'draft';
    $currentStep = is_array($proposal) ? ($proposal['current_step'] ?? null) : null;
    $proposalId = is_array($proposal) ? (int) ($proposal['id'] ?? 0) : 0;
    $isFinalized = in_array($status, ['ongoing', 'approved', 'completed'], true);
    $currentIdx = is_string($currentStep) ? array_search($currentStep, $reviewSteps, true) : false;

    $applicantStatus = match ($status) {
        'draft' => 'pending',
        'returned' => 'returned',
        'suspended' => 'suspended',
        default => 'completed',
    };

    $applicantDate = proposal_format_workflow_date(is_array($proposal) ? ($proposal['submitted_at'] ?? null) : null);
    $applicantName = is_array($proposal)
        ? trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''))
        : '';

    $steps = [[
        'key' => 'applicant',
        'title' => $isCoordinatorSubmittedReport
            ? 'College R&D Coordinator'
            : ($isManuscript
            ? 'Lead Researcher'
            : ($isExtension ? 'Faculty Extension Worker' : 'Faculty Researcher')),
        'description' => match (true) {
            $isProgressReport => 'Submit progress report',
            $isTerminalReport => 'Submit terminal report',
            $isTerminalReportAssessment => 'Submit terminal report assessment form',
            $isObrMatrix => 'Submit OBR matrix',
            $isCoordinatorSubmittedReport => 'Submit quarterly researches report',
            $isManuscript => 'Submit manuscript writing proposal',
            $isExtension => 'Submit extension proposal',
            default => 'Submit research proposal',
        },
        'status' => $applicantStatus,
        'status_label' => match ($applicantStatus) {
            'pending' => 'Draft — not yet submitted',
            'returned' => 'Returned for revision',
            'suspended' => 'Suspended',
            default => 'Submitted',
        },
        'actor_name' => $applicantName,
        'acted_at' => $applicantDate,
    ]];

    $coordinatorStep = \App\Support\MonitoringRoles::coordinatorStepForType($projectType);
    $reviewDescriptions = [
        $coordinatorStep => 'Endorses and forwards to College Dean',
        \App\Support\MonitoringRoles::DEAN => $isExtension
            ? 'Approves and forwards to Director of Extension'
            : 'Approves and forwards to Director of Research',
        \App\Support\MonitoringRoles::DIRECTOR_RESEARCH => 'Approves and forwards to VPRIDE',
        \App\Support\MonitoringRoles::DIRECTOR_EXTENSION => 'Approves and forwards to VPRIDE',
        \App\Support\MonitoringRoles::VPRIDE => 'Grants final approval',
    ];

    foreach ($reviewSteps as $index => $stepKey) {
        $approval = $proposalId > 0 ? \App\Models\Proposal::approvalAtStep($proposalId, $stepKey) : null;

        if ($isFinalized) {
            $stepStatus = 'completed';
        } elseif ($status === 'draft') {
            $stepStatus = 'upcoming';
        } elseif ($status === 'returned') {
            $stepStatus = $approval !== null ? 'completed' : 'upcoming';
        } elseif ($status === 'suspended') {
            $stepStatus = $approval !== null ? 'completed' : 'upcoming';
        } elseif ($currentStep === $stepKey) {
            $stepStatus = 'pending';
        } elseif ($approval !== null) {
            $stepStatus = 'completed';
        } elseif ($currentIdx !== false && $index < $currentIdx) {
            $stepStatus = 'completed';
        } else {
            $stepStatus = 'upcoming';
        }

        if ($stepStatus === 'completed' && $stepKey === \App\Support\MonitoringRoles::VPRIDE && $approval === null) {
            $approvalDate = proposal_format_workflow_date(is_array($proposal) ? ($proposal['approved_at'] ?? null) : null);
        } else {
            $approvalDate = proposal_format_workflow_date(is_array($approval) ? ($approval['created_at'] ?? null) : null);
        }

        $steps[] = [
            'key' => $stepKey,
            'title' => \App\Support\MonitoringRoles::stepLabel($stepKey),
            'description' => $reviewDescriptions[$stepKey] ?? '',
            'status' => $stepStatus,
            'status_label' => match ($stepStatus) {
                'pending' => 'Pending review',
                'completed' => 'Completed',
                default => 'Waiting',
            },
            'actor_name' => is_array($approval)
                ? trim((string) ($approval['first_name'] ?? '') . ' ' . (string) ($approval['last_name'] ?? ''))
                : '',
            'acted_at' => $approvalDate,
        ];
    }

    return $steps;
}

function proposal_can_submit_for_review(?array $proposal): bool
{
    if (!is_array($proposal)) {
        return false;
    }

    return !\App\Support\ProposalCoAuthors::hasPendingLinkedCoAuthors(
        (int) ($proposal['id'] ?? 0),
        (string) ($proposal['summary'] ?? '')
    );
}

function proposal_submit_blocked_coauthor_message(?array $proposal): ?string
{
    if (!is_array($proposal) || proposal_can_submit_for_review($proposal)) {
        return null;
    }

    return 'Submit for review is unavailable until all registered faculty co-authors accept their invitation.';
}

/** @return list<string> */
function account_program_options(?string $currentValue = null): array
{
    static $options = null;
    if ($options === null) {
        $options = require APP_PATH . '/config/programs.php';
    }

    $currentValue = trim((string) $currentValue);
    if ($currentValue !== '' && !in_array($currentValue, $options, true)) {
        return array_merge([$currentValue], $options);
    }

    return $options;
}

/**
 * @param array<string, mixed> $post
 * @param array<string, mixed> $existingSummary
 * @return array{reporting_period: string, report_as_of: string}
 */
function quarterly_reporting_require(array $post, array $existingSummary = [], ?string $formType = null): array
{
    \App\Support\QuarterlyReporting::assertSubmissionOpen();

    if ($formType === null || $formType === '') {
        $formType = trim((string) ($existingSummary['form_type'] ?? ''));
    }

    if (\App\Support\FacultyFormDeadlines::locksFacultyReportingPeriod($formType !== '' ? $formType : null)) {
        $parsed = \App\Support\QuarterlyReporting::facultyLockedPeriod(
            $existingSummary,
            $formType !== '' ? $formType : null
        );
    } else {
        $parsed = \App\Support\QuarterlyReporting::parseRequest($post);
    }

    if ($parsed === null) {
        $deadlineSummary = \App\Support\FacultyFormDeadlines::deadlineSummaryText();
        set_flash('error', 'Select a valid reporting period (deadlines: ' . $deadlineSummary . ').');
        redirect(request_path() !== '' ? request_path() : 'dashboard');
    }

    return $parsed;
}

/**
 * @param array<string, mixed> $summary
 * @return array<string, mixed>
 */
function quarterly_reporting_apply_summary(array $summary, array $post): array
{
    $reporting = quarterly_reporting_require($post);
    $summary['reporting_period'] = $reporting['reporting_period'];
    $summary['report_as_of'] = $reporting['report_as_of'];

    return $summary;
}

/**
 * @param array<string, mixed> $summary
 */
function quarterly_reporting_period_from_summary(array $summary): string
{
    return \App\Support\QuarterlyReporting::periodFromSummary($summary);
}

/**
 * @param array<string, mixed> $summary
 */
function quarterly_reporting_period_label(array $summary): string
{
    return \App\Support\QuarterlyReporting::periodLabelFromSummary($summary);
}

/**
 * @param array<string, mixed> $summary
 */
function quarterly_reporting_is_overdue(array $summary): bool
{
    $period = quarterly_reporting_period_from_summary($summary);
    if ($period === '') {
        return false;
    }

    $formType = trim((string) ($summary['form_type'] ?? ''));

    return \App\Support\QuarterlyReporting::isOverdue(
        $period,
        null,
        $formType !== '' ? $formType : null
    );
}
