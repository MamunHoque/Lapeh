@extends('admin.layout')
@section('title', __('admin.settings'))
@section('content')
<div style="max-width:600px;">
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.system_settings') }}</h3></div>
        <div style="padding:24px;">
            <p style="color:var(--slate);font-size:14px;">{{ __('admin.settings_env_pre') }} <code style="background:var(--bg);padding:2px 6px;border-radius:5px;font-size:12px;">.env</code> {{ __('admin.settings_env_post') }}</p>
            <div style="margin-top:20px;display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;padding:14px;background:var(--bg);border-radius:10px;">
                    <span style="font-size:13px;font-weight:600;">{{ __('admin.app_environment') }}</span>
                    <span class="badge {{ config('app.env') === 'production' ? 'badge-green' : 'badge-amber' }}">{{ config('app.env') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px;background:var(--bg);border-radius:10px;">
                    <span style="font-size:13px;font-weight:600;">{{ __('admin.timezone') }}</span>
                    <span style="font-size:13px;color:var(--slate);">{{ config('app.timezone') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px;background:var(--bg);border-radius:10px;">
                    <span style="font-size:13px;font-weight:600;">{{ __('admin.queue_driver') }}</span>
                    <span style="font-size:13px;color:var(--slate);">{{ config('queue.default') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:14px;background:var(--bg);border-radius:10px;">
                    <span style="font-size:13px;font-weight:600;">{{ __('admin.broadcast') }}</span>
                    <span style="font-size:13px;color:var(--slate);">{{ config('broadcasting.default') }}</span>
                </div>
            </div>
            <div style="margin-top:20px;">
                <a href="{{ route('admin.pricing') }}" class="btn btn-primary">{{ __('admin.manage_pricing') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
