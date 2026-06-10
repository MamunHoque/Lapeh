@extends('admin.layout')
@section('title', __('admin.activity_log'))
@section('content')

@php
    // Action → badge tone, derived from the action suffix.
    $toneFor = function (string $action) {
        return match (true) {
            str_contains($action, 'deleted'), str_contains($action, 'cancelled'), str_contains($action, 'rejected') => 'red',
            str_contains($action, 'created'), str_contains($action, 'delivered'), str_contains($action, 'paid'), str_contains($action, 'accepted'), str_contains($action, 'register') => 'green',
            str_contains($action, 'updated'), str_contains($action, 'confirmed'), str_contains($action, 'rated'), str_contains($action, 'resent') => 'blue',
            str_contains($action, 'login'), str_contains($action, 'online') => 'indigo',
            str_contains($action, 'logout'), str_contains($action, 'offline') => 'grey',
            default => 'amber',
        };
    };
    $actionLabel = fn (string $a) => \Illuminate\Support\Facades\Lang::has('activity.'.$a) ? __('activity.'.$a) : ucfirst(str_replace(['.', '_'], ' ', $a));
    $roleLabel = fn (?string $r) => $r && \Illuminate\Support\Facades\Lang::has('admin.role_'.$r) ? __('admin.role_'.$r) : ucfirst($r ?? 'system');
    $roleTone = fn (?string $r) => match ($r) { 'admin' => 'pink', 'restaurant' => 'indigo', 'driver' => 'blue', 'customer' => 'amber', default => 'grey' };
@endphp

{{-- Filter bar --}}
<div class="card" style="margin-bottom:16px;">
    <form method="GET" style="padding:16px 18px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
        <div style="flex:1;min-width:200px;">
            <label class="form-label">{{ __('admin.search') }}</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_placeholder') }}" class="form-input">
        </div>
        <div style="width:170px;">
            <label class="form-label">{{ __('admin.action') }}</label>
            <select name="action" class="form-input form-select">
                <option value="">{{ __('admin.all_actions') }}</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" @selected(request('action') === $a)>{{ $actionLabel($a) }}</option>
                @endforeach
            </select>
        </div>
        <div style="width:150px;">
            <label class="form-label">{{ __('admin.role') }}</label>
            <select name="role" class="form-input form-select">
                <option value="">{{ __('admin.all_roles') }}</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" @selected(request('role') === $r)>{{ $roleLabel($r) }}</option>
                @endforeach
            </select>
        </div>
        <div style="width:140px;">
            <label class="form-label">{{ __('admin.date_from') }}</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div style="width:140px;">
            <label class="form-label">{{ __('admin.date_to') }}</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('admin.filter') }}</button>
        @if(request()->hasAny(['search','action','role','from','to']))
            <a href="{{ route('admin.activity-logs') }}" class="btn btn-ghost">{{ __('admin.reset') }}</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="card-head">
        <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.activity_log') }}</h3>
        <span style="font-size:12.5px;color:var(--slate);">{{ __('admin.results_count', ['count' => $logs->total()]) }}</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('admin.time') }}</th>
                    <th>{{ __('admin.actor') }}</th>
                    <th>{{ __('admin.action') }}</th>
                    <th>{{ __('admin.subject') }}</th>
                    <th>{{ __('admin.details') }}</th>
                    <th>{{ __('admin.ip_address') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:12px;color:var(--slate-2);white-space:nowrap;">{{ $log->created_at?->timezone('Asia/Dubai')->format('d M Y · H:i') }}</td>
                    <td>
                        <div style="font-size:13px;font-weight:600;">{{ $log->user?->name ?? ($log->meta['name'] ?? __('admin.system')) }}</div>
                        <span class="badge badge-{{ $roleTone($log->actor_role) }}" style="font-size:10px;margin-top:2px;">{{ $roleLabel($log->actor_role) }}</span>
                    </td>
                    <td><span class="badge badge-{{ $toneFor($log->action) }}">{{ $actionLabel($log->action) }}</span></td>
                    <td style="font-size:12.5px;">
                        @if($log->subject_type === 'Order' && $log->subject_id)
                            <a href="{{ url('/admin/orders/'.$log->subject_id) }}" class="mono">{{ $log->meta['order_no'] ?? ('Order #'.$log->subject_id) }}</a>
                        @elseif($log->subject_type === 'Complaint' && $log->subject_id)
                            <a href="{{ url('/admin/complaints/'.$log->subject_id) }}" style="color:var(--pink);text-decoration:none;">Complaint #{{ $log->subject_id }}</a>
                        @elseif($log->subject_type)
                            <span style="color:var(--slate);">{{ $log->subject_type }} #{{ $log->subject_id }}</span>
                        @else
                            <span style="color:var(--slate-2);">—</span>
                        @endif
                    </td>
                    <td style="font-size:11.5px;color:var(--slate);max-width:280px;">
                        @php($meta = collect($log->meta ?? [])->except(['order_no', 'name']))
                        @if($meta->isNotEmpty())
                            {{ $meta->map(fn($v, $k) => str_replace('_', ' ', $k).': '.(is_array($v) ? json_encode($v) : $v))->implode(' · ') }}
                        @else
                            <span style="color:var(--slate-2);">—</span>
                        @endif
                    </td>
                    <td style="font-size:11.5px;color:var(--slate-2);">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_activity') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $logs->links() }}</div>
</div>
@endsection
