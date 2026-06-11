@extends('admin.layout')
@section('title', __('admin.drivers'))

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <x-admin.toolbar :search="__('admin.search_driver_placeholder')">
        <select name="status" class="form-input form-select admin-toolbar-field">
            <option value="">{{ __('admin.all_statuses') }}</option>
            <option value="online" @selected(request('status')==='online')>{{ __('admin.online') }}</option>
            <option value="offline" @selected(request('status')==='offline')>{{ __('admin.offline') }}</option>
            <option value="on_delivery" @selected(request('status')==='on_delivery')>{{ __('admin.on_delivery') }}</option>
        </select>
        <x-admin.date-range/>
    </x-admin.toolbar>
    <a href="{{ route('admin.drivers.create') }}" class="btn btn-primary">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('admin.add_driver') }}
    </a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr><th>{{ __('admin.driver') }}</th><th>{{ __('admin.vehicle') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.rating') }}</th><th>{{ __('admin.fleet') }}</th><th>{{ __('admin.verified') }}</th><th>{{ __('admin.joined') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($drivers as $driver)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="avatar" style="width:34px;height:34px;font-size:11px;">{{ strtoupper(substr($driver->user->name,0,2)) }}</div>
                        <div>
                            <div style="font-size:13.5px;font-weight:600;">{{ $driver->user->name }}</div>
                            <div style="font-size:12px;color:var(--slate);">{{ $driver->user->phone }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:13px;">{{ __('admin.'.$driver->vehicle_type) }}</div>
                    <div style="font-size:11.5px;color:var(--slate);">{{ $driver->vehicle_plate ?? '—' }}</div>
                </td>
                <td>
                    <span class="badge {{ match($driver->status) { 'online' => 'badge-green', 'on_delivery' => 'badge-blue', default => 'badge-grey' } }}">
                        {{ __('admin.'.$driver->status) }}
                    </span>
                </td>
                <td>
                    <span style="font-size:13px;font-weight:600;">{{ $driver->rating_avg }} ★</span>
                    <span style="font-size:11.5px;color:var(--slate-2);"> ({{ $driver->rating_count }})</span>
                </td>
                <td style="font-size:12.5px;">{{ $driver->fleet?->company_name ?? __('admin.independent') }}</td>
                <td>
                    <span class="badge {{ $driver->is_verified ? 'badge-green' : 'badge-amber' }}">
                        {{ $driver->is_verified ? __('admin.verified') : __('admin.pending') }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $driver->created_at->format('d M Y') }}</td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('admin.drivers.edit', $driver) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">{{ __('admin.edit') }}</a>
                        <form method="POST" action="{{ route('admin.drivers.destroy', $driver) }}" onsubmit="return confirm('{{ __('admin.confirm_delete_driver') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:12px;">{{ __('admin.delete') }}</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_drivers_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $drivers->links() }}</div>
</div>
@endsection
