{{-- Masked secret input. Never echoes the stored value. Props: $key, $label, $help (optional) --}}
@php($configured = $s->has($key))
<div class="form-group">
    <label class="form-label">{{ $label }}
        @if($configured)
            <span class="badge badge-green" style="margin-inline-start:6px;">✓ {{ __('admin.configured') }}</span>
        @else
            <span class="badge badge-grey" style="margin-inline-start:6px;">{{ __('admin.not_configured') }}</span>
        @endif
    </label>
    <input type="password" name="{{ explode('.', $key)[1] }}" autocomplete="new-password"
           placeholder="{{ $configured ? '••••••••••••' : __('admin.enter_value') }}" class="form-input">
    <p class="field-help">{{ $help ?? __('admin.secret_help') }}</p>
</div>
