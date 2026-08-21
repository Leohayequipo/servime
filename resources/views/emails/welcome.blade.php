<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    {{-- Sin esto, Gmail y Apple Mail invierten la paleta por su cuenta y el crema
         termina en gris sucio con el terracota apagado. --}}
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Bienvenido a ServiME</title>
    <style>
        /* Los clientes de correo ignoran casi todo lo que no sea inline. Acá va sólo
           lo que no se puede poner inline: la media query del mobile. */
        @media only screen and (max-width: 620px) {
            .contenedor { width: 100% !important; }
            .pad        { padding-left: 22px !important; padding-right: 22px !important; }
            .col        { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#F2EDE3;">

    {{-- Preheader: es lo que se lee en la bandeja debajo del asunto. Oculto en el cuerpo. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; height:0; width:0;">
        Tu cuenta ya está lista. Adentro están tus datos de acceso para empezar.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F2EDE3;">
        <tr>
            <td align="center" style="padding:28px 12px;">

                <table role="presentation" class="contenedor" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px; background-color:#FBF9F5; border-radius:20px; overflow:hidden; border:1px solid #E1D9C9;">

                    <!-- Cabecera -->
                    <tr>
                        <td align="center" style="background-color:#284236; padding:34px 30px 30px;">
                            <img src="{{ asset('images/logo_servime.png') }}" alt="ServiME" width="82" height="82" style="display:block; width:82px; height:82px; border:0; margin:0 auto 16px;">
                            <p style="margin:0 0 6px; font-family:Georgia,'Times New Roman',serif; font-size:25px; line-height:1.25; color:#F2EDE3;">
                                Tu menú ya sabe conversar
                            </p>
                            <p style="margin:0; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:15px; line-height:1.5; color:#A7BCA8;">
                                Bienvenido a ServiME, {{ $user->name }}
                            </p>
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td class="pad" style="padding:34px 34px 0; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0; font-size:16px; line-height:1.65; color:#55705F;">
                                Tu cuenta ya está creada. Desde acá vas a poder subir tu carta, generar el QR de las mesas
                                y ver qué le preguntan tus clientes al asistente.
                            </p>
                        </td>
                    </tr>

                    <!-- Datos de acceso: lo más importante del mail -->
                    <tr>
                        <td class="pad" style="padding:26px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F2EDE3; border:1px solid #E1D9C9; border-radius:14px;">
                                <tr>
                                    <td style="padding:22px 24px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                                        <p style="margin:0 0 16px; font-size:12px; letter-spacing:.09em; text-transform:uppercase; font-weight:bold; color:#6B8C7A;">
                                            Tus datos de acceso
                                        </p>

                                        <p style="margin:0 0 4px; font-size:13px; color:#55705F;">Email</p>
                                        <p style="margin:0 0 18px; font-size:16px; font-weight:bold; color:#284236; word-break:break-all;">
                                            {{ $user->email }}
                                        </p>

                                        @if(!empty($resetUrl))
                                            <p style="margin:0 0 10px; font-size:13px; color:#55705F;">Contraseña</p>

                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="border-radius:12px; background-color:#C0563A;">
                                                        <a href="{{ $resetUrl }}" style="display:inline-block; padding:13px 26px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:12px;">
                                                            Establecer mi contraseña
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>

                                            <p style="margin:12px 0 0; font-size:13px; line-height:1.55; color:#55705F;">
                                                El link vence en 60 minutos. Si se te pasa, entrá con
                                                <strong style="color:#284236;">¿Olvidaste tu contraseña?</strong> desde el login y te llega uno nuevo.
                                            </p>
                                        @else
                                            <p style="margin:0; font-size:14px; line-height:1.55; color:#55705F;">
                                                Ingresá con la contraseña que te pasamos y cambiala desde tu perfil cuando entres.
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Qué trae -->
                    <tr>
                        <td class="pad" style="padding:32px 34px 0; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 18px; font-family:Georgia,'Times New Roman',serif; font-size:19px; color:#284236;">
                                Lo que vas a tener
                            </p>

                            @php
                                // En dos columnas por fila: los clientes de correo no soportan grid,
                                // así que la grilla se arma con celdas de tabla.
                                $bloques = [
                                    ['Un asistente que conversa', 'Responde sobre tu carta las 24 horas, con tus platos reales.'],
                                    ['En cuatro idiomas',         'Detecta el idioma del celular del cliente y le responde en el suyo.'],
                                    ['Pre-pedido en la mesa',     'El cliente arma lo que quiere y el camarero solo confirma.'],
                                    ['Qué se pide y qué no',      'Los platos más consultados y lo que buscan y no encontraron.'],
                                ];
                            @endphp

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                @foreach (array_chunk($bloques, 2) as $fila)
                                    <tr>
                                        @foreach ($fila as $i => $bloque)
                                            <td class="col" width="50%" valign="top" style="padding:0 {{ $i === 0 ? '8px' : '0' }} 14px {{ $i === 0 ? '0' : '8px' }};">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F2EDE3; border:1px solid #E1D9C9; border-radius:12px;">
                                                    <tr>
                                                        <td style="padding:16px 18px;">
                                                            <p style="margin:0 0 5px; font-size:15px; font-weight:bold; color:#284236;">{{ $bloque[0] }}</p>
                                                            <p style="margin:0; font-size:13.5px; line-height:1.55; color:#55705F;">{{ $bloque[1] }}</p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td class="pad" align="center" style="padding:26px 34px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:14px; background-color:#C0563A;">
                                        <a href="{{ rtrim(config('app.url'), '/') }}/dashboard" style="display:inline-block; padding:15px 34px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:14px;">
                                            Entrar a mi panel
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Primer paso -->
                    <tr>
                        <td class="pad" style="padding:28px 34px 34px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FAF3EC; border-left:3px solid #D4B08C; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 20px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                                        <p style="margin:0 0 5px; font-size:14px; font-weight:bold; color:#284236;">Por dónde empezar</p>
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#55705F;">
                                            Subí tu carta en PDF o sacale una foto: la IA extrae los platos, los precios y las
                                            categorías sola. Después la repasás y ya podés generar el QR.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#284236; padding:28px 34px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            @php
                                // config() y no env(): con la config cacheada en producción
                                // env() devuelve null y el dato de contacto desaparecía.
                                $soporteEmail = config('services.support.email');
                                $soporteWa    = config('services.support.whatsapp');
                            @endphp

                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#A7BCA8;">
                                ¿Alguna duda? Respondé este mail y te contestamos.
                            </p>

                            <p style="margin:0 0 18px; font-size:14px;">
                                @if($soporteEmail)
                                    <a href="mailto:{{ $soporteEmail }}" style="color:#D4B08C; text-decoration:none;">{{ $soporteEmail }}</a>
                                @endif
                                @if($soporteEmail && $soporteWa)
                                    <span style="color:#6B8C7A;">&nbsp;·&nbsp;</span>
                                @endif
                                @if($soporteWa)
                                    <a href="https://wa.me/{{ $soporteWa }}" style="color:#D4B08C; text-decoration:none;">WhatsApp</a>
                                @endif
                            </p>

                            <p style="margin:0; padding-top:16px; border-top:1px solid rgba(242,237,227,.16); font-size:12px; line-height:1.6; color:#6B8C7A;">
                                Te escribimos a {{ $user->email }} porque se creó una cuenta de ServiME con esa dirección.<br>
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
