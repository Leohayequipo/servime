{{-- Modo vista previa del menú público (ver App\Support\VistaPrevia).

     Lo incluye `tracking.blade.php` EN LUGAR del tracking: así no hay forma de que una
     pantalla nueva registre eventos en la vista previa, porque todas pasan por ahí.

     Dos cosas hace acá:
     - `smTrack` existe pero no manda nada. Las pantallas lo llaman sin chequear, y si no
       estuviera definido tirarían error al primer click.
     - Los links internos del menú conservan `?preview=1`. Sin eso, tocar "Ver la carta"
       adentro del celular abría la carta normal, que sí registra la visita. --}}
<script>
(function () {
    window.SM_VISTA_PREVIA = true;
    window.smTrack = function () {};

    // El menú que se está editando (?menu=ID) viaja igual: sin él, pasar de la portada a
    // la carta mostraba el menú vigente del comercio en vez del que se edita.
    const menuEnEdicion = new URLSearchParams(window.location.search).get('menu');

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || !url.pathname.startsWith('/menu/')) return;

        url.searchParams.set('preview', '1');
        if (menuEnEdicion) url.searchParams.set('menu', menuEnEdicion);
        link.href = url.toString();
    }, true);
})();
</script>
