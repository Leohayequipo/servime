<?php

/**
 * Genera la imagen que se ve al compartir servime.net (WhatsApp, redes).
 *
 * 1200x630 es el formato estándar de Open Graph. Fondo verde profundo y no crema
 * a propósito: la preview se muestra sobre el fondo blanco del chat, y el crema
 * se perdía contra él.
 */
$W = 1200;
$H = 630;

$SERIF = 'C:/Windows/Fonts/georgiab.ttf';   // sustituto de Fraunces
$SANS = 'C:/Windows/Fonts/segoeui.ttf';
$SANS_B = 'C:/Windows/Fonts/segoeuib.ttf';

foreach ([$SERIF, $SANS, $SANS_B] as $f) {
    if (! file_exists($f)) {
        fwrite(STDERR, "Falta la fuente: $f\n");
        exit(1);
    }
}

$im = imagecreatetruecolor($W, $H);
imagealphablending($im, true);

$verde = imagecolorallocate($im, 27, 44, 35);
$crema = imagecolorallocate($im, 242, 237, 227);
$terra = imagecolorallocate($im, 232, 146, 122);
$terraBarra = imagecolorallocate($im, 192, 86, 58);
$salvia = imagecolorallocate($im, 167, 188, 168);
$wood = imagecolorallocate($im, 212, 176, 140);

imagefill($im, 0, 0, $verde);

// Sin adornos de fondo a propósito: probé un degradado radial (salía en bandas
// escalonadas) y el logo como marca de agua (imagecopymerge ignora el canal alfa
// y dejaba un rectángulo visible). El verde plano se lee mejor en el chat.

// Barra de acento a la izquierda
imagefilledrectangle($im, 0, 0, 13, $H, $terraBarra);

// ── Logo ──
$logo = imagecreatefrompng('resources/brand/logo_servime_1254.png');
$lado = 104;
imagecopyresampled($im, $logo, 82, 74, 0, 0, $lado, $lado, imagesx($logo), imagesy($logo));

// ── Textos ──
// Georgia es más angosta que Fraunces, así que los tamaños están ajustados a ojo
// contra el render, no copiados de la landing.
imagettftext($im, 54, 0, 82, 300, $crema, $SERIF, 'Menú digital con QR e IA');
imagettftext($im, 40, 0, 82, 372, $terra, $SERIF, 'para restaurantes y bares');

imagettftext($im, 23, 0, 82, 448, $salvia, $SANS, 'Tus clientes escanean, preguntan y piden desde la mesa.');
imagettftext($im, 23, 0, 82, 486, $salvia, $SANS, 'Sin apps, sin login y en cuatro idiomas.');

imagettftext($im, 24, 0, 82, 566, $wood, $SANS_B, 'servime.net');

imagepng($im, 'public/images/og-image.png', 9);

printf("og-image.png  %dx%d  %.1f KB\n", $W, $H, filesize('public/images/og-image.png') / 1024);
