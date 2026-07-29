<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Quotation - {{ $quotation->quotation_no }}</title>
    <style>
        @page { size:letter portrait; margin:0; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e5e7eb; color:#1f2933; font-family:Arial,Helvetica,sans-serif; font-size:13px; }
        .print-button { margin:12px; padding:8px 14px; border:1px solid #1f2937; border-radius:4px; background:#fff; cursor:pointer; font-weight:700; }

        .qp-sheet { width:8.5in; min-height:11in; margin:0 auto; padding:.25in .28in; background:#fff; border:1px solid #b8c0ca; overflow:hidden; }
        .qp-top { display:grid; grid-template-columns:1fr 68mm; gap:4mm; border-bottom:1px solid #aeb7c2; padding-bottom:1.8mm; }
        .qp-company { display:grid; grid-template-columns:18mm 1fr; gap:2mm; align-items:start; }
        .qp-logo { width:16mm; height:13mm; object-fit:contain; }
        .qp-company-name { margin:0; color:#3e4a55; font-family:"Arial Narrow",Arial,sans-serif; font-size:18px; font-weight:900; line-height:1; letter-spacing:.5px; text-transform:uppercase; }
        .qp-company-lines { margin-top:1.7mm; color:#4b5563; font-size:13px; line-height:1.35; }
        .qp-title { color:#4f8ec7; font-size:15px; font-weight:900; letter-spacing:.4px; text-align:right; text-transform:uppercase; }
        .qp-info-grid { margin-top:1mm; display:grid; grid-template-columns:36mm 32mm; border-top:1px solid #c8d0d8; border-left:1px solid #c8d0d8; }
        .qp-info-label,.qp-info-value { min-height:3.8mm; border-right:1px solid #c8d0d8; border-bottom:1px solid #c8d0d8; padding:.35mm .9mm; font-size:13px; line-height:1; }
        .qp-info-label { color:#64748b; font-style:italic; text-align:right; }
        .qp-info-value { color:#374151; font-weight:800; text-align:center; }

        .qp-sold-to { margin-top:2.6mm; border-top:1px solid #9da7b2; border-left:1px solid #9da7b2; }
        .qp-sold-row { display:grid; grid-template-columns:26mm 1fr; min-height:5.7mm; }
        .qp-sold-label,.qp-sold-value { border-right:1px solid #9da7b2; border-bottom:1px solid #9da7b2; padding:1.2mm 1.7mm; }
        .qp-sold-label { color:#4b5563; font-weight:900; }
        .qp-sold-value { color:#374151; font-weight:700; }

        .qp-order-table { width:100%; margin-top:3mm; border-collapse:collapse; table-layout:fixed; }
        .qp-order-table th,.qp-order-table td { border:1px solid #9da7b2; padding:.75mm 1mm; font-size:13px; }
        .qp-order-table th { height:8.5mm; background:#8bd34b; color:#41513b; font-weight:900; text-align:center; text-transform:uppercase; }
        .qp-order-table td { height:5.45mm; color:#374151; }
        .qp-order-table tbody tr:nth-child(even) td { background:#f4f7f4; }
        .qp-c { text-align:center; }
        .qp-r { text-align:right; }
        .qp-desc { font-weight:700; }

        .qp-bottom { margin-top:3mm; display:flex; justify-content:flex-end; }
        .qp-total-box { border-left:1px solid #9da7b2; border-top:1px solid #9da7b2; width:52mm; }
        .qp-total-row { display:grid; grid-template-columns:1fr 22mm; }
        .qp-total-row div { min-height:6mm; border-right:1px solid #9da7b2; border-bottom:1px solid #9da7b2; padding:1mm 1.3mm; }
        .qp-total-row--green div { background:#8bd34b; color:#41513b; font-weight:900; }
        .qp-amount { color:#197245; font-size:13px; font-weight:900; text-align:right; }
        .qp-sign-label { color:#6b7280; font-size:13px; font-weight:800; text-transform:uppercase; }

        .qp-terms { margin-top:4mm; }
        .qp-terms h3 { font-size:13px; color:#4f8ec7; font-weight:900; text-transform:uppercase; margin:0 0 2mm; }
        .qp-terms ul { margin:0; padding-left:4mm; color:#374151; font-size:13px; }
        .qp-terms li { margin-bottom:.5mm; }

        @media print {
            body { background:#fff; }
            .print-button { display:none; }
            .qp-sheet { width:8.5in; height:11in; min-height:11in; margin:0; border:0; page-break-after:always; page-break-inside:avoid; }
            .qp-sheet:last-of-type { page-break-after:avoid; }
            .qp-order-table th,.qp-order-table tbody tr:nth-child(even) td,.qp-total-row--green div { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print Sales Quotation</button>

    @include('admin.sales-order.partials.quotation-print-sheet', [
        'quotation' => $quotation,
        'previewMode' => false,
        'logoUrl' => $logoUrl ?? asset(config('company.logo')),
    ])

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
