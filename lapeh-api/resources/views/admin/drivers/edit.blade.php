@extends('admin.layout')
@section('title', __('admin.edit').' '.__('admin.driver'))

@section('content')
<div style="max-width:600px;">
    <div style="margin-bottom:18px;"><a href="{{ route('admin.drivers') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_drivers') }}</a></div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.edit_prefix') }} {{ $driver->user->name }}</h3></div>
        <form method="POST" action="{{ route('admin.drivers.update', $driver) }}" style="padding:24px;">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.vehicle_type') }}</label>
                    <select name="vehicle_type" required class="form-input form-select">
                        <option value="bike" @selected($driver->vehicle_type==='bike')>{{ __('admin.bike') }}</option>
                        <option value="car" @selected($driver->vehicle_type==='car')>{{ __('admin.car') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.plate_number') }}</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.fleet') }}</label>
                    <select name="fleet_id" class="form-input form-select">
                        <option value="">{{ __('admin.independent') }}</option>
                        @foreach($fleets as $fleet)
                            <option value="{{ $fleet->id }}" @selected($driver->fleet_id == $fleet->id)>{{ $fleet->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.status') }}</label>
                    <select name="status" required class="form-input form-select">
                        <option value="online" @selected($driver->status==='online')>{{ __('admin.online') }}</option>
                        <option value="offline" @selected($driver->status==='offline')>{{ __('admin.offline') }}</option>
                        <option value="on_delivery" @selected($driver->status==='on_delivery')>{{ __('admin.on_delivery') }}</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:24px;">
                    <input type="checkbox" name="is_verified" value="1" id="verified" @checked($driver->is_verified) style="width:18px;height:18px;accent-color:var(--pink);">
                    <label for="verified" class="form-label" style="margin:0;">{{ __('admin.mark_verified') }}</label>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <a href="{{ route('admin.drivers') }}" class="btn btn-ghost">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
