{{--
    Los datos del comercio que se piden igual al crearlo y al editarlo.

    Estaban escritos dos veces, con markup distinto en cada pantalla: crear usaba
    `input-field` y editar repetía las diez clases de Tailwind que esa clase ya agrupa.
    Cambiar el rótulo de «Dirección» era tocar dos archivos y acordarse del segundo.

    Espera `$comercio`, que es null al crear. Los valores salen de `old()` para que un
    error de validación no borre lo que la persona ya había escrito.
--}}
@props(['comercio' => null])

<div class="md:col-span-2">
    <label for="nombre" class="block text-sm font-medium text-text-secondary mb-2">
        Nombre del comercio <span class="text-danger">*</span>
    </label>
    <input type="text"
           id="nombre"
           name="nombre"
           value="{{ old('nombre', $comercio?->nombre) }}"
           required
           @if(! $comercio) autofocus @endif
           class="input-field">
    <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
</div>

<div class="md:col-span-2">
    <label for="descripcion" class="block text-sm font-medium text-text-secondary mb-2">
        Descripción
    </label>
    <textarea id="descripcion"
              name="descripcion"
              rows="2"
              class="input-field resize-none">{{ old('descripcion', $comercio?->descripcion) }}</textarea>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
</div>

<div>
    <label for="direccion" class="block text-sm font-medium text-text-secondary mb-2">
        Dirección
    </label>
    <input type="text"
           id="direccion"
           name="direccion"
           value="{{ old('direccion', $comercio?->direccion) }}"
           class="input-field">
    <x-input-error :messages="$errors->get('direccion')" class="mt-1" />
</div>

{{-- El texto libre. Los horarios reales (los que encienden el «abierto ahora» del menú)
     van sólo en editar: son 7 días × 2 turnos y no tienen lugar en un alta rápida. --}}
<div>
    <label for="horario" class="block text-sm font-medium text-text-secondary mb-2">
        Horario de atención
    </label>
    <input type="text"
           id="horario"
           name="horario"
           value="{{ old('horario', $comercio?->horario) }}"
           placeholder="Ej: Lunes a Viernes 9:00 - 18:00"
           class="input-field">
    <p class="text-xs text-text-secondary mt-1">Texto libre, como querés que se lea.</p>
    <x-input-error :messages="$errors->get('horario')" class="mt-1" />
</div>
