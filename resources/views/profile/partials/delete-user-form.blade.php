<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-text-primary">
            Eliminar Cuenta
        </h2>

        <p class="mt-1 text-sm text-text-secondary">
            Si deseas eliminar tu cuenta, por favor ponte en contacto con el administrador del sistema.
        </p>
    </header>

    <div class="flex justify-center mt-6">
        <x-danger-button
            type="button"
            onclick="showContactAdmin()"
            class="w-auto max-w-[280px] sm:max-w-none sm:w-auto px-8 py-3 text-base"
        >Eliminar Cuenta</x-danger-button>
    </div>

    <script>
        function showContactAdmin() {
            const email = '{{ env("SUPPORT_EMAIL", "info.servime@gmail.com") }}';
            const whatsapp = '{{ env("SUPPORT_WHATSAPP", "5492257526706") }}';
            
            // Los colores van por tokens, como en el resto de los modales. Antes estaban
            // escritos a mano y eran de la etapa oscura: el cuerpo en #e5e7eb daba 1,24:1
            // sobre el blanco del modal —texto invisible— y los links en #60a5fa, 2,54:1.
            // No los salvaba el `!important` de `.dark-modal .swal2-html-container`: ese
            // pinta el contenedor, y un `color` inline en un hijo le gana igual.
            // Acá funciona `rgb(var(--x))` porque son estilos inline y el navegador los
            // resuelve contra el :root del documento.
            let htmlContent = `
                <div style="text-align: left;">
                    <p style="margin-bottom: 15px;">
                        Para eliminar tu cuenta, necesitas contactar con el administrador del sistema.
                        Por favor, envía un correo electrónico o mensaje con tu solicitud.
                    </p>
                    <div style="background: rgb(var(--c-elev-1)); border-left: 4px solid rgb(var(--c-accent)); padding: 12px; border-radius: 6px; margin-top: 15px;">
                        <p style="margin-bottom: 8px;">
                            <strong>📧 Email:</strong><br>
                            <a href="mailto:${email}" style="color: rgb(var(--c-accent-deep)); text-decoration: underline;">${email}</a>
                        </p>
                        ${whatsapp ? `
                        <p>
                            <strong>💬 WhatsApp:</strong><br>
                            <a href="https://wa.me/${whatsapp}" target="_blank" style="color: rgb(var(--c-accent-deep)); text-decoration: underline;">${whatsapp}</a>
                        </p>
                        ` : ''}
                    </div>
                </div>
            `;

            Swal.fire({
                background: 'rgb(var(--c-elev-2))',
                color: 'rgb(var(--c-ink))',
                customClass: {
                    // Sin `title: 'text-white'`: era blanco sobre el blanco del modal.
                    // Hoy no se nota porque `.dark-modal .swal2-title` lo pisa con
                    // !important, pero es una trampa esperando a que alguien saque ese
                    // !important o el modal pierda la clase. El color lo pone el CSS.
                    popup: 'dark-modal',
                    htmlContainer: 'text-text-secondary',
                },
                title: 'Contacta con el Administrador',
                html: htmlContent,
                icon: 'info',
                iconColor: 'rgb(var(--c-accent))',
                confirmButtonText: 'Cerrar',
                confirmButtonColor: 'rgb(var(--c-accent))',
                width: '500px'
            });
        }
    </script>
</section>

