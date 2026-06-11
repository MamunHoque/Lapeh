<form method="POST" action="{{ route('admin.settings.update', 'registration') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_registration') }}</h3>
        <p>{{ __('admin.registration_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.sender_registration') }}
                    @include('admin.settings.partials.pill', ['state'=>$s->get('registration.sender_enabled')?'ok':'red', 'label'=>$s->get('registration.sender_enabled')?__('admin.open'):__('admin.closed')])
                </b>
                <span>{{ __('admin.sender_registration_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="sender_enabled" value="1" @checked($s->get('registration.sender_enabled'))><span class="track"></span></label>
        </div>
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.driver_registration') }}
                    @include('admin.settings.partials.pill', ['state'=>$s->get('registration.driver_enabled')?'ok':'red', 'label'=>$s->get('registration.driver_enabled')?__('admin.open'):__('admin.closed')])
                </b>
                <span>{{ __('admin.driver_registration_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="driver_enabled" value="1" @checked($s->get('registration.driver_enabled'))><span class="track"></span></label>
        </div>
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.require_otp') }}</b>
                <span>{{ __('admin.require_otp_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="require_otp" value="1" @checked($s->get('registration.require_otp'))><span class="track"></span></label>
        </div>
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.sender_approval') }}</b>
                <span>{{ __('admin.approval_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="sender_requires_approval" value="1" @checked($s->get('registration.sender_requires_approval'))><span class="track"></span></label>
        </div>
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.driver_approval') }}</b>
                <span>{{ __('admin.approval_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="driver_requires_approval" value="1" @checked($s->get('registration.driver_requires_approval'))><span class="track"></span></label>
        </div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
    </div>
</form>
