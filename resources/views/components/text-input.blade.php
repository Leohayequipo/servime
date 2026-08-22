@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-secondary-dark border-accent-gray text-text-primary placeholder:text-text-secondary focus:border-accent-blue focus:ring-accent-blue rounded-md shadow-sm']) }}>
