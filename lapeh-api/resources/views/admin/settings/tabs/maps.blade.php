<form method="POST" action="{{ route('admin.settings.update', 'maps') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_maps') }}</h3>
        <p>{{ __('admin.maps_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            @include('admin.settings.partials.secret', ['key'=>'maps.server_key', 'label'=>__('admin.maps_server_key'), 'help'=>__('admin.maps_server_help')])
            <div class="form-group">
                <label class="form-label">{{ __('admin.maps_client_key') }}</label>
                <input type="text" name="client_key" value="{{ $s->get('maps.client_key') }}" class="form-input">
                <p class="field-help">{{ __('admin.maps_client_help') }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.default_map_lat') }}</label>
                <input type="number" step="any" name="default_lat" value="{{ $s->get('maps.default_lat') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.default_map_lng') }}</label>
                <input type="number" step="any" name="default_lng" value="{{ $s->get('maps.default_lng') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.default_country') }}</label>
                <input type="text" name="default_country" value="{{ $s->get('maps.default_country') }}" class="form-input" maxlength="2">
            </div>
        </div>
        <div class="help-box">{{ __('admin.maps_restrict_help') }}</div>
    </div>
    <div class="settings-foot"><button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button></div>
</form>
