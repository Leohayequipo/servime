/**
 * Achica en el navegador un PDF de carta que pesa más de lo que se puede subir.
 *
 * Se hace acá y no en el servidor porque el problema es justamente subirlo: un PDF de 50 MB
 * no pasa el límite de subida, y Gemini recibe el archivo dentro del pedido (en base64), con
 * un tope de ~20 MB. Lo que la IA necesita leer entra holgado en mucho menos.
 *
 * Dos pasos, y el segundo sólo si el primero no alcanza:
 *
 *   1. Sacar lo que el PDF guarda pero no se ve. Illustrator, con "Conservar capacidades de
 *      edición", mete una copia entera del archivo de diseño (/PieceInfo de cada página): en
 *      una carta real eran 37 de 49 MB. Sin eso, el PDF se ve idéntico.
 *   2. Pasar cada página a una imagen JPEG y armar un PDF nuevo con ellas. Sirve para
 *      escaneos y fotos enormes; se pierde el texto seleccionable, que a la IA no le importa.
 *
 * Las librerías se bajan recién cuando hacen falta: casi ningún PDF pasa el límite.
 *
 * Uso: `await window.achicarPdf(file, { maxBytes, alAvanzar })` devuelve
 * `{ file, como: 'limpio' | 'imagenes' }`, o null si ni así entra. Si algo falla, tira error.
 */
