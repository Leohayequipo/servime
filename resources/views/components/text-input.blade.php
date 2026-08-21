@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-accent-blue focus:ring-accent-blue rounded-md shadow-sm']) }}>

