@php($pricing = $data['pricing'])
<form method="POST" action="{{ route('admin.pricing.update') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.delivery_pricing') }}</h3>
        <p>{{ __('admin.general_pricing_note') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.base_fee_aed') }}</label>
                <input type="number" name="base_fee" value="{{ old('base_fee', $pricing->base_fee) }}" step="0.01" required class="form-input">
                <p class="field-help">{{ __('admin.base_fee_help') }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.per_km_fee_aed') }}</label>
                <input type="number" name="per_km_fee" value="{{ old('per_km_fee', $pricing->per_km_fee) }}" step="0.01" required class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.min_fee_aed') }}</label>
                <input type="number" name="min_fee" value="{{ old('min_fee', $pricing->min_fee) }}" step="0.01" required class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.search_radius') }}</label>
                <input type="number" name="search_radius_km" value="{{ old('search_radius_km', $pricing->search_radius_km) }}" step="0.5" required class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.offer_timeout') }}</label>
                <input type="number" name="request_timeout_sec" value="{{ old('request_timeout_sec', $pricing->request_timeout_sec) }}" required class="form-input">
                <p class="field-help">{{ __('admin.offer_timeout_help') }}</p>
            </div>
        </div>
        <div class="help-box">
            <b>{{ __('admin.fee_formula') }}</b> {{ __('admin.fee_formula_body') }}
        </div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_pricing') }}</button>
    </div>
</form>
