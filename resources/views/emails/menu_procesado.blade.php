<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Tu menú está listo — ServiME</title>
    <style>
        @media only screen and (max-width: 620px) {
            .contenedor { width: 100% !important; }
            .pad        { padding-left: 22px !important; padding-right: 22px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#F2EDE3;">

    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; height:0; width:0;">
        La IA terminó de leer tu carta. Ya podés revisarla y activarla.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F2EDE3;">
        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation" class="contenedor" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#FBF9F5; border-radius:20px; overflow:hidden; border:1px solid #E1D9C9;">

                    <!-- Cabecera -->
                    <tr>
                        <td align="center" style="background-color:#284236; padding:30px 30px 26px;">
                            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" width="66" height="66" style="display:block; width:66px; height:66px; border:0; margin:0 auto 12px;">
                            <p style="margin:0; font-family:Georgia,'Times New Roman',serif; font-size:23px; line-height:1.3; color:#F2EDE3;">
                                Tu menú está listo
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo -->
                    <tr>
                        <td class="pad" style="padding:32px 34px 0; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 14px; font-size:16px; line-height:1.65; color:#55705F;">
                                Hola <strong style="color:#284236;">{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0; font-size:16px; line-height:1.65; color:#55705F;">
                                La IA terminó de leer <strong style="color:#284236;">"{{ $menuName }}"</strong>@if($comercioName) de <strong style="color:#284236;">{{ $comercioName }}</strong>@endif.
                                Ya podés revisar los platos, ajustar lo que haga falta y activarlo para tus clientes.
                            </p>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td class="pad" style="padding:26px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:14px; background-color:#C0563A;">
                                        <a href="{{ $menuUrl }}" style="display:inline-block; padding:14px 32px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            Ver mis menús
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Recordatorio -->
                    <tr>
                        <td class="pad" style="padding:26px 34px 34px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FAF3EC; border-left:3px solid #D4B08C; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 20px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#55705F;">
                                            Conviene darle una repasada antes de activarlo: la IA lee muy bien, pero si la
                                            carta tenía algún precio borroso o un nombre partido en dos renglones, es el
                                            momento de corregirlo.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#284236; padding:24px 34px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#6B8C7A;">
                                ServiME — menús digitales con IA para bares y restaurantes<br>
                                © {{ date('Y') }} ServiME
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
