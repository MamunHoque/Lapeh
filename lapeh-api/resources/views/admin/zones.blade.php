@extends('admin.layout')
@section('title', __('admin.zones'))
@section('content')
<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start;">
<div class="card">
    <div class="card-head">
        <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.delivery_zones') }}</h3>
        <x-admin.toolbar :search="__('admin.search_zone_placeholder')">
            <x-admin.date-range/>
        </x-admin.toolbar>
    </div>
    <table class="table">
        <thead><tr><th>{{ __('admin.name') }}</th><th>{{ __('admin.base_fee') }}</th><th>{{ __('admin.per_km') }}</th><th>{{ __('admin.status') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($zones as $zone)
            <tr>
                <td style="font-size:13.5px;font-weight:600;">{{ $zone->name }}</td>
                <td>{{ $zone->base_fee ? 'AED '.$zone->base_fee : '—' }}</td>
                <td>{{ $zone->per_km_fee ? 'AED '.$zone->per_km_fee : '—' }}</td>
                <td><span class="badge {{ $zone->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ __('admin.'.$zone->status) }}</span></td>
                <td>
                    <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}" onsubmit="return confirm('{{ __('admin.confirm_delete_zone') }}')" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:12px;">{{ __('admin.delete') }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_zones') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $zones->links() }}</div>
</div>
<div class="card">
    <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.add_zone') }}</h3></div>
    <form method="POST" action="{{ route('admin.zones.store') }}" style="padding:20px;">
        @csrf
        <div class="form-group"><label class="form-label">{{ __('admin.name') }}</label><input type="text" name="name" required class="form-input" placeholder="Downtown Dubai"></div>
        <div class="form-group"><label class="form-label">{{ __('admin.base_fee_override') }}</label><input type="number" name="base_fee" step="0.01" class="form-input" placeholder="{{ __('admin.blank_for_global') }}"></div>
        <div class="form-group"><label class="form-label">{{ __('admin.per_km_override') }}</label><input type="number" name="per_km_fee" step="0.01" class="form-input" placeholder="{{ __('admin.blank_for_global') }}"></div>
        <div class="form-group"><label class="form-label">{{ __('admin.status') }}</label>
            <select name="status" class="form-input form-select"><option value="active">{{ __('admin.active') }}</option><option value="inactive">{{ __('admin.inactive') }}</option></select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('admin.create_zone') }}</button>
    </form>
</div>
</div>
@endsection
