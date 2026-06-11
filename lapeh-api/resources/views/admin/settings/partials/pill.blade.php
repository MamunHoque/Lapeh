{{-- Status pill. Props: $state in [ok, off, warn], $label --}}
@php($state = $state ?? 'off')
<span class="badge {{ ['ok'=>'badge-green','off'=>'badge-grey','warn'=>'badge-amber','red'=>'badge-red'][$state] ?? 'badge-grey' }}">
    @if($state==='ok') ✓ @elseif($state==='warn') ! @endif {{ $label }}
</span>
