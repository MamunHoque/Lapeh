@extends('admin.layout')
@section('title', __('admin.pricing_config'))
@section('content')
<div style="max-width:560px;">
<div class="card">
    <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.delivery_pricing') }}</h3></div>
    <form method="POST" action="{{ route('admin.pricing.update') }}" style="padding:24px;">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">{{ __('admin.base_fee_aed') }}</label>
                <input type="number" name="base_fee" value="{{ old('base_fee', $pricing->base_fee) }}" step="0.01" required class="form-input">
                <p style="font-size:11.5px;color:var(--slate);margin-top:4px;">{{ __('admin.base_fee_help') }}</p>
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
                <p style="font-size:11.5px;color:var(--slate);margin-top:4px;">{{ __('admin.offer_timeout_help') }}</p>
            </div>
        </div>
        <div style="background:var(--bg);border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:13px;color:var(--slate);">
            <b style="color:var(--ink);">{{ __('admin.fee_formula') }}</b> {{ __('admin.fee_formula_body') }}
        </div>
        <button type="submit" class="btn btn-primary">{{ __('admin.save_pricing') }}</button>
    </form>
</div>
</div>
@endsection
