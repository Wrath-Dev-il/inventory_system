<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cannot Print — No Items</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: #1f2933;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 40px 48px;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .card h2 {
            margin: 0 0 8px;
            font-size: 20px;
            color: #991b1b;
        }
        .card p {
            margin: 0;
            color: #6b7280;
        }
        .card button {
            margin-top: 20px;
            padding: 8px 20px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        .card button:hover { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Cannot Print</h2>
        <p>This Sales Order has no items to print.</p>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
