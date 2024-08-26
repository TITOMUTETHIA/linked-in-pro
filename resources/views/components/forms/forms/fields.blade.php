@props(['label', 'name'])
<div>
    @if ($label)
        <x-form.label :$name :$label />
    @endif
    <div class="mt-1">
        {{ $slot}}
        <x-form.error :error="$error->first($name)" />

    </div>
</div>