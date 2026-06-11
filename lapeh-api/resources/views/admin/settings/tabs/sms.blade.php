@php($provider = $s->get('sms.provider', 'log'))
<form method="POST" action="{{ route('admin.settings.update', 'sms') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_sms') }}
            @include('admin.settings.partials.pill', ['state'=> $provider==='log'?'warn':'ok', 'label'=> strtoupper($provider)])
        </h3>
        <p>{{ __('admin.sms_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="form-group">
            <label class="form-label">{{ __('admin.sms_provider') }}</label>
            <select name="provider" class="form-input form-select">
                <option value="log" @selected($provider==='log')>{{ __('admin.sms_log') }}</option>
                <option value="unifonic" @selected($provider==='unifonic')>Unifonic — UAE / MENA</option>
                <option value="infobip" @selected($provider==='infobip')>Infobip — UAE</option>
                <option value="gateway" @selected($provider==='gateway')>{{ __('admin.sms_uae_gateway') }} (Etisalat / Broadnet)</option>
            </select>
            <p class="field-help">{{ __('admin.sms_provider_help') }}</p>
        </div>

        <fieldset style="border:1px solid var(--line);border-radius:12px;padding:6px 16px 0;margin:10px 0;">
            <legend style="font-size:12px;font-weight:700;color:var(--pink);padding:0 6px;">Unifonic</legend>
            <div class="grid-2">
                @include('admin.settings.partials.secret', ['key'=>'sms.unifonic_app_sid', 'label'=>'App SID'])
                <div class="form-group"><label class="form-label">{{ __('admin.sms_sender_id') }}</label><input type="text" name="unifonic_sender_id" value="{{ $s->get('sms.unifonic_sender_id') }}" class="form-input"></div>
                <div class="form-group"><label class="form-label">{{ __('admin.sms_base_url') }}</label><input type="text" name="unifonic_base_url" value="{{ $s->get('sms.unifonic_base_url') }}" class="form-input"></div>
            </div>
        </fieldset>

        <fieldset style="border:1px solid var(--line);border-radius:12px;padding:6px 16px 0;margin:10px 0;">
            <legend style="font-size:12px;font-weight:700;color:var(--pink);padding:0 6px;">Infobip</legend>
            <div class="grid-2">
                @include('admin.settings.partials.secret', ['key'=>'sms.infobip_api_key', 'label'=>'API Key'])
                <div class="form-group"><label class="form-label">{{ __('admin.sms_base_url') }}</label><input type="text" name="infobip_base_url" value="{{ $s->get('sms.infobip_base_url') }}" class="form-input" placeholder="https://xxxxx.api.infobip.com"></div>
                <div class="form-group"><label class="form-label">{{ __('admin.sms_sender_id') }}</label><input type="text" name="infobip_sender_id" value="{{ $s->get('sms.infobip_sender_id') }}" class="form-input"></div>
            </div>
        </fieldset>

        <fieldset style="border:1px solid var(--line);border-radius:12px;padding:6px 16px 0;margin:10px 0;">
            <legend style="font-size:12px;font-weight:700;color:var(--pink);padding:0 6px;">{{ __('admin.sms_uae_gateway') }}</legend>
            <div class="grid-2">
                @include('admin.settings.partials.secret', ['key'=>'sms.gateway_api_key', 'label'=>'API Key'])
                <div class="form-group"><label class="form-label">{{ __('admin.sms_username') }}</label><input type="text" name="gateway_username" value="{{ $s->get('sms.gateway_username') }}" class="form-input"></div>
                <div class="form-group"><label class="form-label">{{ __('admin.sms_sender_id') }}</label><input type="text" name="gateway_sender_id" value="{{ $s->get('sms.gateway_sender_id') }}" class="form-input"></div>
                <div class="form-group"><label class="form-label">{{ __('admin.sms_endpoint') }}</label><input type="text" name="gateway_endpoint" value="{{ $s->get('sms.gateway_endpoint') }}" class="form-input"></div>
            </div>
            <p class="field-help" style="padding-bottom:10px;">{{ __('admin.sms_gateway_help') }}</p>
        </fieldset>

        <div class="help-box">⚠️ {{ __('admin.sms_tra_help') }} · <a href="{{ route('admin.sms') }}" style="color:var(--pink);font-weight:600;">{{ __('admin.sms_templates') }} →</a></div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
    </div>
</form>

<form method="POST" action="{{ route('admin.settings.test.sms') }}" class="settings-form card">
    @csrf
    <div class="settings-section-head"><h3 class="sora">{{ __('admin.send_test_sms') }}</h3><p>{{ __('admin.sms_test_help') }}</p></div>
    <div class="settings-body">
        <div class="form-group" style="max-width:300px;">
            <label class="form-label">{{ __('admin.test_phone') }}</label>
            <input type="text" name="to" value="+971" class="form-input" placeholder="+9715XXXXXXXX" required>
        </div>
    </div>
    <div class="settings-foot"><button type="submit" class="btn btn-ghost">{{ __('admin.send_test_sms') }}</button></div>
</form>
