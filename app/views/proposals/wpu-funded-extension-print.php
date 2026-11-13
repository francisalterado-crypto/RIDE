<?php
/** Standalone print / PDF page for the ESO-03 Extension Project Proposal. */
/** @var array $proposal */
/** @var string $stepLabel */
/** @var bool $isUnsavedPreview */

$documentTitle = trim((string) ($proposal['title'] ?? '')) !== ''
    ? (string) $proposal['title']
    : 'Extension Program-Project Proposal';
$pdfFileName = trim((string) preg_replace('/[^A-Za-z0-9 _.-]+/', '', $documentTitle));
$pdfFileName = ($pdfFileName !== '' ? substr($pdfFileName, 0, 80) : 'Extension-Proposal') . ' - ESO-03.pdf';
$paperClass = 'proposal-paper completed-researches-paper eso-extension-paper wpu-funded-extension-paper';
$paperPartial = APP_PATH . '/views/proposals/_wpu-funded-extension-paper.php';
require APP_PATH . '/views/proposals/_print-document.php';
