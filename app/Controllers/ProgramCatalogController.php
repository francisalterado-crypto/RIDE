<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\ExtensionProgram;
use App\Models\ExtensionProject;

/** Admin catalog of academic programs, extension programs and extension projects (with planned activities). */
final class ProgramCatalogController
{
    public const TABS = ['extension' => 'Extension Programs', 'projects' => 'Extension Projects', 'academic' => 'Academic Programs'];

    public function index(): void
    {
        $tab = (string) ($_GET['tab'] ?? 'extension');
        $tab = array_key_exists($tab, self::TABS) ? $tab : 'extension';
        $collegeId = (int) ($_GET['college'] ?? 0) ?: null;
        $programId = (int) ($_GET['program'] ?? 0) ?: null;
        $year = (int) ($_GET['year'] ?? 0) ?: null;

        view('admin.programs.index', [
            'tab' => $tab,
            'colleges' => College::all(),
            'collegeId' => $collegeId,
            'programId' => $programId,
            'year' => $year,
            'academicPrograms' => AcademicProgram::all($collegeId),
            'extensionPrograms' => ExtensionProgram::all($collegeId),
            'allExtensionPrograms' => ExtensionProgram::all(),
            'projects' => $tab === 'projects'
                ? ExtensionProject::all(['college_id' => $collegeId, 'program_id' => $programId, 'year' => $year])
                : [],
            'allAcademicPrograms' => AcademicProgram::all(),
        ]);
    }

    // ---- Academic programs ----

    public function storeAcademic(): void
    {
        $this->guard('admin/programs?tab=academic');
        [$collegeId, $name] = [$this->collegeIdFromPost(), trim((string) ($_POST['name'] ?? ''))];
        if ($name === '' || strlen($name) > 150) {
            $this->fail('Program name is required (150 characters or less).', 'admin/programs?tab=academic');
        }
        if (AcademicProgram::nameExists($name)) {
            $this->fail('That academic program already exists.', 'admin/programs?tab=academic');
        }
        $id = AcademicProgram::create($collegeId, $name);
        AuditLog::record('academic_program', $id, 'create', ['name' => $name]);
        set_flash('success', 'Academic program added.');
        redirect('admin/programs?tab=academic');
    }

    public function editAcademic(int $id): void
    {
        $program = AcademicProgram::find($id) ?? $this->notFound('admin/programs?tab=academic');
        view('admin.programs.academic-edit', ['program' => $program, 'colleges' => College::all()]);
    }

