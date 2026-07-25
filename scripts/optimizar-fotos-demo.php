<?php

/**
 * Optimiza las fotos del chat demo de la landing (public/images/demo/).
 *
 * Redimensiona a un ancho razonable para la tarjeta y genera un .webp liviano,
 * que es el formato que la landing busca primero.
 *
 * Uso:  php scripts/optimizar-fotos-demo.php
 */
$dir = __DIR__.'/../public/images/demo';
$anchoMax = 600;   // la tarjeta se ve a ~270px; 600 cubre pantallas retina
$calidad = 80;

if (! is_dir($dir)) {
    fwrite(STDERR, "No existe la carpeta: $dir\n");
    exit(1);
}

$archivos = glob($dir.'/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [];

if (! $archivos) {
    echo "No hay fotos .jpg/.png en public/images/demo/ todavía.\n";
    echo "Poné las fotos ahí (ver LEEME.txt) y volvé a correr este script.\n";
    exit(0);
}

foreach ($archivos as $ruta) {
    $antes = filesize($ruta);
    [$w, $h, $tipo] = getimagesize($ruta);

    $src = match ($tipo) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($ruta),
        IMAGETYPE_PNG => imagecreatefrompng($ruta),
        default => null,
    };

    if (! $src) {
        echo basename($ruta).": formato no soportado, se omite\n";
        continue;
    }

    $escala = min(1, $anchoMax / $w);
    $nw = (int) round($w * $escala);
    $nh = (int) round($h * $escala);

    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $ruta);
    imagewebp($dst, $webp, $calidad);

    imagedestroy($src);
    imagedestroy($dst);

    printf(
        "%-26s %dx%d %s KB  ->  %s  %dx%d %s KB (-%d%%)\n",
        basename($ruta), $w, $h, number_format($antes / 1024),
        basename($webp), $nw, $nh, number_format(filesize($webp) / 1024),
        round((1 - filesize($webp) / $antes) * 100)
    );
}

echo "\nListo. La landing va a usar los .webp automáticamente.\n";
