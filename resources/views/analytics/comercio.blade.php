<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div class="flex items-center space-x-3">
                    @if($comercio->logo_url)
                        <img src="{{ asset('storage/' . $comercio->logo_url) }}"
                             alt="{{ $comercio->nombre }}"
                             class="w-10 h-10 rounded-full object-cover">
                    @endif
                    <div>
                        <h2 class="font-semibold text-xl text-text-primary leading-tight flex items-center gap-2">
                            <x-icono name="chart" class="w-5 h-5 flex-none opacity-60" />
                            Analytics · {{ $comercio->nombre }}
                        </h2>
                        <p class="text-sm text-text-secondary">Comportamiento de los últimos 30 días</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('analytics.dashboard') }}"
                       class="bg-secondary-dark hover:bg-accent-gray text-text-primary font-medium py-2 px-4 rounded-lg border border-accent-gray">
                        ← Dashboard
                    </a>
                    <a href="{{ route('analytics.export', $comercio) }}"
                       class="bg-accent-green hover:opacity-90 text-gray-900 font-semibold py-2 px-4 rounded-lg inline-flex items-center gap-2">
                        <x-icono name="download" class="w-4 h-4 flex-none" />
                        Exportar
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 rounded-lg p-4 mb-6 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Tarjetas de métrica. Ver components/stat.blade.php: el color aparece solo
                 cuando comunica algo, y el número va acompañado de su variación y su
                 tendencia para que se pueda interpretar. --}}
            @php
                $prev = $stats['previos_30'];
                $delta = $prev > 0 ? (int) round(($stats['ultimos_30'] - $prev) / $prev * 100) : null;
                $sinResp = $stats['sin_responder'];
                // Contra las consultas del mismo período que el contador, no contra el
                // histórico: si no, el porcentaje se diluía y no coincidía con el número.
                $pctSinResp = $stats['ultimos_30'] > 0
                    ? round($sinResp / $stats['ultimos_30'] * 100)
                    : 0;
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                {{-- El valor son los últimos 30 días, no el total histórico: todo el panel
                     está acotado a ese período (lo dice el subtítulo), así que un total de
                     siempre acá era inconsistente. El histórico va como nota. --}}
                <x-stat label="Consultas"
                        :value="number_format($stats['ultimos_30'], 0, ',', '.')"
                        :nota="$stats['total_conversations'] > $stats['ultimos_30']
                            ? 'últimos 30 días · '.number_format($stats['total_conversations'], 0, ',', '.').' desde el inicio'
                            : 'en los últimos 30 días'"
                        :delta="$delta"
                        :serie="$stats['serie']" />

                {{-- Personas, no mensajes: "54 consultas" no distingue 50 clientes de 3
                     indecisos. Va al lado de Consultas porque el par es el que se lee. --}}
                <x-stat label="Personas"
                        :value="$metrics['totalVisitantes']"
                        nota="usaron tu menú · últimos 30 días"
                        ayuda="Cuántos clientes distintos abrieron tu menú este mes, sin importar cuántas veces lo hayan mirado. «Consultas» cuenta mensajes; esto cuenta personas: 54 consultas pueden ser de 50 clientes o de 3 indecisos."
                        admin="Es un id en el localStorage del navegador, por comercio. Celular y compu son dos visitantes; si borra los datos del navegador, vuelve a contar como nuevo. En iOS, Safari lo borra a los 7 días sin uso." />

                {{-- El período va en cada nota y no solo en el encabezado: "respondió
                     todas" sin decir de cuándo no se puede interpretar. --}}
                <x-stat label="Sin responder"
                        :value="$sinResp"
                        :nota="($sinResp > 0 ? $pctSinResp.'% de las consultas' : 'respondió todas').' · últimos 30 días'"
                        :tono="$sinResp > 0 ? 'alerta' : 'neutro'"
                        ayuda="Las veces que un cliente escribió y el asistente no pudo contestarle, y le respondió un mensaje de disculpas. En cero está todo bien; si el número sube, escribinos: puede que se haya agotado la cuota de mensajes de tu plan."
                        admin="Cuenta las conversaciones con used_openai = false: cuota del plan agotada, API caída o key rota. El flag se apaga sólo si no llegó ningún token; un stream cortado a mitad cuenta como respondido." />

                <x-stat label="Hoy"
                        :value="$stats['conversations_today']"
                        nota="consultas del día" />
            </div>

            {{-- El tiempo de respuesta es infraestructura nuestra: el comercio no controla
                 el modelo ni la red, así que ocupaba un cuarto de la fila para señalarle en
                 ámbar un defecto que no puede arreglar. Queda como nota para el admin. --}}
            @if(auth()->user()->isSuperAdmin() && $stats['avg_response_time'])
                <p class="-mt-6 mb-8 text-xs text-text-secondary">
                    Tiempo de respuesta del asistente:
                    <b class="{{ $stats['avg_response_time'] > 6000 ? 'text-amber-500' : '' }}">{{ number_format($stats['avg_response_time'] / 1000, 1, ',', '.') }} s</b>
                    promedio · últimos 30 días · solo visible para admins
                </p>
            @endif

            <!-- Sugerencias accionables -->
            {{-- Solo super admin, a propósito y por ahora.
                 Las sugerencias se apoyan en lo que el asistente declaró que NO encontró, y
                 eso incluye sus propias fallas: llegó a decir "6 clientes preguntaron por
                 rabas, quizás valga la pena sumarlo" con las rabas en la carta a $26.000.
                 Un consejo equivocado es peor que ninguno, así que se prueban de este lado
                 antes de mostrárselas al comercio. Para abrirlas hay que separar primero la
                 demanda real de los faltantes que SÍ están en el menú (se puede: alcanza con
                 pasar cada faltante por ChatbotService::platosQueCoinciden()). --}}
            @if(count($suggestions) > 0 && auth()->user()->isSuperAdmin())
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 shadow-sm sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-amber-900 dark:text-amber-200 mb-4 flex items-center gap-2">
                        <x-icono name="idea" class="w-4 h-4 flex-none opacity-60" />
                        Sugerencias para tu negocio
                        <span class="text-xs font-normal opacity-70">· solo visible para admins</span>
                    </h3>
                    <ul class="space-y-2.5">
                        @foreach($suggestions as $s)
                            <li class="flex items-start gap-3 text-sm text-amber-900 dark:text-amber-100">
                                <x-icono :name="$s['icon']" class="w-4 h-4 flex-none mt-0.5 opacity-70" />
                                <span>{{ $s['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Intención de las consultas -->
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                            <x-icono name="target" class="w-4 h-4 flex-none opacity-60" />
                            ¿Para qué consultan?
                        </h3>
                        <p class="text-xs text-text-secondary">
                            Intención en {{ $intents['total'] }} mensajes (últimos 30 días)
                            @if($intents['ia'] > 0)
                                · <span class="text-indigo-600 dark:text-indigo-400">{{ $intents['ia'] }} clasificados con IA</span>
                            @endif
                        </p>
                    </div>
                    @if(auth()->user()->isSuperAdmin() || $comercio->tieneFeature('intencion_ia'))
                        <div class="text-right">
                            <form method="POST" action="{{ route('analytics.reclassify', $comercio) }}">
                                @csrf
                                <button type="submit"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                                    ✨ Reclasificar con IA
                                </button>
                            </form>
                            @if($ultimaClasificacion)
                                <p class="text-[11px] text-text-secondary mt-1">Última: {{ $ultimaClasificacion->created_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                @php $intMax = collect($intents['counts'])->max() ?: 1; @endphp
                @if($intents['total'] > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                        @foreach($intents['counts'] as $name => $count)
                            @if($count > 0)
                                <div>
                                    <div class="flex justify-between text-sm text-text-secondary mb-1">
                                        <span>{{ $name }}</span><b>{{ $count }}</b>
                                    </div>
                                    <div class="h-2 bg-accent-gray/40 rounded">
                                        <div class="h-2 {{ $name === 'Otras' ? 'bg-gray-400' : 'bg-indigo-500' }} rounded" style="width: {{ round($count / $intMax * 100) }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-text-secondary text-center py-4">Sin mensajes para analizar todavía.</p>
                @endif
            </div>

            <!-- Comportamiento: IA vs Clásico + Embudo -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                @php
                    $ia = $metrics['menuChoice']['ia']; $cl = $metrics['menuChoice']['classic'];
                    $totalChoice = $ia + $cl;
                    $iaPct = $totalChoice ? round($ia / $totalChoice * 100) : 0;
                    $clPct = $totalChoice ? 100 - $iaPct : 0;
                @endphp
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                        <x-icono name="split" class="w-4 h-4 flex-none opacity-60" />
                        ¿IA o menú clásico?
                        <x-ayuda titulo="¿IA o menú clásico?"
                                 texto="Qué elige la gente al llegar: preguntarle al asistente o ver el menú de toda la vida. Tené en cuenta que la pantalla no los ofrece parejos —el asistente tiene un botón grande y atajos, el menú completo un link más abajo—, así que es esperable que gane el asistente."
                                 admin="Una vez por visita y vale la PRIMERA elección, para que la suma cierre con el escalón «eligieron» del embudo. Quien probó los dos va aparte en menuChoice['ambos']." />
                    </h3>
                    <p class="text-text-secondary text-sm mb-4">Con qué entra cada visita.</p>
                    @if($totalChoice > 0)
                        <div class="flex h-5 rounded-full overflow-hidden mb-4">
                            <div class="bg-amber-500" style="width: {{ $iaPct }}%"></div>
                            <div class="bg-gray-400 dark:bg-gray-600" style="width: {{ $clPct }}%"></div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-text-secondary"><span class="inline-block w-3 h-3 rounded-sm bg-amber-500 mr-1"></span>IA · <b>{{ $ia }}</b> ({{ $iaPct }}%)</span>
                            <span class="text-text-secondary"><span class="inline-block w-3 h-3 rounded-sm bg-gray-400 dark:bg-gray-600 mr-1"></span>Clásico · <b>{{ $cl }}</b> ({{ $clPct }}%)</span>
                        </div>
                        @if(($metrics['menuChoice']['ambos'] ?? 0) > 0)
                            <p class="text-xs text-text-secondary mt-3">
                                {{ $metrics['menuChoice']['ambos'] }} {{ $metrics['menuChoice']['ambos'] === 1 ? 'visita probó' : 'visitas probaron' }} las dos formas.
                            </p>
                        @endif
                    @else
                        <p class="text-text-secondary text-center py-4">Sin datos de elección todavía.</p>
                    @endif
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="funnel" class="w-4 h-4 flex-none opacity-60" />
                        Embudo de interés
                        <x-ayuda titulo="Embudo de interés"
                                 texto="Hasta dónde llega la gente que abre tu menú, paso a paso: todos visitan, algunos pasan por la pantalla inicial, menos eligen cómo ver el menú, menos todavía tocan «Lo quiero» y unos pocos abren su pedido para revisarlo antes de pedírselo al mozo. Ese último escalón es el que más se parece a una venta. Donde veas la caída más grande, ahí está lo que conviene revisar. Cuenta personas, no toques."
                                 admin="Por visita (corte de 30 min de inactividad sobre el mismo session_id). «Revisaron su pedido» exige items > 0: el panel se puede abrir vacío y el escalón saldría mayor que el anterior." />
                    </h3>
                    @php
                        $fv = $metrics['funnel']['visitas'];
                        $fc = $metrics['funnel']['vieron_eleccion'] ?? 0;
                        $fe = $metrics['funnel']['eligieron']; $fi = $metrics['funnel']['interesados'];
                        $fp = $metrics['funnel']['armaron_pedido'] ?? 0;
                        // "Pasaron por la pantalla inicial" es un paso propio: quien entra
                        // directo al chat o al clásico no la ve, y sin ese paso el salto a
                        // "eligieron" parecía una caída enorme que no existía.
                        $pasos = [
                            ['Visitaron el menú', $fv, 'bg-blue-500'],
                            ['Pasaron por la pantalla inicial', $fc, 'bg-indigo-500'],
                            ['Eligieron una opción', $fe, 'bg-amber-500'],
                            ['Mostraron interés ("Lo quiero")', $fi, 'bg-green-500'],
                            ['Revisaron su pedido', $fp, 'bg-emerald-600'],
                        ];
                    @endphp
                    @if($fv > 0)
                        <div class="space-y-3">
                            @foreach($pasos as [$label, $val, $color])
                                <div>
                                    <div class="flex justify-between text-sm text-text-secondary mb-1">
                                        <span>{{ $label }}</span><b>{{ $val }}</b>
                                    </div>
                                    <div class="h-2 bg-accent-gray/40 rounded">
                                        <div class="h-2 {{ $color }} rounded" style="width: {{ $fv ? round($val / $fv * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin visitas registradas todavía.</p>
                    @endif
                </div>
            </div>

            {{-- Por dónde entran. El asistente tiene varias puertas (el botón grande y los
                 chips sugeridos) y el menú completo una sola: sin este desglose, todas se
                 mezclaban en el "IA vs clásico" de arriba y no se sabía si los chips servían.
                 Se cuentan clicks, no personas: la pregunta es qué puerta se usa. --}}
            @php
                $en = $metrics['entradas'];
                $enTotal = array_sum($en);
                // Se nombran con el texto que el cliente ve en la portada, no con el
                // nombre interno: el comercio reconoce sus botones por lo que dicen.
                $puertas = [
                    ['«Preguntame lo que quieras»', $en['cta'], 'bg-amber-500'],
                    ['Atajos sugeridos', $en['chip'], 'bg-indigo-500'],
                    ['«Quiero ver el menú completo»', $en['completo'], 'bg-gray-400 dark:bg-gray-600'],
                ];
            @endphp
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                            <x-icono name="split" class="w-4 h-4 flex-none opacity-60" />
                            ¿Por dónde entran?
                            <x-ayuda titulo="¿Por dónde entran?"
                                     texto="En la pantalla inicial hay tres caminos: el botón grande del asistente, los atajos sugeridos (que abren el asistente con la pregunta ya escrita) y el link al menú completo. Esto te dice cuál usan. Ojo al comparar: el asistente tiene dos puertas y el menú completo una sola, así que es esperable que junte más."
                                     admin="Acá se cuentan toques y no visitas, a propósito: la pregunta es qué botón se usa. Sale de data.desde del evento menu_choice; los eventos anteriores a ese campo no aparecen." />
                        </h3>
                        <p class="text-text-secondary text-sm mb-4">Qué toca el cliente en la pantalla inicial.</p>
                        @if($enTotal > 0)
                            @foreach($puertas as [$label, $val, $color])
                                <div class="mb-3">
                                    <div class="flex justify-between text-sm text-text-secondary mb-1">
                                        <span>{{ $label }}</span><b>{{ $val }} ({{ round($val / $enTotal * 100) }}%)</b>
                                    </div>
                                    <div class="h-2 bg-accent-gray/40 rounded">
                                        <div class="h-2 {{ $color }} rounded" style="width: {{ round($val / $enTotal * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-text-secondary text-center py-4">Todavía no hay toques registrados en la pantalla inicial.</p>
                        @endif
                    </div>

                    {{-- Un solo widget para los atajos: antes eran dos que parecían
                         contradecirse, porque uno contaba el TOQUE (tracking_events) y el
                         otro la CONSULTA que ese toque generó (chat_conversations). Ver
                         Support\Chip::rendimiento(). --}}
                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                            <x-icono name="target" class="w-4 h-4 flex-none opacity-60" />
                            Atajos
                            <x-ayuda titulo="Atajos"
                                     texto="Los botones que el cliente toca en vez de escribir, tanto en la pantalla de bienvenida como dentro del chat. «Preguntaron» es cuántos de esos toques llegaron al asistente: si tocan y no preguntan, se están yendo antes de que responda. «Con resultado» es cuántas veces terminó ofreciéndoles algún plato, y es el número que más importa: un atajo muy tocado y sin resultado es una promesa que tu menú no está pudiendo cumplir, y conviene cambiarle el texto o sacarlo. Podés editarlos desde la pantalla de tu menú."
                                     admin="Cruza tracking_events/menu_choice (el toque en la portada) con chat_conversations (la consulta). Agrupa por el id del chip, que sale de hash(label|q): editar cualquiera de los dos genera una fila nueva, a propósito, para poder comparar redacciones. Los clicks previos a los atajos configurables no tienen id y caen agrupados por texto, marcados como «previo». En el chat no hay evento de tracking: el toque ES la consulta." />
                        </h3>
                        <p class="text-text-secondary text-sm mb-4">El que nadie toca conviene cambiarlo. El que tocan y no da resultado, también.</p>
                        @if(count($chipStats) > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-text-secondary text-xs border-b border-accent-gray/40">
                                            <th class="text-left font-normal pb-2">Atajo</th>
                                            <th class="text-right font-normal pb-2 whitespace-nowrap">Toques</th>
                                            <th class="text-right font-normal pb-2 whitespace-nowrap">Preguntaron</th>
                                            <th class="text-right font-normal pb-2 whitespace-nowrap">Con resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($chipStats as $chip)
                                            <tr class="border-b border-accent-gray/20 last:border-0">
                                                <td class="py-2 pr-3">
                                                    <span class="text-text-primary">{{ $chip['label'] }}</span>
                                                    <span class="text-text-secondary/60 text-xs ml-1">
                                                        {{ $chip['superficie'] === 'anterior' ? 'previo' : $chip['superficie'] }}
                                                    </span>
                                                </td>
                                                <td class="py-2 text-right text-text-primary whitespace-nowrap">{{ $chip['clicks'] }}</td>
                                                <td class="py-2 text-right whitespace-nowrap {{ $chip['abandonos'] > 0 ? 'text-amber-500' : 'text-text-secondary' }}">
                                                    {{ $chip['preguntas'] }}
                                                    @if($chip['abandonos'] > 0)
                                                        <span class="text-xs opacity-70">(−{{ $chip['abandonos'] }})</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 text-right whitespace-nowrap {{ $chip['efectividad'] >= 60 ? 'text-green-500' : ($chip['efectividad'] > 0 ? 'text-text-secondary' : 'text-amber-500') }}">
                                                    {{ $chip['con_platos'] }} <span class="text-xs opacity-70">({{ $chip['efectividad'] }}%)</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">Todavía nadie tocó un atajo.</p>
                        @endif
                    </div>

                    {{-- Reseñas en Google. Se prende y se le cambia el texto desde la pantalla
                         del menú; acá se mira si funciona. --}}
                    @if($comercio->pideResena() || $resenaStats['impresiones'] > 0)
                        <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                            <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                                <x-icono name="heart" class="w-4 h-4 flex-none opacity-60" />
                                Reseñas en Google
                                <x-ayuda titulo="Reseñas en Google"
                                         texto="A quien usó el asistente y ya lleva un rato en la mesa le ofrecemos dejar su reseña. Aparece recién cuando vuelve a mirar el celular, así no lo interrumpe mientras come. Si muchos lo cierran, probá con otro texto. Si la mayoría ni lo mira, el tema no es qué dice sino cuándo aparece. Los textos los cambiás desde la pantalla de tu menú."
                                         admin="Eventos resena_view / resena_click / resena_dismiss, contados por sesión y no por evento. «Ni lo miraron» es la resta, topeada en cero: si se pierde una impresión (bloqueador, pestaña cerrada antes del beacon) los clicks pueden superarlas. Se muestra una vez por dispositivo y no vuelve por RESENA_DIAS_GRACIA días." />
                            </h3>
                            <p class="text-text-secondary text-sm mb-4">Una reseña nueva vale más que cualquier métrica de acá.</p>

                            @if($resenaStats['impresiones'] > 0)
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <div class="text-2xl font-bold text-text-primary tabular-nums">{{ $resenaStats['impresiones'] }}</div>
                                        <div class="text-xs text-text-secondary mt-0.5">Se les mostró</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-green-500 tabular-nums">{{ $resenaStats['clicks'] }}</div>
                                        <div class="text-xs text-text-secondary mt-0.5">Fueron a Google</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-amber-500 tabular-nums">{{ $resenaStats['cierres'] }}</div>
                                        <div class="text-xs text-text-secondary mt-0.5">Lo cerraron</div>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-text-secondary tabular-nums">{{ $resenaStats['ignorados'] }}</div>
                                        <div class="text-xs text-text-secondary mt-0.5">Ni lo miraron</div>
                                    </div>
                                </div>

                                @php $tasa = (int) round($resenaStats['clicks'] * 100 / max($resenaStats['impresiones'], 1)); @endphp
                                <div class="mt-4 pt-4 border-t border-accent-gray/40">
                                    <p class="text-text-secondary text-sm">
                                        De cada 100 que lo vieron, <span class="text-text-primary font-semibold">{{ $tasa }}</span> fueron a dejar la reseña.
                                        @if($resenaStats['cierres'] > $resenaStats['clicks'])
                                            Lo cierran más de lo que lo tocan: probá cambiando el texto.
                                        @elseif($resenaStats['ignorados'] > $resenaStats['impresiones'] / 2)
                                            La mayoría ni lo registra: el problema es cuándo aparece, no qué dice.
                                        @endif
                                    </p>
                                </div>
                            @else
                                <p class="text-text-secondary text-center py-4">Todavía no se le mostró a nadie.</p>
                            @endif
                        </div>
                    @endif
                </div>

            <!-- Dispositivo · Visitantes · Micrófono -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3 flex items-center gap-2">
                        <x-icono name="phone" class="w-4 h-4 flex-none opacity-60" />
                        Dispositivos
                        <x-ayuda titulo="Dispositivos"
                                 texto="Desde qué aparato abren tu menú. Como el QR está en la mesa, lo esperable es que sea casi todo móvil: si ves mucho escritorio, están llegando por un link compartido o desde tus redes, y no escaneando el código en el local. Eso cambia para qué te sirve el menú."
                                 admin="Se clasifica por user_agent y «escritorio» es el cajón de descarte: lo que no dice mobi/android/iphone/ipad cae ahí, incluidos los headless y los bots de previsualización de links." />
                    </h3>
                    @php $dv = $metrics['devices']; $dvTotal = array_sum($dv) ?: 1; @endphp
                    @foreach(['movil' => 'Móvil', 'desktop' => 'Escritorio', 'tablet' => 'Tablet'] as $k => $label)
                        <div class="mb-2">
                            <div class="flex justify-between text-sm text-text-secondary">
                                <span>{{ $label }}</span><b>{{ round($dv[$k] / $dvTotal * 100) }}%</b>
                            </div>
                            <div class="h-1.5 bg-accent-gray/40 rounded mt-1">
                                <div class="h-1.5 bg-teal-500 rounded" style="width: {{ round($dv[$k] / $dvTotal * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3 flex items-center gap-2">
                        <x-icono name="return" class="w-4 h-4 flex-none opacity-60" />
                        Visitantes que vuelven
                        <x-ayuda titulo="Visitantes que vuelven"
                                 texto="Cuántos abrieron tu menú en dos momentos distintos, separados por más de media hora. No hace falta que sea otro día: quien mira la carta al llegar y la vuelve a abrir para el postre ya cuenta acá, así que no lo leas como «clientes habitués»."
                                 admin="Visitante = id de localStorage; visita = corte por 30 min de inactividad; recurrente = 2 o más visitas. En iOS queda corto: Safari borra la marca a los 7 días sin uso." />
                    </h3>
                    @php $vs = $metrics['visitors']; $vt = array_sum($vs) ?: 1; @endphp
                    <p class="text-4xl font-bold text-text-primary">{{ round($vs['recurrentes'] / $vt * 100) }}%</p>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $vs['recurrentes'] }} de {{ $metrics['totalVisitantes'] }} visitantes volvieron
                    </p>
                    <p class="text-xs text-text-secondary mt-1">{{ $vs['nuevos'] }} nuevos</p>
                </div>

                {{-- Acá iba "Tiempo promedio en el menú". Se sacó porque el número no
                     significaba nada: la duración es el rato entre el primer evento y el
                     último, y no hay evento al salir, así que la última pantalla nunca se
                     cuenta y la visita de un solo evento mide 0 s. Medido en local: mediana
                     6 s contra un promedio de 7 minutos — el promedio no le pasaba a casi
                     ninguna visita real. Vuelve cuando haya un evento de salida
                     (pagehide/visibilitychange con sendBeacon) y se muestre la mediana. --}}

                {{-- El dictado por voz termina en el input y se manda como texto, así que sin
                     su propio evento no había forma de saber si alguien usa el micrófono. --}}
                @php $voz = $metrics['vozVisitantes'] ?? 0; $vtot = $metrics['totalVisitantes'] ?: 1; @endphp
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3 flex items-center gap-2">
                        <x-icono name="mic" class="w-4 h-4 flex-none opacity-60" />
                        Hablaron con el asistente
                        <x-ayuda titulo="Hablaron con el asistente"
                                 texto="Cuántas personas usaron el micrófono para dictar en vez de escribir. En un local ruidoso o con gente alrededor puede que nadie se anime, y si no la usa nadie, esa función no vale la pena."
                                 admin="Evento mic_start, por visita. Es un piso, no un porcentaje exacto: el reconocimiento de voz no existe en todos los navegadores y a esos clientes ni siquiera les aparece el botón." />
                    </h3>
                    <p class="text-4xl font-bold text-text-primary">{{ round($voz / $vtot * 100) }}%</p>
                    <p class="text-sm text-text-secondary mt-1">
                        {{ $voz }} de {{ $metrics['totalVisitantes'] }} {{ $metrics['totalVisitantes'] === 1 ? 'persona usó' : 'personas usaron' }} el micrófono
                    </p>
                </div>
            </div>

            <!-- Promos y platos top -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="flame" class="w-4 h-4 flex-none opacity-60" />
                        Promos más pedidas
                        <x-ayuda titulo="Promos más pedidas"
                                 texto="Cuál de las promociones que cargaste engancha más: cuenta cada vez que alguien tocó «Lo quiero» en una de ellas, una por una. Abajo ves cuántas veces abrieron la sección: si la abren mucho y después nadie toca «Lo quiero», el problema no es que no las vean, es la oferta o el precio."
                                 admin="Cruza los dish_want con getPromocionesDelMenu(), sin tracking propio. La categoría se detecta por nombre EXACTO «Promociones del Día»: si el comercio la llama distinto, se queda sin chip, sin atajo y sin este widget." />
                    </h3>
                    <p class="text-text-secondary text-sm mb-4">Las promociones que cargaste, por interés real.</p>
                    @php $promoMax = $metrics['topPromos']->max() ?: 1; @endphp
                    @forelse($metrics['topPromos'] as $promo => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $promo }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-red-500 rounded" style="width: {{ round($count / $promoMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Todavía nadie pidió una promo.</p>
                    @endforelse
                    @if(($metrics['aperturasPromos'] ?? 0) > 0)
                        <p class="text-xs text-text-secondary mt-3 pt-3 border-t border-accent-gray/40">
                            {{ $metrics['aperturasPromos'] }} {{ $metrics['aperturasPromos'] === 1 ? 'vez abrieron' : 'veces abrieron' }} la sección de promos.
                        </p>
                    @endif
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="heart" class="w-4 h-4 flex-none opacity-60" />
                        Platos más deseados
                        <x-ayuda titulo="Platos más deseados"
                                 texto="Los platos que más veces sumaron al pedido con el botón «Lo quiero». Es lo más cerca de una venta que ves acá, pero no es una venta: el cliente puede sumarlo y después arrepentirse. Incluye tus promociones, que además tienen su propio ranking más arriba — son los mismos toques mirados de dos maneras, no los sumes."
                                 admin="Evento dish_want, por toque: dos milanesas del mismo cliente son dos." />
                    </h3>
                    @php $dishMax = $metrics['topDishes']->max() ?: 1; @endphp
                    @forelse($metrics['topDishes'] as $dish => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $dish }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-green-500 rounded" style="width: {{ round($count / $dishMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Nadie tocó "Lo quiero" aún.</p>
                    @endforelse
                    @if(($dishesFuera ?? 0) > 0)
                        <p class="text-xs text-text-secondary mt-3 pt-3 border-t border-accent-gray/40">
                            Y {{ $dishesFuera }} {{ $dishesFuera === 1 ? 'plato más pedido' : 'platos más pedidos' }} menos veces.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Cómo exploran la carta desde el chat: por categoría o filtrando por etiqueta.
                 Van después de "platos deseados" porque son el paso anterior: curiosear una
                 parte del menú, no querer un plato puntual. --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                        <x-icono name="grid" class="w-4 h-4 flex-none opacity-60" />
                        Qué categorías miran
                        <x-ayuda titulo="Qué categorías miran"
                                 texto="En el chat, el cliente puede abrir la lista de categorías de tu carta y tocar una. Esto es el ranking. No confundir con «Platos más deseados»: allá ya decidió que quiere un plato, acá recién está curioseando. Sirve para saber qué despierta interés y qué conviene mostrar primero."
                                 admin="Evento categoria_tap, por toque (volver dos veces a postres suma dos) y con el nombre ORIGINAL del menú, no el traducido." />
                    </h3>
                    <p class="text-text-secondary text-sm mb-4">Las secciones de tu carta que más abren en el chat.</p>
                    @php $catMax = $metrics['topCategorias']->max() ?: 1; @endphp
                    @forelse($metrics['topCategorias'] as $cat => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $cat }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-purple-500 rounded" style="width: {{ round($count / $catMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Todavía nadie abrió el listado de categorías.</p>
                    @endforelse
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                        <x-icono name="check" class="w-4 h-4 flex-none opacity-60" />
                        Qué etiquetas filtran
                        <x-ayuda titulo="Qué etiquetas filtran"
                                 texto="En el chat el cliente puede filtrar por etiqueta: sin TACC, vegano, sin lactosa, lo que tengas cargado. Es lo único del panel que te dice qué necesidades alimentarias tienen tus clientes: si muchos filtran por algo que casi no tenés, ahí hay platos para sumar. Ojo que si un plato apto no está etiquetado, el filtro no lo encuentra."
                                 admin="Evento tag_tap. Los nombres se normalizan al agrupar: SIN_TACC, sin-tacc y sin tacc son la misma fila." />
                    </h3>
                    <p class="text-text-secondary text-sm mb-4">Las restricciones o preferencias que buscan tus clientes.</p>
                    @php $tagMax = $metrics['topTags']->max() ?: 1; @endphp
                    @forelse($metrics['topTags'] as $tag => $count)
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-text-secondary mb-1">
                                <span class="truncate">{{ $tag }}</span><b>{{ $count }}</b>
                            </div>
                            <div class="h-2 bg-accent-gray/40 rounded">
                                <div class="h-2 bg-teal-500 rounded" style="width: {{ round($count / $tagMax * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-text-secondary text-center py-4">Todavía nadie filtró por etiquetas.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top palabras + Heatmap -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="search" class="w-4 h-4 flex-none opacity-60" />
                        Palabras más buscadas
                    </h3>
                    @php $wordMax = $topWords->max() ?: 1; @endphp
                    @if($topWords->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($topWords as $word => $count)
                                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-1"
                                      style="font-size: {{ 0.75 + ($count / $wordMax) * 0.7 }}rem">
                                    {{ $word }} <span class="ml-1 text-xs opacity-70">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin mensajes para analizar todavía.</p>
                    @endif
                </div>

                {{-- Demanda insatisfecha: lo que los clientes pidieron y el menú no tiene.
                     Lo declara el asistente en cada respuesta, ya normalizado a 1-3 palabras,
                     así "tenés sushi?", "hay sushi?" y "tienen sushi" cuentan juntas.
                     Cada línea es una decisión concreta, a diferencia de la nube de palabras
                     de al lado, que describe la demanda pero no señala huecos. --}}
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                        <x-icono name="alert" class="w-4 h-4 flex-none opacity-60" />
                        Te lo pidieron y no lo tenés
                    </h3>
                    <p class="text-text-secondary text-sm mb-4">Lo que los clientes buscaron y no está en tu menú. Puede ser algo para sumar a la carta, o algo que sí tenés pero está mal cargado (sin descripción o sin etiqueta).</p>
                    @if(count($demandaInsatisfecha) > 0)
                        <ul class="space-y-2">
                            @foreach($demandaInsatisfecha as $item)
                                <li class="flex items-center justify-between gap-3 bg-accent-gray/20 rounded-xl px-3 py-2">
                                    <span class="text-text-primary text-sm">{{ $item['consulta'] }}</span>
                                    <span class="text-text-secondary text-xs whitespace-nowrap">{{ $item['veces'] }}{{ $item['veces'] === 1 ? ' vez' : ' veces' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-text-secondary text-center py-4">Todo lo que preguntaron encontró respuesta en tu menú. 👌</p>
                    @endif

                    {{-- Los faltantes que en realidad SÍ están en la carta: no son demanda,
                         es el asistente que no los encontró. Al comercio no le sirven (le
                         dirían que sume algo que ya vende) pero a nosotros nos avisan que
                         el chat está fallando, que es algo que hoy no se detecta de otra forma. --}}
                    @if(!empty($fallasDelAsistente))
                        <div class="mt-4 pt-4 border-t border-accent-gray/40">
                            <p class="text-xs text-amber-500 mb-2">
                                ⚠ El asistente no encontró esto, y SÍ está en el menú · solo visible para admins
                            </p>
                            <p class="text-xs text-text-secondary">
                                @foreach($fallasDelAsistente as $f)
                                    <span class="inline-block mr-3">{{ $f['consulta'] }} <b>×{{ $f['veces'] }}</b></span>
                                @endforeach
                            </p>
                        </div>
                    @endif
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="grid" class="w-4 h-4 flex-none opacity-60" />
                        Actividad por hora y día
                    </h3>
                    @php $dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']; $hmMax = $metrics['heatmapMax']; @endphp
                    @if($hmMax > 0)
                        <div class="space-y-1">
                            @for($d = 0; $d < 7; $d++)
                                <div class="flex items-center gap-1">
                                    <span class="w-8 text-xs text-text-secondary flex-shrink-0">{{ $dias[$d] }}</span>
                                    @for($h = 0; $h < 24; $h++)
                                        @php $c = $metrics['heatmap'][$d][$h]; $r = $c > 0 ? max(0.15, $c / $hmMax) : 0; @endphp
                                        <div class="flex-1 h-4 rounded-sm" title="{{ $dias[$d] }} {{ $h }}:00 · {{ $c }}"
                                             style="background: {{ $c > 0 ? 'rgba(245,158,11,'.$r.')' : 'rgba(120,120,120,0.12)' }}"></div>
                                    @endfor
                                </div>
                            @endfor
                            <div class="flex justify-between text-[10px] text-text-secondary pl-9 pt-1">
                                <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
                            </div>
                        </div>
                    @else
                        <p class="text-text-secondary text-center py-4">Sin actividad para el heatmap todavía.</p>
                    @endif
                </div>
            </div>

            <!-- Preguntas Más Frecuentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                            <x-icono name="help" class="w-4 h-4 flex-none opacity-60" />
                            Preguntas más frecuentes
                            <x-ayuda titulo="Preguntas más frecuentes"
                                     texto="Las consultas que se repitieron dos veces o más. Lo que alguien preguntó una sola vez no aparece acá, porque no es un patrón: para leer todo está el historial de conversaciones, al final de la página. El aviso en ámbar significa que en la mayoría de esas veces el asistente no encontró nada del menú para ofrecer."
                                     admin="Agrupa por frase normalizada, no por tema: «tienen postres» y «¿Tienen postres?» cuentan juntas, «listame postres» va aparte. Descarta saludos. El ámbar sale si platos_sugeridos = 0 en más de la mitad." />
                        </h3>
                        <p class="text-text-secondary text-sm mb-4">Lo que los clientes preguntaron más de una vez, y si el asistente pudo recomendarles algo del menú.</p>
                        @if($frequentQuestions->count() > 0)
                            @php $qMax = $frequentQuestions->max('frequency') ?: 1; @endphp
                            <div class="space-y-2.5">
                                @foreach($frequentQuestions as $question)
                                    <div>
                                        <div class="flex items-baseline justify-between gap-3 mb-1">
                                            <div class="min-w-0 flex items-center gap-2">
                                                <span class="text-sm text-text-primary truncate">{{ $question->user_message }}</span>
                                                {{-- Idiomas en que llegó la misma pregunta: explica por qué
                                                     "quiero una pizza" y "I want a pizza" cuentan separadas. --}}
                                                @foreach($question->idiomas as $idioma)
                                                    @if($idioma !== 'es')
                                                        <span class="flex-none text-[10px] font-semibold uppercase tracking-wide text-text-secondary border border-accent-gray rounded px-1">{{ $idioma }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <span class="flex-none text-xs text-text-secondary">{{ $question->frequency }}{{ $question->frequency > 1 ? ' veces' : ' vez' }}</span>
                                        </div>
                                        {{-- La barra hace comparable de un vistazo lo que antes era una
                                             lista de números sueltos. Verde = el asistente resolvió;
                                             ámbar = no encontró nada del menú para ofrecer. --}}
                                        <div class="h-1.5 bg-accent-gray/40 rounded overflow-hidden">
                                            <div class="h-1.5 rounded {{ $question->resuelta === false ? 'bg-amber-500' : 'bg-blue-500' }}"
                                                 style="width: {{ round($question->frequency / $qMax * 100) }}%"></div>
                                        </div>
                                        @if($question->resuelta === false)
                                            <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">El asistente no encontró nada del menú para ofrecer</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">
                                Todavía no hay preguntas que se repitan.
                            </p>
                        @endif

                        {{-- Sin esto el comercio escribe algo en el chat, no lo ve acá y cree
                             que el panel se lo comió. No se lo comió: no se repitió. --}}
                        @if($preguntasUnicas > 0)
                            <p class="text-xs text-text-secondary mt-4 pt-3 border-t border-accent-gray/40">
                                Hay {{ $preguntasUnicas }} {{ $preguntasUnicas === 1 ? 'consulta más que se hizo' : 'consultas más que se hicieron' }} una sola vez.
                                <a href="#historial" class="text-accent-blue hover:underline">Verlas en el historial ↓</a>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Estadísticas Diarias -->
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                            <x-icono name="chart" class="w-4 h-4 flex-none opacity-60" />
                            Actividad de los Últimos 7 Días
                        </h3>
                        @if($dailyStats->count() > 0)
                            <div class="space-y-3">
                                @foreach($dailyStats as $day)
                                    <div class="flex items-center justify-between py-2 border-b border-accent-gray/50 last:border-b-0">
                                        {{-- Este widget se había quedado atrás: mostraba "101/101
                                             con IA" (el número que sacamos de las tarjetas por no
                                             avisar nada) y el tiempo de respuesta en milisegundos,
                                             que es infraestructura nuestra. Queda lo único que le
                                             sirve al comercio —cuánta gente le consultó ese día— y
                                             el aviso cuando algo no se pudo responder. --}}
                                        @php
                                            $sinResponder = $day->total - $day->openai_count;
                                            // Los días a mano: config('app.locale') es 'en' (el
                                            // español se aplica sólo en las rutas del menú público,
                                            // por middleware), así que translatedFormat() escribiría
                                            // "Tue 11/08" en un panel que está todo en español.
                                            $dia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                                            $fecha = \Carbon\Carbon::parse($day->date);
                                        @endphp
                                        <div>
                                            <p class="text-sm font-medium text-text-primary">
                                                {{ $dia[$fecha->dayOfWeek] }} {{ $fecha->format('d/m') }}
                                            </p>
                                            @if($sinResponder > 0)
                                                <p class="text-xs text-amber-500">
                                                    {{ $sinResponder }} sin responder
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-text-primary">
                                                {{ $day->total }} {{ $day->total === 1 ? 'consulta' : 'consultas' }}
                                            </p>
                                            @if(auth()->user()->isSuperAdmin() && $day->avg_response_time)
                                                <p class="text-xs text-text-secondary">
                                                    {{ number_format($day->avg_response_time / 1000, 1, ',', '.') }} s · solo admins
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-text-secondary text-center py-4">
                                No hay actividad registrada en los últimos 7 días
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Historial de conversaciones.

                 Antes cada fila mostraba fecha + IP en blanco negrita y el mensaje del
                 cliente en gris chico abajo: la jerarquía al revés, porque lo único que
                 se quiere leer es qué preguntaron. Además repetía "anónimo" y un badge
                 azul "1 msj" en las 25 filas, sin aportar nada.

                 Ahora manda el mensaje, y los badges aparecen SOLO cuando dicen algo:
                 cantidad si hay más de uno, idioma si no es español, y un aviso ámbar en
                 los hilos donde el asistente no encontró nada del menú. --}}
            <div class="bg-secondary-dark border border-accent-gray rounded-2xl overflow-hidden">
                <div class="p-6 historial" id="historial">
                    @php
                        $etiquetasPeriodo = [7 => '7 días', 30 => '30 días', 90 => '3 meses', 365 => '1 año', 0 => 'Todo'];
                        // Frase aparte de la etiqueta del pill: 'últimos 1 año' no se dice.
                        $fraseP = [7 => 'últimos 7 días', 30 => 'últimos 30 días', 90 => 'últimos 3 meses', 365 => 'último año', 0 => 'todo el historial'];
                        // Cada pill conserva el resto de los filtros: cambiar el periodo no
                        // deberia borrar la busqueda ni la cantidad elegida.
                        $conFiltro = fn (array $extra) => request()->fullUrlWithQuery($extra);
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h3 class="text-lg font-medium text-text-primary flex items-center gap-2">
                            <x-icono name="messages" class="w-4 h-4 flex-none opacity-60" />
                            Historial de conversaciones
                        </h3>

                        {{-- Busqueda del lado del servidor. Es un GET para que el resultado
                             quede en la URL y se pueda compartir o guardar. --}}
                        <form method="GET" action="{{ route('analytics.comercio', $comercio) }}"
                              class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="periodo" value="{{ $periodo }}">
                            <input type="hidden" name="cantidad" value="{{ $cantidad }}">
                            <div class="relative">
                                <x-icono name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary pointer-events-none" />
                                <input type="search" name="q" value="{{ $q }}"
                                       placeholder="Buscar..."
                                       class="w-52 max-w-full bg-primary-dark border border-accent-gray rounded-full pl-9 pr-3 py-1.5 text-sm text-text-primary placeholder-text-secondary focus:outline-none focus:border-accent-blue">
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs cursor-pointer select-none">
                                <input type="checkbox" name="sin_resolver" value="1" class="peer sr-only"
                                       @checked($soloSinResolver) onchange="this.form.submit()">
                                <span class="px-2.5 py-1.5 rounded-full border border-accent-gray text-text-secondary peer-checked:border-amber-500 peer-checked:text-amber-500 transition-colors">
                                    Solo sin resolver
                                </span>
                            </label>
                        </form>
                    </div>

                    {{-- Periodo y cantidad como enlaces: sin JS, y cada estado queda en la URL.
                         Antes la lista mostraba 25 hilos de 79 sin decirlo, y para ver algo mas
                         viejo habia que adivinar una palabra para buscar. --}}
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-text-secondary mr-1">Período</span>
                            @foreach($etiquetasPeriodo as $dias => $etiqueta)
                                <a href="{{ $conFiltro(['periodo' => $dias]) }}"
                                   class="px-2.5 py-1 rounded-full text-xs border transition-colors
                                          {{ $periodo === $dias
                                              ? 'border-accent-blue text-accent-blue'
                                              : 'border-accent-gray text-text-secondary hover:text-text-primary' }}">{{ $etiqueta }}</a>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-text-secondary mr-1">Mostrar</span>
                            @foreach(\App\Http\Controllers\AnalyticsController::CANTIDADES as $n)
                                <a href="{{ $conFiltro(['cantidad' => $n]) }}"
                                   class="px-2.5 py-1 rounded-full text-xs border transition-colors
                                          {{ $cantidad === $n
                                              ? 'border-accent-blue text-accent-blue'
                                              : 'border-accent-gray text-text-secondary hover:text-text-primary' }}">{{ $n }}</a>
                            @endforeach
                        </div>

                        @if($q !== '' || $soloSinResolver || $periodo !== 30 || $cantidad !== 25)
                            <a href="{{ route('analytics.comercio', $comercio) }}"
                               class="text-xs text-text-secondary hover:text-text-primary underline">Restablecer</a>
                        @endif
                    </div>

                    @if($threads->count() > 0)
                        <p class="text-xs text-text-secondary mb-4">
                            {{ $threadsTotal }} {{ $threadsTotal === 1 ? 'hilo' : 'hilos' }}
                            @if($q !== '') que coinciden con &ldquo;{{ $q }}&rdquo; @endif
                            @if($soloSinResolver) sin resolver @endif
                            &middot; {{ $fraseP[$periodo] }}
                            @if($threadsTotal > $threads->count())
                                &middot; se muestran {{ $threads->count() }}
                            @endif
                        </p>

                        <div class="space-y-2">
                            @foreach($threads as $thread)
                                <details class="group rounded-xl border border-accent-gray/50 hover:border-accent-gray transition-colors">
                                    <summary class="flex items-start gap-3 cursor-pointer px-4 py-3 select-none list-none">
                                        <x-icono name="chevron" class="w-4 h-4 flex-none mt-0.5 text-text-secondary transition-transform group-open:rotate-90" />

                                        <div class="min-w-0 flex-1">
                                            {{-- El mensaje es lo que se lee: va primero y en el color principal --}}
                                            <p class="text-sm text-text-primary truncate">{{ $thread['titulo'] ?: '(sin mensaje)' }}</p>
                                            @if($thread['respuesta'])
                                                <p class="text-xs text-text-secondary/80 truncate mt-0.5">{{ \Illuminate\Support\Str::limit($thread['respuesta'], 110) }}</p>
                                            @endif
                                            {{-- La fecha del ÚLTIMO mensaje, que es por la que se
                                                 ordena la lista. Antes se mostraba la del primero y
                                                 parecía desordenada: un hilo largo que empezó el 09
                                                 y siguió el 11 quedaba arriba de todo mostrando "09".
                                                 Si la charla abarcó varios días se aclara el rango. --}}
                                            <p class="text-xs text-text-secondary mt-1">
                                                {{ $thread['fin']->format('d/m H:i') }}@if($thread['total'] > 1) &middot; {{ $thread['total'] }} mensajes @endif
                                                @if(!$thread['inicio']->isSameDay($thread['fin']))
                                                    &middot; <span class="opacity-70">empezó el {{ $thread['inicio']->format('d/m') }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="flex-none flex items-center gap-1.5">
                                            @foreach($thread['idiomas'] as $idioma)
                                                <span class="text-[10px] font-semibold uppercase tracking-wide text-text-secondary border border-accent-gray rounded px-1 py-0.5">{{ $idioma }}</span>
                                            @endforeach
                                            @if($thread['sin_resolver'])
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-500 border border-amber-500/40 rounded-full px-2 py-0.5">
                                                    <x-icono name="alert" class="w-3 h-3 flex-none" />
                                                    {{ $thread['falta'] ? implode(', ', array_slice($thread['falta'], 0, 2)) : 'sin resultado' }}
                                                </span>
                                            @endif
                                        </div>
                                    </summary>

                                    <div class="px-4 pb-4 pt-1 space-y-3 border-t border-accent-gray/30">
                                        @foreach($thread['mensajes'] as $m)
                                            <div class="pt-2">
                                                {{-- Burbujas como en el chat real, en vez de emoji --}}
                                                <div class="flex justify-end">
                                                    <p class="text-sm text-text-primary bg-accent-gray/40 rounded-2xl rounded-tr-md px-3 py-2 max-w-[85%]">{{ $m->user_message }}</p>
                                                </div>
                                                <div class="flex justify-start mt-1.5">
                                                    <div class="max-w-[85%]">
                                                        <p class="text-sm text-text-secondary bg-accent-gray/20 rounded-2xl rounded-tl-md px-3 py-2">{{ $m->bot_response }}</p>
                                                        {{-- Qué platos le ofreció de verdad. En el chat el cliente
                                                             ve las tarjetas con foto y precio; acá alcanza con los
                                                             nombres, que es lo que permite detectar de un vistazo
                                                             cuándo el asistente recomendó cualquier cosa.
                                                             NULL = conversación anterior a que se guardara. --}}
                                                        @if(!empty($m->platos_ofrecidos))
                                                            <p class="text-[11px] text-text-secondary mt-1">
                                                                <span class="opacity-60">ofreció:</span>
                                                                {{ implode(' · ', $m->platos_ofrecidos) }}
                                                            </p>
                                                        @endif
                                                        <p class="text-[11px] text-text-secondary/70 mt-1">
                                                            {{ $m->created_at->format('H:i') }}@if($m->response_time_ms) &middot; {{ number_format($m->response_time_ms / 1000, 1, ',', '.') }} s @endif
                                                            @unless($m->used_openai) &middot; <span class="text-amber-500">respuesta de respaldo</span> @endunless
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <x-icono name="{{ $q !== '' || $soloSinResolver ? 'search' : 'messages' }}" class="mx-auto w-10 h-10 text-text-secondary opacity-40" />
                            @if($q !== '' || $soloSinResolver)
                                <p class="mt-3 text-sm text-text-primary">Ningún hilo coincide con la búsqueda</p>
                                <p class="mt-1 text-sm text-text-secondary">Probá con otra palabra o ampliá el período.</p>
                                <a href="{{ route('analytics.comercio', $comercio) }}" class="mt-3 inline-block text-sm text-accent-blue underline">Ver todas</a>
                            @else
                                <p class="mt-3 text-sm text-text-primary">Todavía no hay conversaciones</p>
                                <p class="mt-1 text-sm text-text-secondary">Cuando alguien use el chat del menú, los hilos aparecen acá.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

