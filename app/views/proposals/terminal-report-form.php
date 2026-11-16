<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Terminal Report | RIDE Hub';
$pageHeading = 'Terminal Report';
$pageSubtitle = $isEdit
    ? 'Update your terminal report before saving or resubmitting.'
    : 'Project terminal report (WPU-QSF-RDE-VPRDE-21).';

$reportFormType = 'terminal_report';
$reportLabel = 'Terminal Report';
$reportRoute = 'terminal-report';
require APP_PATH . '/views/proposals/_project-report-form.php';
