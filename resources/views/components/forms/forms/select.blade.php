@props(['label', 'name'])
@php
    $defaults = [
        'id' => $name,
        'name' => $name,
        'class' => 'rounded-xl bg-white/10 border-white/10 px-5 py-4 w-full'
    ]
@endphp

<x-form.field :$label :$name>
    <select {{ $aattribute($defaults)}}>
        {{ $slot}}
    </select>
</x-form.field>