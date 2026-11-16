<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Progress Report | RIDE Hub';
$pageHeading = 'Progress Report';
$pageSubtitle = $isEdit
    ? 'Update your progress report before saving or resubmitting.'
    : 'Quarterly project progress report (WPU-QSF-RDE-VPRDE-18).';

$reportFormType = 'progress_report';
$reportLabel = 'Progress Report';
$reportRoute = 'progress-report';
require APP_PATH . '/views/proposals/_project-report-form.php';
