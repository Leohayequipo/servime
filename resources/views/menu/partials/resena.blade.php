{{--
    Pedido de reseña en Google. Requiere $comercio y que $comercio->pideResena() sea true.

    NO es un temporizador. Un setTimeout de 20 minutos no sirve en celular: la persona mira
    la carta, pide, bloquea el teléfono y come. El navegador suspende la pestaña en segundo
    plano y el timer no dispara, o dispara tarde y contra una pantalla apagada.

    Y el momento tampoco es el minuto 20: es cuando el teléfono VUELVE a la mano, que es
    cuando terminaron. Por eso el disparo es `visibilitychange`, y el tiempo solo se usa
    como condición. Si nunca vuelve, no pasa nada: no se le debe nada a nadie.

    El estado vive en localStorage y no en memoria, porque iOS descarta pestañas viejas: al
    volver, la página se recarga entera y en memoria no habría quedado nada.
--}}
<style>
    /* El valor estático va primero: si el navegador no soporta color-mix, gana ese y el
       bloque igual se ve. Mismo criterio que --accent-soft en el resto del menú. */
    #bloque-resena .resena-caja {
        border: 1px solid rgba(255, 255, 255, .22);
        border-color: color-mix(in srgb, var(--accent) 45%, transparent);
        background: rgba(255, 255, 255, .06);
        background: linear-gradient(168deg,
            color-mix(in srgb, var(--accent) 16%, transparent),
            color-mix(in srgb, var(--accent) 5%, transparent));
    }
    #bloque-resena .resena-cta {
        background: var(--accent);
        color: #fff;
        padding: 11px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
    }
    #bloque-resena .resena-cta:hover { filter: brightness(1.07); }
    #bloque-resena .resena-estrellas {
        color: var(--accent);
        font-size: 17px;
        letter-spacing: 3px;
        line-height: 1;
    }
    #bloque-resena .resena-titulo { font-size: 15px; font-weight: 600; margin: 9px 0 4px; }
    #bloque-resena .resena-texto { font-size: 13.5px; line-height: 1.45; margin: 0 0 13px; }
    /* Discreto a propósito: es la salida para el que no quiere, no una opción que compita
       con el botón. Igual con área tocable de sobra, que si no es una trampa en el celular. */
    #bloque-resena .resena-no {
        display: block;
        width: 100%;
        margin-top: 4px;
        padding: 9px;
        background: none;
        border: 0;
        color: var(--text-dim);
        font-size: 13px;
        cursor: pointer;
    }
    #bloque-resena .resena-no:hover { color: var(--text); }
    #bloque-resena .resena-gracias {
        text-align: center;
        color: var(--accent);
        font-size: 14px;
        font-weight: 600;
        padding: 6px 0;
    }
