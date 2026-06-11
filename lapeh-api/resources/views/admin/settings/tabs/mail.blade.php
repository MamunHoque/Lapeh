<form method="POST" action="{{ route('admin.settings.update', 'mail') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_mail') }}
            @include('admin.settings.partials.pill', ['state'=>$s->has('mail.host')?'ok':'off', 'label'=>$s->has('mail.host')?__('admin.configured'):__('admin.not_configured')])
        </h3>
        <p>{{ __('admin.mail_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_driver') }}</label>
                <select name="mailer" class="form-input form-select">
                    @foreach(['smtp'=>'SMTP','log'=>'Log (dev)'] as $v=>$lbl)
                        <option value="{{ $v }}" @selected($s->get('mail.mailer')===$v)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_host') }}</label>
                <input type="text" name="host" value="{{ $s->get('mail.host') }}" class="form-input" placeholder="smtp.gmail.com">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_port') }}</label>
                <input type="number" name="port" value="{{ $s->get('mail.port') }}" class="form-input" placeholder="587">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_encryption') }}</label>
                <select name="encryption" class="form-input form-select">
                    @foreach(['tls'=>'TLS','ssl'=>'SSL',''=>'None'] as $v=>$lbl)
                        <option value="{{ $v }}" @selected($s->get('mail.encryption')===$v)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_username') }}</label>
                <input type="text" name="username" value="{{ $s->get('mail.username') }}" class="form-input" autocomplete="off">
            </div>
            @include('admin.settings.partials.secret', ['key'=>'mail.password', 'label'=>__('admin.mail_password')])
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_from_address') }}</label>
                <input type="email" name="from_address" value="{{ $s->get('mail.from_address') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.mail_from_name') }}</label>
                <input type="text" name="from_name" value="{{ $s->get('mail.from_name') }}" class="form-input">
            </div>
        </div>
        <div class="help-box">{{ __('admin.mail_providers_help') }}</div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
        <button type="submit" form="mail-test-form" class="btn btn-ghost">{{ __('admin.send_test_email') }}</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.settings.test.email') }}" id="mail-test-form" style="display:none;">@csrf</form>