    public function updateAcademic(int $id): void
    {
        $back = 'admin/programs/academic/' . $id . '/edit';
        $this->guard($back);
        AcademicProgram::find($id) ?? $this->notFound('admin/programs?tab=academic');
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || strlen($name) > 150) {
            $this->fail('Program name is required (150 characters or less).', $back);
        }
        if (AcademicProgram::nameExists($name, $id)) {
            $this->fail('That academic program already exists.', $back);
        }
        AcademicProgram::update($id, $this->collegeIdFromPost(), $name, !empty($_POST['is_active']));
        AuditLog::record('academic_program', $id, 'update', ['name' => $name]);
        set_flash('success', 'Academic program updated.');
        redirect('admin/programs?tab=academic');
    }

    public function destroyAcademic(int $id): void
    {
        $this->guard('admin/programs?tab=academic');
        if (!AcademicProgram::delete($id)) {
            $this->fail('This program is assigned to accounts, proposals or projects. Mark it inactive instead.', 'admin/programs?tab=academic');
        }
        AuditLog::record('academic_program', $id, 'delete');
        set_flash('success', 'Academic program deleted.');
        redirect('admin/programs?tab=academic');
    }

    // ---- Extension programs ----

    public function storeExtension(): void
    {
        $this->guard('admin/programs?tab=extension');
        $data = $this->extensionProgramInput();
        $this->validateExtensionProgram($data, null, 'admin/programs?tab=extension');
        $id = ExtensionProgram::create($data);
        AuditLog::record('extension_program', $id, 'create', ['name' => $data['name']]);
        set_flash('success', 'Extension program added.');
        redirect('admin/programs?tab=extension');
    }

    public function editExtension(int $id): void
    {
        $program = ExtensionProgram::find($id) ?? $this->notFound('admin/programs?tab=extension');
        view('admin.programs.extension-edit', [
            'program' => $program,
            'colleges' => College::all(),
            'projects' => ExtensionProject::all(['program_id' => $id]),
        ]);
    }

    public function updateExtension(int $id): void
    {
        $back = 'admin/programs/extension/' . $id . '/edit';
        $this->guard($back);
        ExtensionProgram::find($id) ?? $this->notFound('admin/programs?tab=extension');
        $data = $this->extensionProgramInput();
        $this->validateExtensionProgram($data, $id, $back);
        ExtensionProgram::update($id, $data);
        AuditLog::record('extension_program', $id, 'update', ['name' => $data['name']]);
        set_flash('success', 'Extension program updated.');
        redirect($back);
    }

    public function destroyExtension(int $id): void
    {
        $this->guard('admin/programs?tab=extension');
        $reason = ExtensionProgram::deleteBlockingReason($id);
        if ($reason !== null) {
            $this->fail($reason, 'admin/programs/extension/' . $id . '/edit');
        }
        ExtensionProgram::delete($id);
        AuditLog::record('extension_program', $id, 'delete');
        set_flash('success', 'Extension program deleted.');
        redirect('admin/programs?tab=extension');
    }

    // ---- Extension projects ----

    public function storeProject(): void
    {
        $this->guard('admin/programs?tab=projects');
        $data = $this->projectInput('admin/programs?tab=projects');
        $id = ExtensionProject::create($data);
        AuditLog::record('extension_project', $id, 'create', ['title' => $data['title']]);
        set_flash('success', 'Extension project added. Add its planned activities below.');
        redirect('admin/programs/projects/' . $id);
    }

    public function showProject(int $id): void
    {
        $project = ExtensionProject::find($id) ?? $this->notFound('admin/programs?tab=projects');
        view('admin.programs.project-edit', [
            'project' => $project,
            'extensionPrograms' => ExtensionProgram::all(),
            'academicPrograms' => AcademicProgram::all(),
            'planned' => ExtensionProject::plannedActivities($id),
            'conducted' => ExtensionProject::conductedActivities($id),
        ]);
    }

    public function updateProject(int $id): void
    {
        $back = 'admin/programs/projects/' . $id;
        $this->guard($back);
        ExtensionProject::find($id) ?? $this->notFound('admin/programs?tab=projects');
        $data = $this->projectInput($back);
        ExtensionProject::update($id, $data);
        AuditLog::record('extension_project', $id, 'update', ['title' => $data['title']]);
        set_flash('success', 'Extension project updated.');
        redirect($back);
    }

    public function destroyProject(int $id): void
    {
        $this->guard('admin/programs?tab=projects');
        $reason = ExtensionProject::deleteBlockingReason($id);
        if ($reason !== null) {
            $this->fail($reason, 'admin/programs/projects/' . $id);
        }
        ExtensionProject::delete($id);
        AuditLog::record('extension_project', $id, 'delete');
        set_flash('success', 'Extension project deleted.');
        redirect('admin/programs?tab=projects');
    }

    public function storePlannedActivity(int $id): void
    {
        $back = 'admin/programs/projects/' . $id;
        $this->guard($back);
        ExtensionProject::find($id) ?? $this->notFound('admin/programs?tab=projects');
        $title = trim((string) ($_POST['activity_title'] ?? ''));
        if ($title === '') {
            $this->fail('Activity title is required.', $back);
        }
        ExtensionProject::addPlannedActivity($id, [
            'title' => substr($title, 0, 500),
            'coordinators' => trim((string) ($_POST['activity_coordinators'] ?? '')) ?: null,
            'participants' => $this->intOrNull($_POST['activity_participants'] ?? null),
            'budget' => $this->moneyOrNull($_POST['activity_budget'] ?? null),
            'schedule' => substr(trim((string) ($_POST['activity_schedule'] ?? '')), 0, 255) ?: null,
        ]);
        set_flash('success', 'Planned activity added.');
        redirect($back);
    }

    public function destroyPlannedActivity(int $id, int $activityId): void
    {
        $back = 'admin/programs/projects/' . $id;
        $this->guard($back);
        ExtensionProject::deletePlannedActivity($id, $activityId);
        set_flash('success', 'Planned activity removed.');
        redirect($back);
    }

    // ---- Input helpers ----

    /** @return array{college_id: ?int, name: string, acronym: ?string, description: ?string, agenda_period: ?string, is_active: bool} */
    private function extensionProgramInput(): array
    {
        return [
            'college_id' => $this->collegeIdFromPost(),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'acronym' => trim((string) ($_POST['acronym'] ?? '')) ?: null,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'agenda_period' => trim((string) ($_POST['agenda_period'] ?? '')) ?: null,
            'is_active' => !array_key_exists('is_active_present', $_POST) || !empty($_POST['is_active']),
        ];
    }

    private function validateExtensionProgram(array $data, ?int $id, string $back): void
    {
        if ($data['name'] === '' || strlen($data['name']) > 255) {
            $this->fail('Program name is required (255 characters or less).', $back);
        }
        if ($data['acronym'] !== null && strlen($data['acronym']) > 40) {
            $this->fail('Acronym must be 40 characters or less.', $back);
        }
        if (ExtensionProgram::nameExists($data['college_id'], $data['name'], $id)) {
            $this->fail('This college already has an extension program with that name.', $back);
        }
    }

    /** @return array<string, mixed> */
    private function projectInput(string $back): array
    {
        $program = ExtensionProgram::find((int) ($_POST['extension_program_id'] ?? 0));
        if ($program === null) {
            $this->fail('Choose the extension program this project belongs to.', $back);
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '' || strlen($title) > 500) {
            $this->fail('Project title is required (500 characters or less).', $back);
        }
        $status = strtolower(trim((string) ($_POST['status'] ?? 'planned')));
        $academicId = (int) ($_POST['academic_program_id'] ?? 0);

        return [
            'extension_program_id' => (int) $program['id'],
            // The project belongs to its program's college.
            'college_id' => $program['college_id'] !== null ? (int) $program['college_id'] : null,
            'academic_program_id' => $academicId > 0 && AcademicProgram::find($academicId) !== null ? $academicId : null,
            'campus' => substr(trim((string) ($_POST['campus'] ?? '')), 0, 80) ?: null,
            'title' => $title,
            'leader_name' => substr(trim((string) ($_POST['leader_name'] ?? '')), 0, 255) ?: null,
            'leader_user_id' => null,
            'year' => ($y = (int) ($_POST['year'] ?? 0)) >= 2000 && $y <= 2100 ? $y : null,
            'total_budget' => $this->moneyOrNull($_POST['total_budget'] ?? null),
            'target_participants' => $this->intOrNull($_POST['target_participants'] ?? null),
            'status' => array_key_exists($status, ExtensionProject::STATUSES) ? $status : 'planned',
        ];
    }

    private function collegeIdFromPost(): ?int
    {
        $id = (int) ($_POST['college_id'] ?? 0);

        return $id > 0 && College::findById($id) !== null ? $id : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value !== '' && ctype_digit($value) ? (int) $value : null;
    }

    private function moneyOrNull(mixed $value): ?float
    {
        $value = str_replace([',', ' '], '', trim((string) $value));

        return $value !== '' && is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : null;
    }

    private function guard(string $back): void
    {
        if (!verify_csrf()) {
            $this->fail('Invalid session. Please try again.', $back);
        }
    }

    private function fail(string $message, string $back): never
    {
        set_flash('error', $message);
        redirect($back);
    }

    private function notFound(string $back): never
    {
        $this->fail('Record not found.', $back);
    }
}
