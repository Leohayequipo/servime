<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Recuperar tu contraseña — ServiME</title>
    <style>
        @media only screen and (max-width: 620px) {
            .contenedor { width: 100% !important; }
            .pad        { padding-left: 22px !important; padding-right: 22px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#F2EDE3;">

    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; height:0; width:0;">
        Tenés 60 minutos para elegir una contraseña nueva.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F2EDE3;">
        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation" class="contenedor" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px; background-color:#FBF9F5; border-radius:20px; overflow:hidden; border:1px solid #E1D9C9;">

                    <!-- Cabecera -->
                    <tr>
                        <td align="center" style="background-color:#284236; padding:32px 30px 28px;">
                            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" width="72" height="72" style="display:block; width:72px; height:72px; border:0; margin:0 auto 14px;">
                            <p style="margin:0; font-family:Georgia,'Times New Roman',serif; font-size:23px; line-height:1.3; color:#F2EDE3;">
                                Recuperar tu contraseña
                            </p>
                        </td>
                    </tr>

                    <!-- Mensaje -->
                    <tr>
                        <td class="pad" style="padding:32px 34px 0; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 14px; font-size:17px; font-weight:bold; color:#284236;">
                                Hola{{ isset($user->name) ? ', '.$user->name : '' }}
                            </p>
                            <p style="margin:0; font-size:16px; line-height:1.65; color:#55705F;">
                                Pediste recuperar tu contraseña de ServiME. Tocá el botón y elegí una nueva.
                            </p>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td class="pad" align="center" style="padding:28px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:14px; background-color:#C0563A;">
                                        <a href="{{ $url }}" style="display:inline-block; padding:15px 36px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            Elegir contraseña nueva
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Vencimiento -->
                    <tr>
                        <td class="pad" style="padding:24px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F2EDE3; border:1px solid #E1D9C9; border-radius:12px;">
                                <tr>
                                    <td align="center" style="padding:14px 20px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                                        <p style="margin:0; font-size:14px; line-height:1.55; color:#55705F;">
                                            El enlace vence en <strong style="color:#284236;">60 minutos</strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Si no fuiste vos -->
                    <tr>
                        <td class="pad" style="padding:16px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FAF3EC; border-left:3px solid #D4B08C; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 20px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#55705F;">
                                            <strong style="color:#284236;">¿No lo pediste?</strong>
                                            Podés ignorar este mail tranquilo: tu contraseña no cambia hasta que alguien
                                            entre a ese enlace y elija una nueva.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Enlace de respaldo: si el botón no abre, esto es lo único que salva el mail -->
                    <tr>
                        <td class="pad" style="padding:24px 34px 34px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6B8C7A;">
                                Si el botón no funciona, copiá y pegá esta dirección en tu navegador:
                            </p>
                            <p style="margin:0; font-size:12.5px; line-height:1.5; color:#55705F; word-break:break-all;">
                                <a href="{{ $url }}" style="color:#B4462A; text-decoration:underline;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#284236; padding:26px 34px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            @php
                                // config() y no un dato escrito a mano: así el contacto sale de un
                                // solo lugar y no queda desactualizado si cambia.
                                $soporteEmail = config('services.support.email');
                            @endphp

                            <p style="margin:0 0 12px; font-size:13px; line-height:1.6; color:#A7BCA8;">
                                Este mail se envía automáticamente.
                                @if($soporteEmail)
                                    Si necesitás ayuda, escribinos a
                                    <a href="mailto:{{ $soporteEmail }}" style="color:#D4B08C; text-decoration:none;">{{ $soporteEmail }}</a>.
                                @endif
                            </p>
                            <p style="margin:0; padding-top:14px; border-top:1px solid rgba(242,237,227,.16); font-size:12px; line-height:1.6; color:#6B8C7A;">
                                © {{ date('Y') }} ServiME — Buenos Aires, Argentina
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
