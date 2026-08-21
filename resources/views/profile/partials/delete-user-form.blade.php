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
            
            let htmlContent = `
                <div style="text-align: left; color: #e5e7eb;">
                    <p style="margin-bottom: 15px;">
                        Para eliminar tu cuenta, necesitas contactar con el administrador del sistema. 
                        Por favor, envía un correo electrónico o mensaje con tu solicitud.
                    </p>
                    <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3B82F6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                        <p style="margin-bottom: 8px;">
                            <strong>📧 Email:</strong><br>
                            <a href="mailto:${email}" style="color: #60a5fa; text-decoration: underline;">${email}</a>
                        </p>
                        ${whatsapp ? `
                        <p>
                            <strong>💬 WhatsApp:</strong><br>
                            <a href="https://wa.me/${whatsapp}" target="_blank" style="color: #60a5fa; text-decoration: underline;">${whatsapp}</a>
                        </p>
                        ` : ''}
                    </div>
                </div>
            `;

            Swal.fire({
                background: 'rgb(var(--c-elev-2))',
                color: 'rgb(var(--c-ink))',
                customClass: {
                    popup: 'dark-modal',
                    title: 'text-white',
                    htmlContainer: 'text-gray-300',
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

