@php($isProd = config('app.env') === 'production')
<form method="POST" action="{{ route('admin.settings.update', 'otp') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_otp') }}</h3>
        <p>{{ __('admin.otp_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.otp_length') }}</label>
                <input type="number" name="length" value="{{ $s->get('otp.length') }}" min="4" max="8" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.otp_ttl') }}</label>
                <input type="number" name="ttl_minutes" value="{{ $s->get('otp.ttl_minutes') }}" min="1" class="form-input">
            </div>
            @unless($isProd)
                @include('admin.settings.partials.secret', ['key'=>'otp.master', 'label'=>__('admin.master_otp'), 'help'=>__('admin.master_otp_help')])
            @endunless
        </div>

        @if($isProd)
            <div class="alert alert-error" style="margin-bottom:0;">{{ __('admin.master_otp_prod') }}</div>
        @endif

        <div class="help-box" style="margin-top:16px;">
            <div class="info-row"><span>{{ __('admin.otp_dev_envs') }}</span><span>{{ implode(', ', config('lapeh.otp.dev_envs', [])) }}</span></div>
            <div class="info-row"><span>{{ __('admin.rate_limit') }} — verify-otp</span><span>10 / min</span></div>
            <div class="info-row"><span>{{ __('admin.rate_limit') }} — resend-otp</span><span>5 / min</span></div>
            <div class="info-row"><span>{{ __('admin.session_lifetime') }}</span><span>{{ config('session.lifetime') }} min</span></div>
        </div>
    </div>
    <div class="settings-foot"><button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button></div>
</form>
