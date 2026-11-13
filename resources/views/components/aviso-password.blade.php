{{--
    Recordatorio de que la cuenta todavía tiene la contraseña inicial.

    Aparece en cada pantalla del panel mientras `debe_cambiar_password` esté prendido y
    desaparece solo cuando la persona elige una propia. No bloquea nada: se decidió
    avisar y no obligar, para no sumar un paso forzoso más a quien recién entra.

    Mismo criterio que <x-flash />: el texto va en tinta y el color semántico queda en
    el borde y el ícono. Ver la skill `identidad`.
--}}
@auth
    @if(auth()->user()->debe_cambiar_password)
        <div class="bg-warning/10 border-b border-warning/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="flex-none w-6 h-6 rounded-full bg-warning/15 text-warning grid place-items-center text-sm font-bold" aria-hidden="true">!</span>
                <p class="flex-1 min-w-[14rem] text-sm text-text-primary">
                    <span class="font-semibold">Estás usando la contraseña inicial.</span>
                    Es la misma para varias cuentas: elegí una propia para proteger la tuya.
                </p>
                <a href="{{ route('password.cambiar') }}"
                   class="flex-none text-sm font-semibold underline underline-offset-2 text-text-primary hover:no-underline">
                    Elegir mi contraseña
                </a>
            </div>
        </div>
    @endif
@endauth