</style>
<script>
(function () {
    'use strict';

    // Los umbrales salen de config/servime.php, no del panel: si cada comercio los toca,
    // no se puede comparar nada entre locales. Por env se pueden bajar en local para
    // probar sin esperar veinte minutos.
    var CFG = {
        url: @json($comercio->google_review_url),
        minMensajes: @json(config('servime.resenas.mensajes')),
        minutos: @json(config('servime.resenas.minutos')),
        diasDeGracia: @json(config('servime.resenas.dias_gracia')),
        // Los escribe el comercio desde el panel. Se insertan con textContent y no como
        // HTML: es texto de un tercero y no tiene por qué poder meter markup en la página.
        // (Ojo al comentar acá: escribir el nombre de una directiva Blade suelto en un
        // comentario JS la hace compilar igual y tira la vista entera.)
        titulo: @json($comercio->resenaTitulo()),
        texto: @json($comercio->resenaTexto()),
    };

    var KEY = 'servime_resena_{{ $comercio->id }}';
    var MIN = 60 * 1000;

    function leer() {
        try {
            var raw = localStorage.getItem(KEY);
            var s = raw ? JSON.parse(raw) : null;
            if (s && typeof s === 'object') return s;
        } catch (e) { /* localStorage lleno o bloqueado */ }
        return { inicio: Date.now(), msgs: 0, mostrado: null };
    }

    function guardar(s) {
        try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) { /* no romper el chat */ }
    }

    var S = leer();
    if (!S.inicio) { S.inicio = Date.now(); }
    guardar(S);

    /** ¿Usó el menú de verdad? Tres mensajes, o algo en el carrito. */
    function usoReal() {
        if (S.msgs >= CFG.minMensajes) return true;
        try {
            if (typeof loadWish === 'function' && loadWish().length > 0) return true;
        } catch (e) { /* el carrito puede no estar listo todavía */ }
        return false;
    }

    function yaSeLePidio() {
        // 0 = sin memoria. Es el modo de prueba: deja repetir el flujo entero sin tener
        // que borrar el localStorage del teléfono, que en un celular es un dolor.
        if (CFG.diasDeGracia <= 0) return false;

        // Al que YA dejó su reseña no se le vuelve a pedir. La gracia normal corre desde
        // que se muestra, así que sin esto, a los 15 días le estaríamos pidiendo otra vez
        // justo a la única persona que hizo lo que le pedimos. Y dos reseñas del mismo
        // local, además, no es algo que Google quiera.
        if (S.resenado) return true;

        if (!S.mostrado) return false;

        return (Date.now() - S.mostrado) < CFG.diasDeGracia * 24 * 60 * MIN;
    }

    function mostrar() {
        var cont = document.getElementById('chatMessages');
        if (!cont || document.getElementById('bloque-resena')) return;

        var div = document.createElement('div');
        div.id = 'bloque-resena';
        div.innerHTML =
            '<div class="resena-caja animate-slide-in-left rounded-2xl px-4 py-4 shadow-lg">' +
              '<div class="resena-estrellas">★★★★★</div>' +
              '<p class="resena-titulo text-text-primary"></p>' +
              '<p class="resena-texto text-text-secondary"></p>' +
              '<a id="resena-cta" target="_blank" rel="noopener noreferrer"' +
              ' class="resena-cta block w-full text-center rounded-xl">{{ __('Escribir reseña') }}</a>' +
              '<button type="button" id="resena-no" class="resena-no">{{ __('Ahora no') }}</button>' +
            '</div>';

        // Título y texto por textContent: los escribió el comercio y no tienen por qué
        // poder inyectar markup. El href igual, por propiedad y no interpolado en el HTML.
        div.querySelector('.resena-titulo').textContent = CFG.titulo;
        div.querySelector('.resena-texto').textContent = CFG.texto;
        div.querySelector('#resena-cta').href = CFG.url;

        cont.appendChild(div);

        var scroll = document.getElementById('chatScroll');
        if (scroll) scroll.scrollTop = scroll.scrollHeight;

        // Vibración: es un extra, no el mecanismo. En iPhone `vibrate` no existe y ahí
        // simplemente no pasa nada — el bloque igual quedó a la vista, que es lo que
        // importa. En Android le avisa sin ruido, que en una mesa se agradece.
        try {
            if (navigator.vibrate) navigator.vibrate([60, 40, 60]);
        } catch (e) { /* nunca romper por una vibración */ }

        S.mostrado = Date.now();
        guardar(S);

        if (window.smTrack) window.smTrack('resena_view');

        // Al que ya fue a Google no se le sigue pidiendo lo mismo: el bloque pasa a
        // agradecer. Vuelve del navegador y encontrarse el mismo pedido intacto parece
        // que no registró lo que hizo.
        document.getElementById('resena-cta').addEventListener('click', function () {
            if (window.smTrack) window.smTrack('resena_click');

            // Queda marcado para siempre: ya cumplió, no se le pide de nuevo. Se guarda
            // antes de tocar el DOM porque acá el navegador se va a Google.
            S.resenado = true;
            guardar(S);

            div.querySelector('.resena-caja').innerHTML =
                '<div class="resena-gracias">{{ __('¡Gracias! ✦') }}</div>';
        });

        document.getElementById('resena-no').addEventListener('click', function () {
            if (window.smTrack) window.smTrack('resena_dismiss');
            div.remove();
        });
    }

    var timer = null;

    /**
     * Reloj de respaldo para cuando el teléfono NO se va.
     *
     * `visibilitychange` solo dispara en las transiciones. Si el cliente se queda mirando
     * la pantalla mientras se cumplen los minutos —pidió, dejó el teléfono en la mesa a la
     * vista, siguió chusmeando la carta—, no vuelve a dispararse nada y el bloque no
     * aparece nunca. Probado en un celular real: fue exactamente lo que pasó.
     *
     * Sigue sin ser el mecanismo principal, y por eso no reemplaza a nada: un timer no es
     * confiable en segundo plano, que es justamente el caso que cubre `visibilitychange`.
     * Cada uno tapa el agujero del otro.
     */
    function programar() {
        if (timer !== null) return;
        var falta = (CFG.minutos * MIN) - (Date.now() - S.inicio);
        if (falta <= 0) return;
        timer = setTimeout(function () {
            timer = null;
            revisar();
        }, falta + 500);
    }

    function revisar() {
        if (document.hidden) return;
        if (yaSeLePidio()) return;
        if (!usoReal()) return;
        if ((Date.now() - S.inicio) < CFG.minutos * MIN) {
            // Todavía no, pero va a llegar solo: dejar programado el reintento.
            programar();

            return;
        }
        mostrar();
    }

    // El disparo. `visibilitychange` cubre el caso normal (desbloqueó el teléfono con la
    // pestaña viva); el chequeo al cargar cubre el otro, cuando iOS descartó la pestaña y
    // volver implicó recargar. Los dos terminan en el mismo lugar.
    document.addEventListener('visibilitychange', revisar);
    window.addEventListener('pageshow', revisar);

    // Contador de mensajes, que llama el chat al mandar cada uno.
    window.smResena = {
        contarMensaje: function () {
            S.msgs++;
            guardar(S);
            // Recién ahora puede tener sentido esperar: antes del mensaje que cumple el
            // mínimo, programar el reloj sería para nada.
            if (usoReal()) revisar();
        },
    };

    revisar();
})();
</script>
