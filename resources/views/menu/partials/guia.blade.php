{{--
    Guía de bienvenida: el tour que señala dónde tocar (Driver.js). Requiere $comercio y
    $pantalla ('portada' | 'chat'). Va DESPUÉS del tracking, porque usa window.smTrack.

    Reglas (acordadas oct-2026):
    - La prende o apaga el comercio desde el editor (`guia_clientes`, arranca prendida).
    - Se muestra UNA vez por teléfono y por pantalla, para todos los comercios: la app es
      la misma, el que la vio en un local ya sabe usarla en otro. La marca se pone al
      MOSTRARLA, no al terminarla: el que la cierra en el primer paso es el que no la quiere.
    - Versión en la clave: pasarla a v2 la vuelve a mostrar a todos (algo nuevo que enseñar).
    - En el chat no sale si llegó con una pregunta (atajo de la portada) ni si ya charló en
      esta visita. Si el navegador no deja guardar (incógnito), no sale nunca: si no,
      aparecería en cada entrada.
    - `?guia=1` la fuerza sin marcar nada: es lo que usa el botón "Ver cómo se ve" del editor,
      y por eso funciona aunque esté apagada y dentro de la vista previa.

    Driver.js se descarga recién cuando hay que mostrarla: el resto de las visitas no paga
    ni un byte.
--}}
@php
    $guiaForzada = request()->boolean('guia');
    $guiaActiva = $guiaForzada || ($comercio->guia_clientes && ! \App\Support\VistaPrevia::activa($comercio));

    $pasosGuia = $pantalla === 'portada'
        ? [
            ['el' => '.cta', 'titulo' => __('Tu camarero virtual'), 'texto' => __('Contale qué tenés ganas de comer y te recomienda de la carta.')],
            ['el' => '.grid', 'titulo' => __('Atajos'), 'texto' => __('Un toque y te muestra lo que buscás.')],
            ['el' => '.completo', 'titulo' => __('¿Preferís mirar la carta entera?'), 'texto' => __('Acá está completa, con fotos y precios.')],
        ]
        : [
            ['el' => '#messageInput', 'lado' => 'top', 'titulo' => __('Preguntá como le preguntarías al mozo'), 'texto' => __('Escribí lo que tengas ganas: «algo dulce», «sin TACC», «qué va con una birra».')],
            ['el' => '#micButton', 'lado' => 'top', 'titulo' => __('¿Más cómodo hablando?'), 'texto' => __('Mantené apretado el micrófono, hablá y soltalo.')],
            ['el' => '#guiaAyuda', 'lado' => 'bottom', 'titulo' => __('¿Querés verlo de nuevo?'), 'texto' => __('Tocá acá cuando quieras.')],
        ];

    // Driver.js marca el número de paso con dobles llaves (current / total): se arman así
    // para que Blade no las tome como suyas.
    $progresoGuia = str_replace([':actual', ':total'], ['{'.'{current}}', '{'.'{total}}'], __(':actual de :total'));
@endphp

