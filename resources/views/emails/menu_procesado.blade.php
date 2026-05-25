<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú procesado</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#0f172a;padding:28px 40px;text-align:center;">
                            <span style="font-size:28px;font-weight:900;color:#f59e0b;letter-spacing:-1px;">ServiME</span>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 40px 32px;">
                            <p style="margin:0 0 16px;font-size:22px;font-weight:700;color:#0f172a;">
                                ✅ ¡Tu menú está listo!
                            </p>
                            <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">
                                Hola <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">
                                El menú <strong>"{{ $menuName }}"</strong>
                                @if($comercioName) de <strong>{{ $comercioName }}</strong>@endif
                                fue procesado exitosamente por la IA. Ya podés activarlo y compartirlo con tus clientes.
                            </p>
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:8px;background:#f59e0b;">
                                        <a href="{{ $menuUrl }}"
                                           style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:700;color:#0f172a;text-decoration:none;">
                                            Ver mis menús
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">
                                ServiME — Menús digitales con IA para bares y restaurantes
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

