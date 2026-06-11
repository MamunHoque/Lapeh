<form method="POST" action="{{ route('admin.settings.update', 'general') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_general') }}</h3>
        <p>{{ __('admin.general_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.app_name') }}</label>
                <input type="text" name="app_name" value="{{ old('app_name', $s->get('general.app_name')) }}" class="form-input" required>
                <p class="field-help">{{ __('admin.app_name_help') }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.tagline') }}</label>
                <input type="text" name="tagline" value="{{ old('tagline', $s->get('general.tagline')) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.default_locale') }}</label>
                <select name="locale" class="form-input form-select">
                    <option value="en" @selected($s->get('general.locale')==='en')>English</option>
                    <option value="ar" @selected($s->get('general.locale')==='ar')>العربية</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.currency') }}</label>
                <input type="text" name="currency" value="{{ old('currency', $s->get('general.currency')) }}" class="form-input" maxlength="3">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.timezone') }}</label>
                <input type="text" name="timezone" value="{{ old('timezone', $s->get('general.timezone')) }}" class="form-input">
                <p class="field-help">{{ __('admin.timezone_help') }}</p>
            </div>
        </div>

        <div class="switch" style="border-top:1px solid var(--line);margin-top:6px;">
            <div class="switch-meta">
                <b>{{ __('admin.maintenance_mode') }}</b>
                <span>{{ __('admin.maintenance_help') }}</span>
            </div>
            <label class="toggle">
                <input type="checkbox" name="maintenance_mode" value="1" @checked($s->get('general.maintenance_mode'))>
                <span class="track"></span>
            </label>
        </div>
        <div class="form-group" style="margin-top:14px;">
            <label class="form-label">{{ __('admin.maintenance_message') }}</label>
            <textarea name="maintenance_message" rows="2" class="form-input">{{ old('maintenance_message', $s->get('general.maintenance_message')) }}</textarea>
        </div>

        <div class="help-box" style="margin-top:8px;display:flex;justify-content:space-between;align-items:center;">
            <span><b>{{ __('admin.pricing_config') }}</b> — {{ __('admin.general_pricing_note') }}</span>
            <a href="{{ route('admin.settings.tab', 'pricing') }}" class="btn btn-ghost">{{ __('admin.manage') }} →</a>
        </div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
    </div>
</form>
