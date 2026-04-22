<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $title ?? 'Lettre' }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "DejaVu Serif", Georgia, serif;
        font-size: 11.5pt;
        color: #1a1a1a;
        line-height: 1.75;
        background: #fff;
        padding: 40px 50px;
    }
    .header {
        border-bottom: 2px solid #1a1a1a;
        padding-bottom: 14px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand {
        font-size: 9pt;
        color: #888;
        font-family: "DejaVu Sans", Arial, sans-serif;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .doc-type {
        font-size: 9pt;
        color: #888;
        font-family: "DejaVu Sans", Arial, sans-serif;
        text-align: right;
    }
    .content {
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 11.5pt;
        line-height: 1.8;
        color: #1a1a1a;
        margin-top: 10px;
    }
    .footer {
        position: fixed;
        bottom: 20px;
        left: 50px;
        right: 50px;
        border-top: 1px solid #e0e0e0;
        padding-top: 8px;
        font-size: 8pt;
        color: #aaa;
        font-family: "DejaVu Sans", Arial, sans-serif;
        display: flex;
        justify-content: space-between;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">CoachBRVM · LettreCI</div>
        <div class="doc-type">
            Généré le {{ now()->isoFormat('D MMMM YYYY') }}<br>
            {{ $templateName ?? '' }}
        </div>
    </div>

    <div class="content">{{ $content }}</div>

    <div class="footer">
        <span>Généré par LettreCI — coachbrvm.com</span>
        <span>Document non contractuel · Usage personnel</span>
    </div>
</body>
</html>
