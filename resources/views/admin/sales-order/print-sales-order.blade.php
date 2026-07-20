<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>A-Sales Order - {{ $order->so_no }}</title>
    @include('admin.sales-order.partials.print-sheet-styles')
</head>
<body>
    <button class="print-button" onclick="window.print()">Print A-Sales Order</button>

    @include('admin.sales-order.partials.print-sheet', ['mode' => 'vat'])

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
