<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f7; padding: 24px; margin: 0;">

<div style="max-width: 600px; margin: 0 auto;">

    {{-- Header --}}
    <div style="background: #060910; border-radius: 8px 8px 0 0; padding: 28px 32px; text-align: center;">
        <a href="{{ url('/') }}" style="font-family: Georgia, serif; font-size: 22px; font-weight: 900; color: #C9A84C; text-decoration: none; letter-spacing: .02em;">
            Coach<em style="font-style: normal; color: #E8EAF0;">BRVM</em>
        </a>
    </div>

    {{-- Body --}}
    <div style="background: #ffffff; padding: 36px 32px; border: 1px solid #e0e0e8; border-top: none;">

        <div style="font-size: 36px; text-align: center; margin-bottom: 20px;">💬</div>

        <h2 style="font-family: Georgia, serif; font-size: 22px; font-weight: 700; color: #0d0f1a; margin: 0 0 8px; text-align: center;">
            Nouvelle réponse sur votre sujet
        </h2>
        <p style="font-size: 13px; color: #888; text-align: center; margin: 0 0 28px; letter-spacing: .04em; text-transform: uppercase;">
            Forum Coach BRVM
        </p>

        <p style="font-size: 15px; color: #333; margin: 0 0 20px;">
            Bonjour <strong>{{ $topic->user->name ?? 'Membre' }}</strong>,
        </p>

        <p style="font-size: 15px; color: #555; line-height: 1.7; margin: 0 0 24px;">
            <strong style="color: #0d0f1a;">{{ $post->user->name ?? 'Un membre' }}</strong>
            a répondu à votre sujet sur le forum Coach BRVM.
        </p>

        {{-- Topic title --}}
        <div style="background: #f8f8fb; border-left: 3px solid #C9A84C; border-radius: 0 4px 4px 0; padding: 14px 18px; margin-bottom: 24px;">
            <p style="font-size: 11px; color: #999; letter-spacing: .1em; text-transform: uppercase; margin: 0 0 5px;">Sujet</p>
            <p style="font-size: 16px; font-weight: 700; color: #0d0f1a; margin: 0; line-height: 1.4;">
                {{ $topic->title }}
            </p>
        </div>

        {{-- Reply excerpt --}}
        <div style="background: #fafafa; border: 1px solid #e8e8ee; border-radius: 6px; padding: 18px 20px; margin-bottom: 28px;">
            <p style="font-size: 11px; color: #999; letter-spacing: .1em; text-transform: uppercase; margin: 0 0 10px;">
                Extrait de la réponse
            </p>
            <p style="font-size: 14px; color: #444; line-height: 1.75; margin: 0; font-style: italic;">
                "{{ \Illuminate\Support\Str::limit($post->body, 200) }}"
            </p>
        </div>

        {{-- CTA --}}
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="{{ $topicUrl }}"
               style="display: inline-block; background: #C9A84C; color: #060910 !important; text-decoration: none;
                      padding: 14px 32px; border-radius: 4px; font-weight: 700; font-size: 14px;
                      letter-spacing: .04em; text-transform: uppercase;">
                Voir la réponse →
            </a>
        </div>

        <p style="font-size: 13px; color: #888; text-align: center; line-height: 1.6; margin: 0;">
            Si vous ne souhaitez plus recevoir ces notifications, vous pouvez les gérer depuis votre profil.
        </p>

    </div>

    {{-- Footer --}}
    <div style="background: #060910; border-radius: 0 0 8px 8px; padding: 18px 32px; text-align: center;">
        <p style="font-size: 11px; color: #6B7590; margin: 0; letter-spacing: .06em; text-transform: uppercase;">
            Coach BRVM · Comprends. Analyse. Progresse.
        </p>
        <p style="font-size: 11px; color: #444; margin: 6px 0 0;">
            Cet email a été envoyé à {{ $topic->user->email ?? '' }}
        </p>
    </div>

</div>

</body>
</html>