(function () {
    'use strict';

    // Versiones fijas y con hash: si el CDN sirviera otra cosa, el navegador no la ejecuta.
    // pdf.js va en la 3 "legacy": es la última que se carga con un <script> común (de la 4 en
    // adelante son módulos) y la que anda en navegadores viejos.
    const PDF_LIB = {
        src: 'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js',
        integrity: 'sha384-weMABwrltA6jWR8DDe9Jp5blk+tZQh7ugpCsF3JwSA53WZM9/14PjS5LAJNHNjAI',
    };
    const PDF_JS = {
        src: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/legacy/build/pdf.min.js',
        integrity: 'sha384-OemFRmhjDZwhIKuUld0HJozkF2YErsgDaCL41trxGQZt4/WgnopJQqQl2DvDZ07Z',
    };
    const PDF_JS_WORKER = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/legacy/build/pdf.worker.min.js';

    // Paso 2. El lado mayor de 2400 px es el mismo que usan las fotos de la carta: la letra
    // sigue nítida. Si con eso no entra, se prueba más chico antes de rendirse.
    const PASADAS = [
        { lado: 2400, calidad: 0.85 },
        { lado: 1800, calidad: 0.75 },
        { lado: 1400, calidad: 0.65 },
    ];

    const cargando = {};

    function cargar({ src, integrity }) {
        if (!cargando[src]) {
            cargando[src] = new Promise((resolver, rechazar) => {
                const script = document.createElement('script');
                script.src = src;
                script.integrity = integrity;
                script.crossOrigin = 'anonymous';
                script.onload = resolver;
                script.onerror = () => {
                    delete cargando[src];
                    rechazar(new Error('No se pudo cargar ' + src));
                };
                document.head.appendChild(script);
            });
        }
        return cargando[src];
    }

    // ── Paso 1: sacar lo que no se ve ─────────────────────────────────────────────

    async function limpiar(bytes) {
        await cargar(PDF_LIB);
        const { PDFDocument, PDFName } = window.PDFLib;

        const doc = await PDFDocument.load(bytes, { updateMetadata: false });
        doc.getPages().forEach(pagina => pagina.node.delete(PDFName.of('PieceInfo')));
        doc.catalog.delete(PDFName.of('PieceInfo'));
        borrarHuerfanos(doc.context);

        return doc.save();
    }

    // pdf-lib guarda todos los objetos que leyó, aunque ya nadie los use: lo que colgaba del
    // /PieceInfo seguiría en el archivo. Se marca todo lo que se alcanza desde el catálogo y
    // la info del documento, y el resto se borra.
    function borrarHuerfanos(context) {
        const { PDFRef, PDFDict, PDFArray, PDFStream } = window.PDFLib;
        const usados = new Set();
        const pendientes = [context.trailerInfo.Root, context.trailerInfo.Info];

        while (pendientes.length) {
            const objeto = pendientes.pop();
            if (objeto instanceof PDFRef) {
                if (usados.has(objeto)) continue;
                usados.add(objeto);
                pendientes.push(context.lookup(objeto));
            } else if (objeto instanceof PDFDict) {
                objeto.values().forEach(v => pendientes.push(v));
            } else if (objeto instanceof PDFArray) {
                objeto.asArray().forEach(v => pendientes.push(v));
            } else if (objeto instanceof PDFStream) {
                pendientes.push(objeto.dict);
            }
        }

        context.enumerateIndirectObjects().forEach(([ref]) => {
            if (!usados.has(ref)) context.delete(ref);
        });
    }

    // ── Paso 2: cada página, una imagen ───────────────────────────────────────────

    async function pasarAImagenes(bytes, { lado, calidad }, alAvanzar) {
        await Promise.all([cargar(PDF_LIB), cargar(PDF_JS)]);
        const pdfjsLib = window.pdfjsLib;
        pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_JS_WORKER;

        // pdf.js se queda con el buffer que recibe (se lo pasa a su worker): va una copia.
        const origen = await pdfjsLib.getDocument({ data: bytes.slice() }).promise;
        const nuevo = await window.PDFLib.PDFDocument.create();

        try {
            for (let n = 1; n <= origen.numPages; n++) {
                alAvanzar(`Achicando el PDF… página ${n} de ${origen.numPages}`);

                const pagina = await origen.getPage(n);
                const tamano = pagina.getViewport({ scale: 1 });
                const vista = pagina.getViewport({ scale: lado / Math.max(tamano.width, tamano.height) });

                const canvas = document.createElement('canvas');
                canvas.width = Math.round(vista.width);
                canvas.height = Math.round(vista.height);
                const ctx = canvas.getContext('2d');
                // El JPEG no tiene transparencia: sin fondo, lo transparente sale negro.
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                await pagina.render({ canvasContext: ctx, viewport: vista }).promise;

                const jpeg = await new Promise(r => canvas.toBlob(r, 'image/jpeg', calidad));
                const imagen = await nuevo.embedJpg(await jpeg.arrayBuffer());

                // La hoja conserva su tamaño en puntos: se ve igual de grande que la original.
                nuevo.addPage([tamano.width, tamano.height])
                    .drawImage(imagen, { x: 0, y: 0, width: tamano.width, height: tamano.height });

                pagina.cleanup();
                // En iPhone la memoria de los canvas tiene tope y no se libera sola.
                canvas.width = canvas.height = 0;
            }
        } finally {
            origen.destroy();
        }

        return nuevo.save();
    }

    // ──────────────────────────────────────────────────────────────────────────────

    window.achicarPdf = async function (file, { maxBytes, alAvanzar = () => {} }) {
        const comoArchivo = bytes => new File([bytes], file.name, { type: 'application/pdf', lastModified: Date.now() });

        alAvanzar('Achicando el PDF…');
        const original = new Uint8Array(await file.arrayBuffer());
        let base = original;

        // Si pdf-lib no puede abrirlo (por ejemplo, un PDF con contraseña), queda el paso 2:
        // pdf.js lee más variantes.
        try {
            const limpio = await limpiar(original);
            if (limpio.length <= maxBytes) return { file: comoArchivo(limpio), como: 'limpio' };
            if (limpio.length < original.length) base = limpio;
        } catch (e) {
            console.warn('achicarPdf: no se pudo limpiar, se pasa a imágenes', e);
        }

        for (const pasada of PASADAS) {
            const imagenes = await pasarAImagenes(base, pasada, alAvanzar);
            if (imagenes.length <= maxBytes) return { file: comoArchivo(imagenes), como: 'imagenes' };
        }

        return null;
    };
})();
