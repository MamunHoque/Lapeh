@extends('admin.layout')
@section('title', __('admin.activity_log'))
@section('content')
<div class="card">
    <table class="table">
        <thead><tr><th>{{ __('admin.user') }}</th><th>{{ __('admin.action') }}</th><th>{{ __('admin.subject') }}</th><th>{{ __('admin.time') }}</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td style="font-size:13px;font-weight:600;">{{ $log->user?->name ?? __('admin.system') }}</td>
                <td><span class="mono" style="font-size:11px;color:var(--indigo);">{{ $log->action }}</span></td>
                <td style="font-size:12px;color:var(--slate);">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $log->created_at?->format('d M Y, H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_activity') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $logs->links() }}</div>
</div>
@endsection
