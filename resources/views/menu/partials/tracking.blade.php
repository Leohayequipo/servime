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
        const choice = e.target.closest('[data-track-choice]');
        if (choice) { window.smTrack('menu_choice', { choice: choice.getAttribute('data-track-choice') }); return; }

        const promo = e.target.closest('.cat-chip-promo');
        if (promo) { window.smTrack('promo_click', { promo: (promo.textContent || '').trim().slice(0, 80) }); return; }

        const dish = e.target.closest('.btn-lo-quiero');
        if (dish) { window.smTrack('dish_want', { nombre: dish.getAttribute('data-nombre'), price: dish.getAttribute('data-price') }); return; }
    });
})();
</script>
