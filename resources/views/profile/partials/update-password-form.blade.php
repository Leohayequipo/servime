<section>
    <header>
        <h2 class="text-lg font-medium text-text-primary">
            Actualizar Contraseña
        </h2>

        <p class="mt-1 text-sm text-text-secondary">
            Asegúrate de que tu cuenta use una contraseña larga y aleatoria para mantenerte seguro.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="'Contraseña Actual'" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full px-3 py-2" autocomplete="current-password" />
            @if($errors->updatePassword->get('current_password'))
                <div class="mt-2 bg-red-100 border-2 border-red-500 p-2 rounded">
                    <p class="text-red-800 font-bold">⚠️ {{ $errors->updatePassword->get('current_password')[0] }}</p>
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="update_password_password" :value="'Nueva Contraseña'" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full px-3 py-2" autocomplete="new-password" />
            @if($errors->updatePassword->get('password'))
                <div class="mt-2 bg-red-100 border-2 border-red-500 p-2 rounded">
                    <p class="text-red-800 font-bold">⚠️ {{ $errors->updatePassword->get('password')[0] }}</p>
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="'Confirmar Contraseña'" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full px-3 py-2" autocomplete="new-password" />
            @if($errors->updatePassword->get('password_confirmation'))
                <div class="mt-2 bg-red-100 border-2 border-red-500 p-2 rounded">
                    <p class="text-red-800 font-bold">⚠️ {{ $errors->updatePassword->get('password_confirmation')[0] }}</p>
                </div>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-6">
            <x-primary-button class="w-auto max-w-[280px] sm:max-w-none sm:w-auto px-8 py-3 text-base">Guardar</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-text-secondary"
                >¡Guardado!</p>
            @endif
        </div>
    </form>
</section>

