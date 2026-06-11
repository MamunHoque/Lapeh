<form method="POST" action="{{ route('admin.settings.update', 'fcm') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_fcm') }}
            @include('admin.settings.partials.pill', ['state'=>$s->get('fcm.credentials_path')?'ok':'off', 'label'=>$s->get('fcm.credentials_path')?__('admin.configured'):__('admin.not_configured')])
        </h3>
        <p>{{ __('admin.fcm_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.fcm_project_id') }}</label>
                <input type="text" name="project_id" value="{{ $s->get('fcm.project_id') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.fcm_credentials_path') }}</label>
                <input type="text" name="credentials_path" value="{{ $s->get('fcm.credentials_path') }}" class="form-input" placeholder="storage/app/firebase.json">
                <p class="field-help">{{ __('admin.fcm_path_help') }}</p>
            </div>
        </div>
        <div class="help-box">{{ __('admin.fcm_console_help') }}</div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
        <button type="submit" form="fcm-test" class="btn btn-ghost">{{ __('admin.send_test_push') }}</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.settings.test.push') }}" id="fcm-test" style="display:none;">@csrf</form>
