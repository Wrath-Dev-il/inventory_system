<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Invoice - {{ $order->so_no }}</title>
    @include('admin.sales-order.partials.print-sheet-styles')
</head>
<body>
    <button class="print-button" onclick="window.print()">Print Sales Invoice</button>

    @include('admin.sales-order.partials.print-sheet', ['mode' => 'no-vat'])

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
