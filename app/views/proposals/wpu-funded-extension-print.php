<?php
/** Standalone print / PDF page for the ESO-03 Extension Program/Project Proposal. */
/** @var array $proposal */
/** @var string $stepLabel */
/** @var bool $isUnsavedPreview */

$documentTitle = trim((string) ($proposal['title'] ?? '')) !== ''
    ? (string) $proposal['title']
    : 'Extension Program-Project Proposal';
$pdfFileName = trim((string) preg_replace('/[^A-Za-z0-9 _.-]+/', '', $documentTitle));
$pdfFileName = ($pdfFileName !== '' ? substr($pdfFileName, 0, 80) : 'Extension-Proposal') . ' - ESO-03.pdf';
$appCssPath = BASE_PATH . '/public/assets/css/app.css';
$appCssVersion = is_file($appCssPath) ? (string) filemtime($appCssPath) : '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($documentTitle) ?> — Print Preview</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/ride-logo.png') ?>" sizes="32x32">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= htmlspecialchars($appCssVersion) ?>">
    <style>
        body.print-preview-body { background: #525659; margin: 0; padding: 0 0 3rem; font-family: Georgia, "Times New Roman", serif; }
        .print-toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
            padding: 0.75rem 1rem; background: #323639; color: #fff;
            font-family: Inter, system-ui, sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .print-toolbar-title { font-weight: 600; font-size: 0.95rem; margin: 0; }
        .print-toolbar-note { font-size: 0.8rem; color: #fbbf24; margin: 0.15rem 0 0; }
        .print-toolbar-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .print-toolbar .btn { border-radius: 8px; }
        .print-toolbar .btn-outline { color: #fff; border-color: #9ca3af; }
        .print-sheet {
            width: 210mm; max-width: calc(100% - 32px); box-sizing: border-box;
            margin: 1.5rem auto 0; padding: 15mm 14mm;
            background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,0.35);
        }
        .print-sheet.proposal-paper { border: 0; }
        .print-sheet .proposal-section-title { font-size: 1.15rem; }
        .print-sheet .proposal-table-wrap,
        .print-sheet .trainings-conducted-table-wrap { overflow: visible; border: 0; }
        .print-sheet .proposal-table,
        .print-sheet .trainings-conducted-table {
            width: 100% !important; min-width: 0 !important; table-layout: fixed; font-size: 0.78rem;
        }
        .print-sheet .proposal-table th,
        .print-sheet .proposal-table td { overflow-wrap: anywhere; }
        .print-sheet tr, .print-sheet .eso-signoff-grid > div { page-break-inside: avoid; break-inside: avoid; }
        .print-watermark {
            margin: 0 0 1rem; padding: 0.4rem 0.6rem; border: 1px dashed #b45309; color: #b45309;
            font: 600 0.75rem Inter, system-ui, sans-serif; text-align: center; text-transform: uppercase; letter-spacing: 0.05em;
        }
        @page { size: A4; margin: 12mm; }
        @media print {
            body.print-preview-body { background: #fff; padding: 0; }
            .print-toolbar { display: none !important; }
            .print-sheet { width: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }
        }
        @media (max-width: 640px) {
            .print-sheet { padding: 1rem; }
        }
    </style>
</head>
<body class="print-preview-body">
    <div class="print-toolbar">
        <div>
            <p class="print-toolbar-title"><i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($documentTitle) ?></p>
            <?php if ($isUnsavedPreview): ?>
                <p class="print-toolbar-note">Preview of unsaved changes — save the form to keep them.</p>
            <?php endif; ?>
        </div>
        <div class="print-toolbar-actions">
            <button type="button" class="btn btn-accent" id="print-btn"><i class="fa-solid fa-print"></i> Print</button>
            <button type="button" class="btn btn-accent" id="pdf-btn"><i class="fa-solid fa-file-pdf"></i> Save as PDF</button>
            <button type="button" class="btn btn-outline" id="close-btn"><i class="fa-solid fa-xmark"></i> Close</button>
        </div>
    </div>

    <div class="print-sheet proposal-paper completed-researches-paper eso-extension-paper wpu-funded-extension-paper" id="print-sheet">
        <?php if ($isUnsavedPreview): ?>
            <p class="print-watermark" data-html2canvas-ignore>Preview — not yet saved</p>
        <?php endif; ?>
        <?php require APP_PATH . '/views/proposals/_wpu-funded-extension-paper.php'; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
    (() => {
        document.getElementById('print-btn').addEventListener('click', () => window.print());
        document.getElementById('close-btn').addEventListener('click', () => window.close());

        const pdfButton = document.getElementById('pdf-btn');
        pdfButton.addEventListener('click', async () => {
            // Fall back to the browser's own "Save as PDF" print destination if the library didn't load.
            if (typeof window.html2pdf !== 'function') {
                window.print();
                return;
            }
            const original = pdfButton.innerHTML;
            pdfButton.disabled = true;
            pdfButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating…';
            const sheet = document.getElementById('print-sheet');
            sheet.style.boxShadow = 'none';
            try {
                await window.html2pdf().set({
                    margin: [10, 10, 12, 10],
                    filename: <?= json_encode($pdfFileName) ?>,
                    image: { type: 'jpeg', quality: 0.96 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.eso-signoff-grid > div', '.proposal-section-title'] },
                }).from(sheet).save();
            } finally {
                sheet.style.boxShadow = '';
                pdfButton.disabled = false;
                pdfButton.innerHTML = original;
            }
        });
    })();
    </script>
</body>
</html>
