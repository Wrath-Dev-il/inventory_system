<style>
    @page { size: letter portrait; margin: 0; }
    * { box-sizing: border-box; }
    .sheet,
    .sheet * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    body {
        margin: 0;
        background: #e5e7eb;
        color: #1f2933;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
    }
    .print-button {
        margin: 12px;
        padding: 8px 14px;
        border: 1px solid #1f2937;
        border-radius: 4px;
        background: #fff;
        cursor: pointer;
        font-weight: 700;
    }
    .sheet {
        width: 8.5in;
        min-height: 11in;
        margin: 0 auto;
        padding: .25in .28in;
        background: #fff;
        border: 1px solid #b8c0ca;
        overflow: hidden;
    }
    .sheet + .sheet {
        margin-top: 18px;
        page-break-before: always;
    }
    .top {
        display: grid;
        grid-template-columns: 1fr 62mm;
        gap: 4mm;
        border-bottom: 1px solid #aeb7c2;
        padding-bottom: 1.8mm;
    }
    .company {
        display: grid;
        grid-template-columns: 16mm 1fr;
        gap: 2mm;
        align-items: start;
    }
    .company-logo {
        width: 15mm;
        height: 12mm;
        object-fit: contain;
    }
    .company-name {
        margin: 0;
        color: #3e4a55;
        font-family: "Arial Narrow", Arial, sans-serif;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: .5px;
        line-height: 1;
        text-transform: uppercase;
    }
    .company-lines {
        margin-top: 1.7mm;
        color: #4b5563;
        font-size: 13px;
        line-height: 1.35;
    }
    .doc-title {
        color: #4f8ec7;
        font-size: 15px;
        font-weight: 900;
        letter-spacing: .4px;
        text-align: right;
        text-transform: uppercase;
    }
    .info-grid {
        margin-top: 1mm;
        display: grid;
        grid-template-columns: 34mm 28mm;
        border-top: 1px solid #c8d0d8;
        border-left: 1px solid #c8d0d8;
    }
    .info-grid div {
        min-height: 3.8mm;
        border-right: 1px solid #c8d0d8;
        border-bottom: 1px solid #c8d0d8;
        padding: .35mm .9mm;
        font-size: 13px;
        line-height: 1;
    }
    .info-grid .label {
        color: #64748b;
        font-style: italic;
        text-align: right;
    }
    .info-grid .value {
        color: #374151;
        font-weight: 800;
        text-align: center;
    }
    .sold-to {
        margin-top: 2.6mm;
        border-top: 1px solid #9da7b2;
        border-left: 1px solid #9da7b2;
    }
    .sold-row {
        display: grid;
        grid-template-columns: 18mm 1fr;
        min-height: 5.7mm;
    }
    .sold-label,
    .sold-value {
        border-right: 1px solid #9da7b2;
        border-bottom: 1px solid #9da7b2;
        padding: 1.2mm 1.7mm;
    }
    .sold-label {
        color: #4b5563;
        font-weight: 900;
    }
    .sold-value {
        color: #374151;
        font-weight: 700;
    }
    .order-table {
        width: 100%;
        margin-top: 3mm;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .order-table th,
    .order-table td {
        border: 1px solid #9da7b2;
        padding: .75mm 1mm;
    }
    .order-table th {
        height: 8.5mm;
        background: #8bd34b;
        color: #41513b;
        font-size: 13px;
        font-weight: 900;
        text-align: center;
        text-transform: uppercase;
    }
    .order-table td {
        height: 5.45mm;
        color: #374151;
        font-size: 13px;
    }
    .order-table tbody tr:nth-child(even) td {
        background: #f4f7f4;
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .desc { font-weight: 700; }
    .bottom {
        margin-top: 3mm;
        display: flex;
        justify-content: flex-end;
    }
    .total-box {
        border-left: 1px solid #9da7b2;
        border-top: 1px solid #9da7b2;
        width: 48mm;
    }
    .total-row {
        display: grid;
        grid-template-columns: 1fr 20mm;
    }
    .total-row div {
        min-height: 6mm;
        border-right: 1px solid #9da7b2;
        border-bottom: 1px solid #9da7b2;
        padding: 1mm 1.3mm;
    }
    .total-row--green div {
        background: #8bd34b;
        color: #41513b;
        font-weight: 900;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .total-row .amount {
        color: #197245;
        font-size: 13px;
        font-weight: 900;
        text-align: right;
    }
    .sign-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }
    @media print {
        body { background: #fff; }
        .order-table th,
        .total-row--green div,
        .order-table tbody tr:nth-child(even) td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-button { display: none; }
        .sheet {
            width: 8.5in;
            height: 11in;
            min-height: 11in;
            margin: 0;
            border: 0;
            page-break-after: always;
            page-break-inside: avoid;
        }
        .sheet:last-of-type {
            page-break-after: avoid;
        }
    }
</style>
