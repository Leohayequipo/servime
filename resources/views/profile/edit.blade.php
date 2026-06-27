<x-app-layout>
    <div class="min-h-screen bg-background">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

            <div class="mb-2">
                <h1 class="text-3xl font-bold text-text-primary">Mi perfil</h1>
                <p class="text-text-secondary mt-1">Gestioná tu información, contraseña y cuenta</p>
            </div>

            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
