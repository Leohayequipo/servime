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
                        ayuda="Cuántos clientes distintos abrieron tu menú en los últimos 30 días, sin importar cuántas veces lo hayan mirado. «Consultas» cuenta mensajes; esto cuenta personas: 54 consultas pueden ser de 50 clientes o de 3 indecisos. Se identifica por dispositivo, así que quien entra desde el celular y después desde la computadora cuenta como dos, y si borra los datos del navegador vuelve a contar como alguien nuevo." />

                {{-- El período va en cada nota y no solo en el encabezado: "respondió
                     todas" sin decir de cuándo no se puede interpretar. --}}
                <x-stat label="Sin responder"
                        :value="$sinResp"
                        :nota="($sinResp > 0 ? $pctSinResp.'% de las consultas' : 'respondió todas').' · últimos 30 días'"
                        :tono="$sinResp > 0 ? 'alerta' : 'neutro'"
                        ayuda="Las veces que un cliente escribió y el asistente no pudo contestarle, y le respondió un mensaje de disculpas. Pasa por dos motivos: se agotó la cuota mensual de mensajes de tu plan, o el servicio tuvo una falla puntual. En cero está todo bien; si el número sube, escribinos, porque puede que te convenga ampliar el plan." />

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
                        <x-ayuda titulo="¿IA o menú clásico?" texto="Qué elige la gente al llegar: preguntarle al asistente o ver el menú de toda la vida. Se cuenta una vez por visita y vale la primera elección, así que quien va y vuelve entre los dos no inclina la balanza. Si alguien probó los dos, se avisa aparte. Tené en cuenta que la pantalla inicial no los ofrece parejos: el asistente está en un botón grande y con atajos, y el menú completo en un link más abajo." />
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
                        <x-ayuda titulo="Embudo de interés" texto="Muestra hasta dónde llega la gente que abre tu menú, paso a paso: todos visitan, algunos pasan por la pantalla inicial, menos eligen cómo ver el menú, menos todavía tocan «Lo quiero» y unos pocos abren su pedido para revisarlo antes de pedírselo al mozo. Ese último escalón es el que más se parece a una venta: ya tenían platos elegidos y fueron a mirar la lista. Donde veas la caída más grande, ahí está lo que conviene revisar. Cuenta personas, no toques: si alguien vuelve otro día es una visita nueva, pero si toca cinco veces seguidas sigue siendo una." />
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
                            <x-ayuda titulo="¿Por dónde entran?" texto="En la pantalla inicial hay tres caminos: el botón grande del asistente, los atajos sugeridos (que también abren el asistente, pero con la pregunta ya escrita) y el link al menú completo de toda la vida. Esto te dice cuál usan. Ojo al comparar: el asistente tiene dos puertas y el menú completo una sola, así que es esperable que junte más. Se cuentan toques, no personas: acá lo que importa es qué botón se usa." />
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

                    <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                        <h3 class="text-lg font-medium text-text-primary mb-1 flex items-center gap-2">
                            <x-icono name="target" class="w-4 h-4 flex-none opacity-60" />
                            Atajos más usados
                            <x-ayuda titulo="Atajos más usados" texto="Los atajos son los botones chicos de la pantalla inicial («¿Qué me recomendás?», «Ver promos», «Sin TACC»). Al tocarlos, el cliente entra al asistente con esa pregunta ya hecha. Hoy se arman solos a partir de tu menú: si tenés una categoría de promociones aparece el de promos, si tenés platos sin TACC aparece ese. Este ranking te dice cuál engancha y cuál no le interesa a nadie." />
                        </h3>
                        <p class="text-text-secondary text-sm mb-4">Los botones sugeridos de la portada. El que nadie toca conviene cambiarlo.</p>
                        @php $chipMax = $metrics['topChips']->max() ?: 1; @endphp
                        @forelse($metrics['topChips'] as $chip => $count)
                            <div class="mb-3">
                                <div class="flex justify-between text-sm text-text-secondary mb-1">
                                    <span class="truncate">{{ $chip }}</span><b>{{ $count }}</b>
                                </div>
                                <div class="h-2 bg-accent-gray/40 rounded">
                                    <div class="h-2 bg-indigo-500 rounded" style="width: {{ round($count / $chipMax * 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-text-secondary text-center py-4">Todavía nadie tocó un atajo.</p>
                        @endforelse
                    </div>
                </div>

            <!-- Dispositivo · Visitantes · Micrófono -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3 flex items-center gap-2">
                        <x-icono name="phone" class="w-4 h-4 flex-none opacity-60" />
                        Dispositivos
                        <x-ayuda titulo="Dispositivos" texto="Desde qué aparato abren tu menú, según lo que informa el navegador. Como el QR está en la mesa, lo esperable es que sea casi todo móvil: si ves mucho escritorio, quiere decir que están llegando por un link compartido o desde tus redes, y no escaneando el código en el local. Eso cambia para qué te sirve el menú, y conviene saberlo. Un detalle: cuando el navegador no informa qué es, cuenta como escritorio, así que ese número puede venir un poco alto." />
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
                        <x-ayuda titulo="Visitantes que vuelven" texto="Cuántos abrieron tu menú en dos momentos distintos, separados por más de media hora. No hace falta que sea otro día: alguien que mira la carta al llegar y la vuelve a abrir para el postre ya cuenta acá, así que no lo leas como «clientes habitués». Se reconoce por dispositivo, no por persona: si entra desde el celular y después desde la computadora son dos visitantes, y si borra los datos del navegador vuelve a figurar como nuevo. En iPhone el número queda corto, porque Safari borra esa marca a los 7 días sin usar el menú: los que vuelven la semana siguiente se cuentan como nuevos." />
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
                @php $voz = $metrics['vozVisitas'] ?? 0; $vtot = $metrics['totalVisitas'] ?: 1; @endphp
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-sm font-medium text-text-secondary mb-3 flex items-center gap-2">
                        <x-icono name="mic" class="w-4 h-4 flex-none opacity-60" />
                        Hablaron con el asistente
                        <x-ayuda titulo="Hablaron con el asistente" texto="Cuántas visitas usaron el micrófono para dictar su consulta en vez de escribirla. Sirve para saber si esa función se usa: en un local ruidoso o con gente alrededor puede que nadie se anime, y si nunca la toca nadie no vale la pena tenerla ahí ocupando lugar. No funciona en todos los navegadores, así que parte de los que no aparecen acá es porque no les apareció el botón." />
                    </h3>
                    <p class="text-4xl font-bold text-text-primary">{{ round($voz / $vtot * 100) }}%</p>
                    <p class="text-sm text-text-secondary mt-1">{{ $voz }} de {{ $metrics['totalVisitas'] }} visitas usaron el micrófono</p>
                </div>
            </div>

            <!-- Promos y platos top -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="flame" class="w-4 h-4 flex-none opacity-60" />
                        Promos más clickeadas
                        <x-ayuda titulo="Promos más clickeadas" texto="Si tu menú tiene una categoría marcada como promoción, en el chat aparece como un botón destacado. Acá ves cuántas veces lo tocaron. Ojo con leer de más: que una promo se mire mucho no significa que se venda; para eso mirá si esos clientes después llegaron a tocar «Lo quiero». La barra se dibuja contra la promo más tocada, así que con una sola promo siempre se va a ver llena." />
                    </h3>
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
                        <p class="text-text-secondary text-center py-4">Nadie clickeó promos aún.</p>
                    @endforelse
                </div>

                <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-text-primary mb-4 flex items-center gap-2">
                        <x-icono name="heart" class="w-4 h-4 flex-none opacity-60" />
                        Platos más deseados
                        <x-ayuda titulo="Platos más deseados" texto="Cuando el asistente recomienda un plato, lo muestra en una tarjeta con foto y precio y un botón «Lo quiero» que lo suma al pedido. Esto es el ranking de los platos que más veces tocaron. Es lo más cerca de una venta que ves acá, pero no es una venta: el cliente puede sumarlo y después arrepentirse, o pedirle otra cosa al mozo. Se cuenta cada toque, así que si alguien suma dos milanesas, suma dos." />
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
                        <x-ayuda titulo="Qué categorías miran" texto="Cuando el cliente habla con el asistente puede abrir la lista de categorías de tu carta y tocar una. Esto es el ranking de esas categorías. Ojo que no es lo mismo que «Platos más deseados»: allá el cliente ya decidió que quiere un plato, acá recién está curioseando una parte del menú. Sirve para saber qué despierta interés y qué conviene mostrar primero. Se cuenta cada toque, así que volver dos veces a los postres suma dos." />
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
                        <x-ayuda titulo="Qué etiquetas filtran" texto="En el chat el cliente puede filtrar tu carta por etiqueta: sin TACC, vegano, sin lactosa, lo que tengas cargado en los platos. Este ranking es lo único del panel que te dice qué necesidades alimentarias tienen tus clientes, y es de lo más accionable que hay: si muchos filtran por algo que casi no tenés, ahí hay platos para sumar. Las etiquetas salen de tu menú, así que si un plato apto no está etiquetado, el filtro no lo encuentra." />
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
                        </h3>
                        <p class="text-text-secondary text-sm mb-4">Lo que los clientes escribieron, y si el asistente pudo recomendarles algo del menú.</p>
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
                                Todavía nadie escribió una pregunta.
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
                                        <div>
                                            <p class="text-sm font-medium text-text-primary">
                                                {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                                            </p>
                                            <p class="text-xs text-text-secondary">
                                                {{ $day->openai_count }}/{{ $day->total }} con IA
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-text-primary">
                                                {{ $day->total }} chats
                                            </p>
                                            @if($day->avg_response_time)
                                                <p class="text-xs text-text-secondary">
                                                    {{ round($day->avg_response_time) }}ms promedio
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
                <div class="p-6 historial">
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
                                            <p class="text-xs text-text-secondary mt-1">
                                                {{ $thread['inicio']->format('d/m H:i') }}@if($thread['total'] > 1) &middot; {{ $thread['total'] }} mensajes @endif
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

