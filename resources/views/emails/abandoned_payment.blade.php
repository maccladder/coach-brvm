<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre achat sur Boursiv</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4; padding:40px 16px;">
        <tr>
            <td align="center">

                {{-- Contenu principal, max 600px --}}
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                       style="background-color:#ffffff; border-radius:8px; max-width:600px; width:100%;">

                    {{-- En-tête --}}
                    <tr>
                        <td style="padding:32px 40px 0; border-radius:8px 8px 0 0;">
                            <p style="margin:0; font-size:20px; font-weight:bold; color:#1a1a1a; letter-spacing:-0.3px;">
                                Boursiv
                            </p>
                        </td>
                    </tr>

                    {{-- Séparateur --}}
                    <tr>
                        <td style="padding:16px 40px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top:1px solid #ebebeb; font-size:0; line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding:28px 40px 0; color:#333333; font-size:15px; line-height:1.7;">
                            <p style="margin:0 0 16px;">
                                Nous avons constaté que votre tentative d'achat sur Boursiv n'a pas abouti.
                            </p>
                            <p style="margin:0 0 32px;">
                                Besoin d'aide pour finaliser votre commande&nbsp;? Notre équipe est disponible sur WhatsApp pour vous accompagner.
                            </p>
                        </td>
                    </tr>

                    {{-- Bouton WhatsApp (table-based pour Outlook) --}}
                    <tr>
                        <td align="center" style="padding:0 40px 32px;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#25D366"
                                        style="border-radius:8px; background-color:#25D366;">
                                        <a href="{{ $whatsappUrl }}"
                                           target="_blank"
                                           style="display:inline-block;
                                                  padding:14px 28px;
                                                  color:#ffffff;
                                                  font-size:15px;
                                                  font-weight:bold;
                                                  font-family:Arial,Helvetica,sans-serif;
                                                  text-decoration:none;
                                                  border-radius:8px;
                                                  background-color:#25D366;
                                                  mso-padding-alt:14px 28px;">
                                            <!--[if mso]><i style="letter-spacing:28px;mso-font-width:-100%;mso-text-raise:30pt">&nbsp;</i><![endif]-->
                                            Contacter le support sur WhatsApp
                                            <!--[if mso]><i style="letter-spacing:28px;mso-font-width:-100%">&nbsp;</i><![endif]-->
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Signature --}}
                    <tr>
                        <td style="padding:0 40px 36px; color:#555555; font-size:14px; line-height:1.6;">
                            <p style="margin:0;">L'équipe Boursiv.</p>
                        </td>
                    </tr>

                </table>
                {{-- Fin contenu principal --}}

            </td>
        </tr>
    </table>

</body>
</html>
