<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR de las mesas · {{ $comercio->nombre }}</title>

    {{--
        Página pensada para el papel, no para la pantalla: por eso no usa el layout del
        panel ni los tokens de color de la identidad. Lo que se imprime tiene que ser
        negro sobre blanco, que es lo que mejor escanea y lo que menos tinta gasta.

        Se resuelve con la impresión del navegador (Ctrl+P → Guardar como PDF) en vez de
        generar un PDF en el servidor: no hay librería de PDF en el proyecto, y así el
        comerciante puede elegir páginas y reimprimir una sola mesa cuando se le rompe
        un cartel.
    --}}
    <style>
        @page { size: A4; margin: 10mm; }

        * { box-sizing: border-box; }

        body {
            font-family: "Nunito Sans", Roboto, system-ui, sans-serif;
            margin: 0;
            background: #fff;
            color: #000;
        }

        .barra {
            padding: 16px 20px;
            background: #f3f3f3;
            border-bottom: 1px solid #ddd;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }
        .barra p { margin: 0; font-size: 14px; color: #444; }
        .barra button {
            font: inherit;
            font-weight: 700;
            padding: 10px 20px;
            border: 0;
            border-radius: 10px;
            background: #111;
            color: #fff;
            cursor: pointer;
        }

        .hoja {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            padding: 8mm;
        }

        .tarjeta {
            /* Las líneas punteadas son la guía de corte: sin ellas hay que adivinar
               dónde cortar y las tarjetas salen desparejas. */
            border: 1px dashed #bbb;
            padding: 6mm 3mm;
            text-align: center;
            /* Nueve por hoja A4 con márgenes de 10mm. */
            height: 88mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .comercio {
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.2;
            max-width: 100%;
            overflow: hidden;
        }

        .tarjeta img {
            width: 40mm;
            height: 40mm;
            display: block;
        }

        /* El número grande es para el mozo, que lo mira desde un metro de distancia.
           El chiquito que va dentro del QR es otra cosa: sirve para que el cliente
           confirme que escaneó la mesa en la que está sentado. */
        .numero {
            font-size: 26pt;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.02em;
        }
        .etiqueta { font-size: 9pt; color: #555; margin-top: 2px; }
        .instruccion { font-size: 8.5pt; color: #444; }

        @media print {
            .barra { display: none; }
            .hoja { padding: 0; }
            .tarjeta { border-color: #ddd; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <p>
            <strong>{{ $mesas->count() }}</strong> mesa(s) en servicio ·
            Imprimí esta hoja y cortá por las líneas punteadas.
            Para reimprimir una sola mesa, elegí su página en el diálogo de impresión.
        </p>
        <button type="button" onclick="window.print()">🖨️ Imprimir</button>
    </div>

    <div class="hoja">
        @foreach($mesas as $mesa)
            <div class="tarjeta">
                <div class="comercio">{{ $comercio->nombre }}</div>

                <img src="{{ $qrPorMesa[$mesa->id] }}" alt="Código QR de la mesa {{ $mesa->numero }}">

                <div class="instruccion">Escaneá para ver el menú</div>

                <div>
                    <div class="numero">MESA {{ $mesa->numero }}</div>
                    @if($mesa->etiqueta)
                        <div class="etiqueta">{{ $mesa->etiqueta }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
