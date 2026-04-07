<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 [ADMIN] {{ $product->title }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; background: #060910; overflow: hidden; font-family: system-ui, sans-serif; }

        #admin-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 44px;
            background: rgba(255, 140, 0, 0.15);
            border-bottom: 2px solid rgba(255, 140, 0, 0.5);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; gap: 12px;
        }

        .bar-left { display: flex; align-items: center; gap: 10px; }
        .admin-badge {
            font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
            background: rgba(255,140,0,.2); color: #ff8c00;
            border: 1px solid rgba(255,140,0,.5); padding: 3px 10px; border-radius: 100px;
        }
        .bar-title { font-size: 12px; font-weight: 700; color: #E8EAF0; }

        .bar-btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            text-decoration: none; padding: 6px 12px; border-radius: 3px;
            cursor: pointer; border: 1px solid; transition: all .2s; background: transparent;
            color: #6B7590 !important; border-color: rgba(255,255,255,.15);
        }
        .bar-btn:hover { border-color: #ff8c00; color: #ff8c00 !important; }

        #game-frame {
            position: fixed; top: 44px; left: 0; right: 0; bottom: 0;
            width: 100%; height: calc(100% - 44px);
            border: none; background: #000;
        }
    </style>
</head>
<body>

    <div id="admin-bar">
        <div class="bar-left">
            <span class="admin-badge">👑 MODE ADMIN</span>
            <span class="bar-title">{{ $product->title }}</span>
        </div>
        <div>
            <a href="{{ route('admin.marketplace.show', $product) }}" class="bar-btn">
                ← Retour fiche produit
            </a>
        </div>
    </div>

    <iframe
        id="game-frame"
        sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-forms allow-popups allow-popups-to-escape-sandbox"
        allow="fullscreen"
        allowfullscreen
        src="{{ route('admin.marketplace.game-html', $product) }}"
        title="[ADMIN] {{ $product->title }}"
    ></iframe>

</body>
</html>
