@props(['user' => null, 'inline' => false, 'size' => 10])
@php
    use App\Support\Presence;

    $state  = Presence::stateOf($user);
    $colour = Presence::colour($user);
    $label  = Presence::label($user);
@endphp
{{-- Nothing at all when nobody has ever been recorded: a grey dot would say
     we looked and they were out, and we never looked. --}}
@if($colour)
    <span {{ $attributes->merge([
              'class' => 'pres-dot ' . ($inline ? 'is-inline ' : '') . 'is-' . $state,
              'style' => '--pres: ' . $colour . '; --pres-size: ' . $size . 'px;',
          ]) }}
          title="{{ $label }}" role="img" aria-label="{{ $label }}"></span>
@endif