@if($guiaActiva)
<script>
(function () {
    const PANTALLA = @json($pantalla);
    const FORZADA = @json($guiaForzada);
    const CLAVE = 'servime_guia_' + PANTALLA + '_v1';
    const PASOS = @json($pasosGuia);
    const TEXTOS = {
        progreso: @json($progresoGuia),
        siguiente: @json(__('Siguiente')),
        atras: @json(__('Atrás')),
        listo: @json(__('¡Listo!')),
    };
    const DRIVER_JS = 'https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js';
    const DRIVER_CSS = 'https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css';

    let cargando = null;
    function cargarDriver() {
        if (window.driver?.js?.driver) return Promise.resolve();
        if (cargando) return cargando;
        cargando = new Promise((ok, mal) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = DRIVER_CSS;
            document.head.appendChild(css);
            const js = document.createElement('script');
            js.src = DRIVER_JS;
            js.onload = ok;
            js.onerror = mal;
            document.head.appendChild(js);

            // El globo con los colores del menú: oscuro y con el acento del comercio.
            const raiz = getComputedStyle(document.documentElement);
            const acento = (raiz.getPropertyValue('--accent') || raiz.getPropertyValue('--acento') || '').trim() || '#A9561E';
            const estilo = document.createElement('style');
            estilo.textContent = `
                .driver-popover.servime-guia { background: #1A1614; color: #F5F2EF; border: 1px solid rgba(255,255,255,.12);
                    border-radius: 16px; padding: 18px; max-width: min(290px, calc(100vw - 24px)); min-width: 0;
                    box-shadow: 0 12px 40px rgba(0,0,0,.5); overflow-y: auto; }
                .servime-guia .driver-popover-title { font-size: 18px; font-weight: 700; color: #fff; }
                .servime-guia .driver-popover-description { font-size: 15px; line-height: 1.45; color: #E4DDD5; margin-top: 6px; }
                .servime-guia .driver-popover-progress-text { color: #A39A93; font-size: 13px; }
                .servime-guia .driver-popover-arrow-side-top.driver-popover-arrow { border-top-color: #1A1614; }
                .servime-guia .driver-popover-arrow-side-bottom.driver-popover-arrow { border-bottom-color: #1A1614; }
                .servime-guia .driver-popover-arrow-side-left.driver-popover-arrow { border-left-color: #1A1614; }
                .servime-guia .driver-popover-arrow-side-right.driver-popover-arrow { border-right-color: #1A1614; }
                .servime-guia .driver-popover-footer button { text-shadow: none; border: none; border-radius: 10px;
                    padding: 8px 14px; font-size: 14px; font-weight: 600; background: #2A2420; color: #F5F2EF; }
                .servime-guia .driver-popover-footer .driver-popover-next-btn { background: ${acento}; color: #fff; }
                .servime-guia .driver-popover-close-btn { color: #A39A93; }`;
            document.head.appendChild(estilo);
        });
        return cargando;
    }

    // El globo siempre dentro de lo que se ve, con el botón "Siguiente" a mano.
    //
    // Driver.js lo calcula una vez contra la ventana. En el celular eso se rompe: el
    // teclado y la barra del navegador cambian la pantalla visible (visualViewport) sin
    // que el globo se entere, y queda tapado o fuera de pantalla, sin forma de tocar
    // "Siguiente". Acá se mide contra la pantalla visible y se lo corre lo justo.
    const MARGEN = 8;
    function encuadrar() {
        const el = document.querySelector('.driver-popover.servime-guia');
        if (!el) return;
        const vv = window.visualViewport;
        const arriba = vv ? vv.offsetTop : 0;
        const izq = vv ? vv.offsetLeft : 0;
        const ancho = vv ? vv.width : window.innerWidth;
        const alto = vv ? vv.height : window.innerHeight;

        // Si es más alto que la pantalla, que scrollee por dentro en vez de salirse.
        el.style.maxHeight = Math.max(120, alto - MARGEN * 2) + 'px';

        const r = el.getBoundingClientRect();
        let dx = 0, dy = 0;
        if (r.left < izq + MARGEN) dx = izq + MARGEN - r.left;
        else if (r.right > izq + ancho - MARGEN) dx = izq + ancho - MARGEN - r.right;
        if (r.top < arriba + MARGEN) dy = arriba + MARGEN - r.top;
        else if (r.bottom > arriba + alto - MARGEN) dy = arriba + alto - MARGEN - r.bottom;

        if (dx || dy) {
            el.style.left = (r.left + dx) + 'px';
            el.style.top = (r.top + dy) + 'px';
            el.style.right = 'auto';
            el.style.bottom = 'auto';
            // Corrido, la flecha ya no apunta bien: mejor sin flecha que una torcida.
            const flecha = el.querySelector('.driver-popover-arrow');
            if (flecha) flecha.style.display = 'none';
        }
    }
    let pendiente = null;
    let escuchando = false;
    function encuadrarDespues() {
        clearTimeout(pendiente);
        // Driver reposiciona el globo en su propio resize: se espera a que termine.
        pendiente = setTimeout(encuadrar, 120);
    }

    async function mostrar(repetida) {
        // Sólo los pasos que existen y se ven en este comercio (sin atajos, sin micrófono...).
        const pasos = PASOS.filter(p => {
            const el = document.querySelector(p.el);
            return el && !el.hidden && el.getClientRects().length > 0;
        });
        if (!pasos.length) return;

        try { await cargarDriver(); } catch (e) { return; } // sin CDN, sin guía: no rompe nada

        const guia = window.driver.js.driver({
            steps: pasos.map(p => ({
                element: p.el,
                popover: { title: p.titulo, description: p.texto, side: p.lado || 'bottom', align: 'center' },
            })),
            showProgress: pasos.length > 1,
            progressText: TEXTOS.progreso,
            nextBtnText: TEXTOS.siguiente,
            prevBtnText: TEXTOS.atras,
            doneBtnText: TEXTOS.listo,
            popoverClass: 'servime-guia',
            // Lo que se señala NO se toca mientras dura la guía. Si no, el que toca el
            // campo de texto abre el teclado (y el globo se va de pantalla) y el que toca
            // el micrófono empieza a grabar sin querer.
            disableActiveInteraction: true,
            onPopoverRender: encuadrarDespues,
            onHighlighted: encuadrarDespues,
            overlayOpacity: 0.72,
            stagePadding: 6,
            stageRadius: 18,
            onDestroyStarted: () => {
                if (!repetida) {
                    window.smTrack?.(guia.isLastStep() ? 'guia_completa' : 'guia_cerrada', {
                        pantalla: PANTALLA, paso: (guia.getActiveIndex() ?? 0) + 1, de: pasos.length,
                    });
                }
                guia.destroy();
            },
        });
        window.smTrack?.('guia_vista', { pantalla: PANTALLA, repetida: !!repetida });
        // Teclado, barra del navegador, giro de pantalla: se vuelve a encuadrar.
        if (!escuchando) {
            escuchando = true;
            window.addEventListener('resize', encuadrarDespues);
            window.visualViewport?.addEventListener('resize', encuadrarDespues);
            window.visualViewport?.addEventListener('scroll', encuadrarDespues);
        }
        guia.drive();
    }

    // Para el "?" del chat.
    window.smGuia = { mostrar: () => mostrar(true) };

    async function arrancar() {
        if (FORZADA) return mostrar(true);

        let vista;
        try { vista = localStorage.getItem(CLAVE); } catch (e) { return; }
        if (vista) return;

        if (PANTALLA === 'chat') {
            // Llegó preguntando algo (atajo de la portada): ya sabe a qué vino.
            if (window.servimeLlegoConPregunta) return;
            // Volvió a una charla de esta visita: ya lo usó.
            if (typeof historialListo !== 'undefined') { try { await historialListo; } catch (e) {} }
            if (document.querySelector('#chatMessages .bg-accent-blue, #chatMessages .burbuja-voz')) return;
        }

        try { localStorage.setItem(CLAVE, '1'); } catch (e) { return; }
        mostrar(false);
    }

    // En la portada, después de las animaciones de entrada.
    setTimeout(arrancar, PANTALLA === 'portada' ? 1300 : 700);
})();
</script>
@endif
