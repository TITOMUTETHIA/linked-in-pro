@props(['label', 'name'])
@php
    $default = [
        'type' => 'text',
        'id' => $name,
        'name' => $name,
        'class' => 'round-xl bg-white/10 border-white/10 px-5 py-4 w-full',
        'value' => old($name)
    ]
@endphp

<x-form class="field">
    <input {{ $attributes($defaults)}}>
</x-form>