{{--
    Pantalla de elección cuando el comercio tiene portada procesada.

    Todo lo que se ve acá —el tono base, el velo, las superficies, el acento—
    sale de `tema_derivado`, que BrandThemeService calculó a partir de la foto
    que subió el comercio. Ni un color está elegido en este archivo.

    Si no hay portada, PublicMenuController sirve `menu.choose` (el diseño
    anterior), así que ningún comercio se rompe por no tener foto.
--}}
@php
    $tema = $comercio->tema_derivado;
    $velo = $tema['velo'];
    $sup = $tema['superficies'];
    $acento = $comercio->colorAcento();

    $seoDesc = $comercio->descripcion
        ? \Illuminate\Support\Str::limit(strip_tags($comercio->descripcion), 155)
        : 'Mirá el menú de '.$comercio->nombre.' desde tu celular: platos, precios y promociones actualizadas. Consultá al asistente y armá tu pedido.';
    $seoImg = asset('storage/'.$comercio->portada_hero_url);

    $schemaJson = json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $comercio->nombre,
        'url' => route('menu.public', $comercio->slug),
        'hasMenu' => route('menu.classic', $comercio->slug),
        'image' => $seoImg,
        'description' => $seoDesc,
        'telephone' => $comercio->telefono ?? null,
        'address' => $comercio->direccion ?? null,
    ], fn ($v) => ! is_null($v) && $v !== ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="{{ $tema['base'] }}">

    <title>Menú de {{ $comercio->nombre }} — carta digital online</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ route('menu.public', $comercio->slug) }}">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $comercio->nombre }}">
    <meta property="og:title" content="Menú de {{ $comercio->nombre }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ route('menu.public', $comercio->slug) }}">
    <meta property="og:image" content="{{ $seoImg }}">
    <meta property="og:locale" content="es_AR">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">{!! $schemaJson !!}</script>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/servime_ia.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        :root {
            --base:    {{ $tema['base'] }};
            --cream:   #F2E7D5;
            --cream-2: #E7DAC2;
            --acento:  {{ $acento }};
            --acento-dim: color-mix(in srgb, var(--acento) 26%, transparent);
            --muted:   rgba(242,231,213,.62);
            --surface: {{ $sup['fondo'] }};
            --surface-hi: {{ $sup['hover'] }};
            --borde:   {{ $sup['borde'] }};
            --borde-top: {{ $sup['borde_arriba'] }};
        }

        body {
            font-family: 'Jost', -apple-system, sans-serif;
            background: var(--base);
            color: var(--cream);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Fondo: la portada desenfocada, bajo el velo derivado de su propio
           promedio. Un solo tono en tres pasos. */
        .fondo {
            position: fixed; inset: 0; z-index: 0;
            background: url('{{ asset('storage/'.$comercio->portada_fondo_url) }}') center/cover no-repeat;
        }
        .fondo::after {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(88% 52% at 50% 26%, {{ $velo['centro'] }} 0%, {{ $velo['halo'] }} 100%),
                linear-gradient(180deg, {{ $velo['arriba'] }} 0%, {{ $velo['medio'] }} 45%, {{ $velo['abajo'] }} 100%);
        }

        .page {
            position: relative; z-index: 1; min-height: 100%;
            padding: 12px 14px 20px;
            display: flex; flex-direction: column; align-items: center;
        }
        .wrap { width: 100%; max-width: 404px; }

        /* ── Animación ── */
        @keyframes rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        @keyframes fade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes drift { from { transform: scale(1.02); } to { transform: scale(1.10) translate3d(-1.5%,-2%,0); } }
        @keyframes sheen { 0% { transform: translateX(-120%); } 55%, 100% { transform: translateX(220%); } }
        @keyframes glow { 0%,100% { box-shadow: 0 0 0 0 rgba(126,196,138,.5); } 50% { box-shadow: 0 0 0 5px rgba(126,196,138,0); } }
        @keyframes nudge { 0%,88%,100% { transform: translateX(0); } 94% { transform: translateX(3px); } }

        .an { animation: rise .62s cubic-bezier(.22,1,.36,1) both; }
        .d1 { animation-delay: .06s; } .d2 { animation-delay: .14s; } .d3 { animation-delay: .22s; }
        .d4 { animation-delay: .32s; } .d5 { animation-delay: .42s; }

        @media (prefers-reduced-motion: reduce) {
            .an, .hero img, .cta::after, .cta-go, .dot { animation: none !important; }
        }

        /* ── Hero ── */
        .hero {
            position: relative; width: 100%; height: 238px;
            border-radius: 24px; overflow: hidden;
            border: 1px solid var(--borde);
            box-shadow: 0 20px 46px rgba(0,0,0,.38);
            margin-bottom: 18px;
        }
        .hero img {
            width: 100%; height: 100%; object-fit: cover; display: block;
            /* El encuadre sale del punto focal que eligió el comercio. La
               imagen se guarda entera y se recuadra acá, así se adapta a cada
               ancho de pantalla en vez de quedar congelado en un recorte. */
            object-position: {{ $comercio->focoPortada() }};
            animation: drift 26s ease-in-out infinite alternate;
            will-change: transform;
        }
        /* Velo mínimo: la foto es lo que el comercio quiere mostrar y el velo
           anterior le comía el último 18% hasta disolverla en el fondo.
           Queda sólo lo que cumple una función:
             - arriba, un oscurecido corto para que se lea la píldora de estado
               sobre cualquier foto (una con cielo blanco la haría desaparecer);
             - abajo, una insinuación en el último 12% que no llega a tapar. */
        .hero::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: linear-gradient(180deg,
                rgba(0,0,0,.24) 0%, rgba(0,0,0,0) 30%,
                color-mix(in srgb, var(--base) 0%, transparent) 88%,
                color-mix(in srgb, var(--base) 72%, transparent) 100%);
        }
        .pill-estado {
            position: absolute; top: 13px; right: 13px; z-index: 2;
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 13px; border-radius: 999px;
            background: rgba(24,18,12,.62);
            border: 1px solid rgba(242,231,213,.16);
            backdrop-filter: blur(10px);
            font-size: 9px; letter-spacing: .16em; color: #EFE4D2;
        }
        /* Verde por semántica, no por acento: si tomara el color de marca, un
           comercio con acento rojo tendría un punto rojo diciendo "abierto". */
        .dot { width: 6px; height: 6px; border-radius: 50%; background: #7EC48A;
               animation: glow 2.4s ease-in-out infinite; }

        /* Cerrado: sin latido y en gris. El pulso verde sugiere actividad y
           acá justamente no la hay. */
        .pill-estado.cerrado { color: rgba(239,228,210,.72); }
        .pill-estado.cerrado .dot { background: #9A8F84; animation: none; }

        /* ── Identidad ── */
        .identidad { text-align: center; margin-bottom: 18px; }
        .eyebrow { font-size: 9.5px; letter-spacing: .34em; color: var(--acento); margin-bottom: 8px; }
        .titulo { font-family: 'Playfair Display', serif; font-weight: 500;
                  font-size: 38px; line-height: 1.03; color: #FBF5EA; }
        .regla { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 11px 0 8px; }
        .regla i { height: 1px; width: 44px; background: linear-gradient(90deg, transparent, var(--acento-dim)); }
        .regla i:last-child { background: linear-gradient(270deg, transparent, var(--acento-dim)); }
        .bajada { font-size: 11.5px; font-weight: 300; color: var(--muted); }

        /* ── CTA ── */
        .cta {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 13px;
            background: linear-gradient(140deg, var(--cream) 0%, var(--cream-2) 100%);
            border-radius: 18px; padding: 16px 15px; margin-bottom: 12px;
            text-decoration: none; color: inherit;
            box-shadow: 0 16px 40px rgba(0,0,0,.34);
            transition: transform .22s cubic-bezier(.22,1,.36,1);
        }
        .cta:active { transform: scale(.99); }
        .cta::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 38%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.55), transparent);
            animation: sheen 6.5s ease-in-out 1.6s infinite; pointer-events: none;
        }
        .cta-ico {
            flex-shrink: 0; width: 54px; height: 54px; border-radius: 50%;
            background: color-mix(in srgb, var(--acento) 28%, transparent);
            display: flex; align-items: center; justify-content: center;
        }
        .cta-txt { flex: 1; min-width: 0; }
        .cta-txt h2 { font-family: 'Playfair Display', serif; font-weight: 500;
                      font-size: 18.5px; line-height: 1.16; color: #241B12; margin-bottom: 4px; }
        .cta-txt p { font-size: 10.5px; line-height: 1.45; font-weight: 300; color: #6B5842; }
        .cta-go {
            flex-shrink: 0; width: 41px; height: 41px; border-radius: 50%;
            background: #1A130D; display: flex; align-items: center; justify-content: center;
            animation: nudge 4.5s ease-in-out infinite;
        }

        /* auto-fit: los chips los arma getChipsSugeridos() del menú real, así que
           la cantidad varía por comercio. La grilla no puede asumir tres. */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(96px, 1fr));
                gap: 9px; margin-bottom: 12px; }
        .card {
            background: var(--surface);
            border: 1px solid var(--borde); border-top-color: var(--borde-top);
            border-radius: 15px; padding: 15px 9px 13px; text-align: center;
            text-decoration: none; color: inherit;
            transition: border-color .22s ease, background .22s ease, transform .22s ease;
        }
        .card:active { transform: scale(.98); }
        .card-ico {
            width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 10px;
            background: color-mix(in srgb, var(--acento) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--acento) 22%, transparent);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; line-height: 1;
        }
        .card h3 { font-family: 'Playfair Display', serif; font-weight: 500;
                   font-size: 13px; line-height: 1.2; color: #F7EEE0; }

        /* ── Menú completo ── */
        .completo {
            display: flex; align-items: center; gap: 12px;
            background: var(--surface);
            border: 1px solid var(--borde); border-top-color: var(--borde-top);
            border-radius: 16px; padding: 10px 12px 10px 10px;
            text-decoration: none; color: inherit;
            transition: border-color .22s ease, transform .22s ease;
        }
        .completo:active { transform: scale(.99); }
        .completo img { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; flex-shrink: 0;
                        object-position: {{ $comercio->focoPortada() }}; }
        .completo div { flex: 1; min-width: 0; }
        .completo h3 { font-size: 13px; font-weight: 400; color: #F7EEE0; margin-bottom: 2px; }
        .completo p { font-size: 10.5px; font-weight: 300; color: rgba(242,231,213,.5); }
        .completo span {
            flex-shrink: 0; width: 37px; height: 37px; border-radius: 50%;
            background: var(--acento); display: flex; align-items: center; justify-content: center;
        }

        .pie { margin-top: 16px; text-align: center; font-size: 9px; letter-spacing: .13em;
               color: rgba(242,231,213,.3); animation: fade .8s ease .7s both; }
    </style>
</head>
<body>

<div class="fondo"></div>

<div class="page"><div class="wrap">

    <div class="hero an d1">
        <img src="{{ asset('storage/'.$comercio->portada_hero_url) }}"
             alt="{{ $comercio->nombre }}"
             fetchpriority="high" decoding="async">
        {{-- Sólo se muestra si el comercio cargó horarios. Si no sabemos, no
             decimos nada: antes esto era un "ABIERTO AHORA" fijo que mentía
             a cualquier hora. --}}
        @php $abierto = $comercio->estaAbierto(); @endphp
        @if($abierto !== null)
            <span class="pill-estado {{ $abierto ? '' : 'cerrado' }}">
                <span class="dot"></span>
                @if($abierto)
                    ABIERTO AHORA
                @elseif($proxima = $comercio->proximaApertura())
                    CERRADO · ABRE {{ mb_strtoupper($proxima) }}
                @else
                    CERRADO
                @endif
            </span>
        @endif
    </div>

    <div class="identidad an d2">
        <div class="eyebrow">MENÚ DIGITAL</div>
        <h1 class="titulo">{{ $comercio->nombre }}</h1>
        <div class="regla">
            <i></i>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="{{ $acento }}" stroke-width="1.1">
                <path d="M12 3l2 7 7 2-7 2-2 7-2-7-7-2 7-2 2-7z"/>
            </svg>
            <i></i>
        </div>
        @if($tagline = $comercio->taglinePublico())
            <p class="bajada">{{ $tagline }}</p>
        @endif
    </div>

    <a class="cta an d3" href="{{ route('menu.chat', $comercio) }}" data-track-choice="ia" data-track-desde="cta">
        <span class="cta-ico">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none"
                 stroke="color-mix(in srgb, {{ $acento }} 82%, #000)" stroke-width="1.2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2.8l1.9 5.3 5.3 1.9-5.3 1.9L12 17.2l-1.9-5.3L4.8 10l5.3-1.9L12 2.8z"/>
                <path d="M19 15.4l.7 1.9 1.9.7-1.9.7-.7 1.9-.7-1.9-1.9-.7 1.9-.7.7-1.9z"/>
            </svg>
        </span>
        <span class="cta-txt">
            <h2>Preguntame<br>lo que quieras</h2>
            <p>Nuestra IA te ayuda a elegir, recomienda platos y responde todas tus dudas.</p>
        </span>
        <span class="cta-go">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#EFE3CE"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </span>
    </a>

    {{-- data-track-choice: sin esto, quien entra al chat por un chip no cuenta
         como que eligió, y el embudo del panel da ~1% de elección.
         data-track-desde/label: para saber qué chip usan y cuál conviene cambiar. --}}
    @if(!empty($chips))
        <div class="grid an d4">
            @foreach($chips as $chip)
                {{-- `chip` lleva el id de la VERSIÓN del atajo: si el comercio le cambia la
                     frase o la pregunta, el id cambia y la analítica puede comparar cuál
                     funcionó mejor en vez de sumar todo junto. --}}
                <a class="card" data-track-choice="ia" data-track-desde="chip" data-track-label="{{ $chip['label'] }}"
                   data-track-chip="{{ $chip['id'] ?? '' }}"
                   href="{{ route('menu.chat', ['comercio' => $comercio, 'q' => $chip['q'], 'chip' => $chip['id'] ?? null]) }}">
                    <span class="card-ico">{{ $chip['emoji'] }}</span>
                    <h3>{{ $chip['label'] }}</h3>
                </a>
            @endforeach
        </div>
    @endif

    <a class="completo an d5" href="{{ route('menu.classic', $comercio) }}" data-track-choice="classic" data-track-desde="completo">
        <img src="{{ asset('storage/'.$comercio->portada_thumb_url) }}" alt="">
        <div>
            <h3>Quiero ver el menú completo</h3>
            <p>Explorá todas nuestras opciones.</p>
        </div>
        <span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1A130D"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </span>
    </a>

    <div class="pie">{{ mb_strtoupper($comercio->nombre) }} &nbsp;·&nbsp; POWERED BY SERVIME IA</div>

</div></div>

@include('menu.partials.tracking', ['trackView' => 'choose'])
</body>
</html>
