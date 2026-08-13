{{-- Tracking de eventos del menú público. Requiere $comercio y opcional $trackView. --}}
<script>
(function () {
    const url = @json(route('track', $comercio));
    const view = @json($trackView ?? null);
    const storageKey = 'servime_chat_session_{{ $comercio->id }}';

    function sessionId() {
        let s = localStorage.getItem(storageKey);
        if (!s) {
            s = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem(storageKey, s);
        }
        return s;
    }

    window.smTrack = function (event, data) {
        try {
            const body = JSON.stringify({ event: event, data: data || {}, session_id: sessionId() });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
            } else {
                fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true });
            }
        } catch (e) { /* tracking nunca debe romper la página */ }
    };

    // Vista actual (de acá salen: IA vs clásico, horarios y tiempo de sesión)
    if (view) window.smTrack('page_view', { view: view });

    // Delegación de clicks (funciona también con contenido generado dinámicamente)
    document.addEventListener('click', function (e) {
        // Además de QUÉ eligió (ia|classic), de DÓNDE salió el click: el botón grande,
        // un chip sugerido (y cuál) o el link al menú completo. Sin esto, todas las
        // puertas al asistente se mezclaban en un solo número y no se podía saber si
        // los chips sirven ni cuál conviene cambiar.
        const choice = e.target.closest('[data-track-choice]');
        if (choice) {
            window.smTrack('menu_choice', {
                choice: choice.getAttribute('data-track-choice'),
                desde: choice.getAttribute('data-track-desde') || null,
                label: (choice.getAttribute('data-track-label') || '').trim().slice(0, 60) || null,
                // El id de la VERSIÓN del atajo, para cruzar este click con la consulta que
                // generó después. Sin él habría que cruzar por texto, y editar la frase
                // rompería el cruce justo cuando se quiere comparar antes y después.
                chip: choice.getAttribute('data-track-chip') || null,
            });
            return;
        }

        const promo = e.target.closest('.cat-chip-promo');
        if (promo) { window.smTrack('promo_click', { promo: (promo.textContent || '').trim().slice(0, 80) }); return; }

        // Qué parte de la carta miran. Distinto de dish_want: esto es curiosear una
        // categoría, aquello es querer un plato puntual.
        const cat = e.target.closest('.cat-chip[data-categoria]');
        if (cat) { window.smTrack('categoria_tap', { nombre: cat.getAttribute('data-categoria').slice(0, 60) }); return; }

        // Qué restricciones tienen sus clientes (sin TACC, vegano...). Es lo único que
        // contesta esa pregunta, y hasta ahora se tiraba.
        const tag = e.target.closest('.tag-chip[data-tag]');
        if (tag) { window.smTrack('tag_tap', { nombre: tag.getAttribute('data-tag').slice(0, 60) }); return; }

        const dish = e.target.closest('.btn-lo-quiero');
        if (dish) { window.smTrack('dish_want', { nombre: dish.getAttribute('data-nombre'), price: dish.getAttribute('data-price') }); return; }
    });
})();
</script>
