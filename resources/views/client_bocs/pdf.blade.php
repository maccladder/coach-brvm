<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BOC {{ $boc->title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }
        h1, h2, h3 {
            margin: 0 0 8px;
        }
        .header {
            border-bottom: 1px solid #ccc;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            background: #198754;
            color: #fff;
            font-size: 10px;
        }
        .section {
            margin-top: 16px;
        }
        pre {
            white-space: pre-wrap;
            font-family: DejaVu Sans Mono, monospace;
            background: #f7f7f7;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
        }
        .chart {
            text-align: center;
            margin-top: 12px;
        }
        .chart img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Analyse de ton BOC</h1>
    <p>
        <strong>Titre :</strong> {{ $boc->title }}<br>
        <strong>Date du BOC :</strong> {{ optional($boc->boc_date)->format('d/m/Y') }}<br>
        <span class="badge">Coach BRVM</span>
    </p>
</div>

<div class="section">
    <h2>📝 Interprétation détaillée</h2>
    <pre>{{ $markdown }}</pre>
</div>

@if($chartPath && file_exists($chartPath))
    <div class="section">
        <h2>🌐 Vue d’ensemble du marché</h2>
        <p>Capture des bulles BRVM le jour de ton BOC.</p>
        <div class="chart">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($chartPath)) }}" alt="Bulles BRVM">
        </div>
    </div>
@endif

</body>
</html>
