<x-app-layout>
    @php $isNew = !$plan->exists; @endphp
    <div class="min-h-screen bg-background">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="flex items-center justify-between gap-4 mb-10">
                <h1 class="text-3xl font-bold text-text-primary">{{ $isNew ? 'Nuevo plan' : 'Editar plan' }}</h1>
                <a href="{{ route('admin.plans.index') }}" class="btn-secondary">← Planes</a>
            </div>

            @if($errors->any())
                <div class="mb-6 bg-red-900/20 border border-red-500/30 rounded-2xl p-5">
                    <ul class="list-disc list-inside text-red-200 text-sm space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $isNew ? route('admin.plans.store') : route('admin.plans.update', $plan) }}"
                  class="bg-secondary-dark border border-accent-gray rounded-2xl p-6 space-y-5">
                @csrf
                @unless($isNew) @method('PUT') @endunless

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Nombre (marketinero)</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $plan->nombre) }}" class="input-field" required>
                </div>

                @if($isNew)
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Key interna (opcional)</label>
                        <input type="text" name="key" value="{{ old('key') }}" class="input-field" placeholder="se genera del nombre si lo dejás vacío">
                        <p class="text-text-secondary text-xs mt-1">Identificador estable, no se puede cambiar luego.</p>
                    </div>
                @else
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Key interna</label>
                        <input type="text" value="{{ $plan->key }}" class="input-field opacity-60" disabled>
                    </div>
                @endif

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Descripción</label>
                    <input type="text" name="descripcion" value="{{ old('descripcion', $plan->descripcion) }}" class="input-field" maxlength="255">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Precio (informativo)</label>
                        <input type="number" step="0.01" min="0" name="precio" value="{{ old('precio', $plan->precio) }}" class="input-field" placeholder="—">
                    </div>
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Orden</label>
                        <input type="number" min="0" name="orden" value="{{ old('orden', $plan->orden ?? 0) }}" class="input-field">
                    </div>
                </div>

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Mensajes IA por mes</label>
                    <input type="number" min="0" name="mensajes_ia_mes" value="{{ old('mensajes_ia_mes', $plan->mensajes_ia_mes) }}" class="input-field" placeholder="vacío = ilimitado">
                    <p class="text-text-secondary text-xs mt-1">Dejalo vacío para cuota ilimitada.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Modelo IA</label>
                        <select name="modelo_ia" class="input-field">
                            @foreach(['gpt-4o-mini', 'gpt-4o'] as $modelo)
                                <option value="{{ $modelo }}" @selected(old('modelo_ia', $plan->modelo_ia) === $modelo)>{{ $modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-2">Max tokens chat</label>
                        <input type="number" min="50" max="4000" name="max_tokens_chat" value="{{ old('max_tokens_chat', $plan->max_tokens_chat ?? 900) }}" class="input-field" required>
                    </div>
                </div>

                <div>
                    <label class="block text-text-secondary text-xs font-medium uppercase tracking-wide mb-3">Features incluidas</label>
                    @php $featuresActuales = old('features', $plan->features ?? []); @endphp
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 text-text-primary text-sm">
                            <input type="checkbox" name="features[]" value="analytics" @checked(in_array('analytics', $featuresActuales)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                            Analytics (panel de conversaciones)
                        </label>
                        <label class="flex items-center gap-3 text-text-primary text-sm">
                            <input type="checkbox" name="features[]" value="excel" @checked(in_array('excel', $featuresActuales)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                            Exportar / importar menú a Excel
                        </label>
                        <label class="flex items-center gap-3 text-text-primary text-sm">
                            <input type="checkbox" name="features[]" value="promociones" @checked(in_array('promociones', $featuresActuales)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                            Promociones del día
                        </label>
                        <label class="flex items-center gap-3 text-text-primary text-sm">
                            <input type="checkbox" name="features[]" value="intencion_ia" @checked(in_array('intencion_ia', $featuresActuales)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                            Clasificación de intención con IA (analytics)
                        </label>
                    </div>
                </div>

                <label class="flex items-center gap-3 text-text-primary text-sm">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $plan->activo ?? true)) class="rounded border-accent-gray bg-primary-dark text-accent-blue">
                    Plan activo
                </label>

                <div class="pt-2">
                    <button type="submit" class="btn-primary w-full">{{ $isNew ? 'Crear plan' : 'Guardar cambios' }}</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
