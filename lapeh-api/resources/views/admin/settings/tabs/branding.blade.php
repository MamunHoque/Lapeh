@php
$assetUrl = function ($val) {
    if (!$val) return null;
    return \Illuminate\Support\Str::startsWith($val, ['http://','https://']) ? $val : \Illuminate\Support\Facades\Storage::url($val);
};
$logo = $assetUrl($s->get('branding.logo_url'));
$adminLogo = $assetUrl($s->get('branding.admin_logo_url'));
$favicon = $assetUrl($s->get('branding.favicon_url'));
$color = $s->get('branding.primary_color') ?: '#FB0E72';
$appName = $s->get('general.app_name') ?: 'Lapeh';
@endphp
<form method="POST" action="{{ route('admin.settings.update', 'branding') }}" class="settings-form card" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_branding') }}</h3>
        <p>{{ __('admin.branding_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.app_logo') }}</label>
                @if($logo)<img src="{{ $logo }}" alt="logo" style="height:38px;margin-bottom:8px;border-radius:8px;">@endif
                <input type="file" name="logo_url" accept="image/png,image/svg+xml,image/jpeg,image/webp" class="form-input">
                <p class="field-help">{{ __('admin.logo_help') }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.admin_logo') }}</label>
                @if($adminLogo)<img src="{{ $adminLogo }}" alt="admin logo" style="height:38px;margin-bottom:8px;border-radius:8px;">@endif
                <input type="file" name="admin_logo_url" accept="image/png,image/svg+xml,image/jpeg,image/webp" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.favicon') }}</label>
                @if($favicon)<img src="{{ $favicon }}" alt="favicon" style="height:28px;margin-bottom:8px;">@endif
                <input type="file" name="favicon_url" accept="image/png,image/x-icon,image/svg+xml" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.primary_color') }}</label>
                <div class="color-row">
                    <input type="color" name="primary_color" value="{{ $color }}">
                    <input type="text" value="{{ $color }}" class="form-input" oninput="this.previousElementSibling.value=this.value" style="max-width:140px;">
                </div>
            </div>
        </div>

        <div class="help-box">
            <b>{{ __('admin.live_preview') }}</b>
            <div style="display:flex;gap:18px;margin-top:12px;flex-wrap:wrap;">
                <div style="background:var(--ink);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;color:#fff;">
                    @if($logo)<img src="{{ $logo }}" style="height:24px;">@else<div style="width:26px;height:26px;border-radius:7px;background:{{ $color }};"></div>@endif
                    <b style="font-size:15px;">{{ $appName }}</b>
                </div>
                <div style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:14px 18px;text-align:center;width:150px;">
                    @if($logo)<img src="{{ $logo }}" style="height:30px;">@else<div style="width:34px;height:34px;border-radius:9px;background:{{ $color }};margin:0 auto;"></div>@endif
                    <b style="display:block;margin-top:8px;font-size:13px;">{{ $appName }}</b>
                    <span style="font-size:10px;color:var(--slate);">{{ __('admin.mobile_app') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
    </div>
</form>
