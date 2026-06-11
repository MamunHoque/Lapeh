@extends('admin.layout')
@section('title', __('admin.sms_templates_logs'))
@section('content')
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;">
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.sms_templates') }}</h3></div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead><tr><th>{{ __('admin.key') }}</th><th>{{ __('admin.english') }}</th><th>{{ __('admin.arabic') }}</th></tr></thead>
                <tbody>
                    @forelse($templates as $tmpl)
                    <tr>
                        <td><span class="mono" style="font-size:11px;">{{ $tmpl->key }}</span></td>
                        <td style="font-size:12px;max-width:200px;">{{ Str::limit($tmpl->content_en, 60) }}</td>
                        <td style="font-size:12px;max-width:200px;" dir="rtl">{{ Str::limit($tmpl->content_ar, 60) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_templates') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.recent_sms_logs') }}</h3></div>
        <div style="padding:14px 18px 0;">
            <x-admin.toolbar :search="__('admin.search_sms_placeholder')">
                <select name="status" class="form-input form-select admin-toolbar-field">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    <option value="sent" @selected(request('status')==='sent')>{{ __('admin.sent') }}</option>
                    <option value="failed" @selected(request('status')==='failed')>{{ __('admin.failed') }}</option>
                </select>
                <x-admin.date-range/>
            </x-admin.toolbar>
        </div>
        <table class="table">
            <thead><tr><th>{{ __('admin.to') }}</th><th>{{ __('admin.template') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.time') }}</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:12.5px;">{{ $log->to }}</td>
                    <td><span class="mono" style="font-size:11px;">{{ $log->template_key }}</span></td>
                    <td><span class="badge {{ $log->status === 'sent' ? 'badge-green' : 'badge-red' }}">{{ $log->status === 'sent' ? __('admin.sent') : __('admin.failed') }}</span></td>
                    <td style="font-size:12px;color:var(--slate-2);">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
