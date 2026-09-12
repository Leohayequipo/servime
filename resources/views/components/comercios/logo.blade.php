{{--
    El campo del logo, igual al crear y al editar.

    `$comercio` es null al crear: ahí no hay logo actual que mostrar ni que borrar, y el
    resto del campo es idéntico. El botón de borrar sólo aparece si `$conBorrado` es true
    (la pantalla de edición, que tiene el modal y la función `confirmDeleteLogo`).

    El logo es el isotipo y se recorta cuadrado a 512×512 en `saveSquareLogo()`. La
    portada es otra cosa y vive en la pantalla del menú — ver la skill `portada`.
--}}
@props(['comercio' => null, 'conBorrado' => false])

@if($comercio?->logo_url)
    {{-- onerror: si el archivo ya no está en disco, el <img> roto queda peor que no
         mostrar nada. --}}
    <div class="mb-4" id="logoActual">
        <img src="{{ asset('storage/' . $comercio->logo_url) }}"
             alt="Logo actual"
             onerror="document.getElementById('logoActual').remove()"
             class="w-16 h-16 object-cover rounded-lg border border-accent-gray mx-auto">
    </div>
@endif

{{-- Vista previa de lo que se acaba de elegir, antes de guardar. --}}
<div class="mb-4 hidden" id="logoPreviewWrap">
    <img id="logoPreview" alt="Logo nuevo"
         class="w-16 h-16 object-cover rounded-lg border-2 border-accent-blue mx-auto">
    <p class="text-xs text-accent-blue text-center mt-1">Nuevo — se aplica al guardar</p>
</div>

<div>
    <input type="file"
           id="logo"
           name="logo"
           accept="image/*"
           class="input-field text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-accent-blue file:text-white hover:file:brightness-95">
    <p class="text-xs text-text-secondary mt-1">JPEG, PNG, JPG o GIF. Máximo 2 MB.</p>
    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
</div>
