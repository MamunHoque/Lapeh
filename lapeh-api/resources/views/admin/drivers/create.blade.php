@extends('admin.layout')
@section('title', __('admin.add_driver'))

@section('content')
<div style="max-width:600px;">
    <div style="margin-bottom:18px;"><a href="{{ route('admin.drivers') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_drivers') }}</a></div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.new_driver') }}</h3></div>
        <form method="POST" action="{{ route('admin.drivers.store') }}" style="padding:24px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.full_name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="form-input" placeholder="+971 50 000 0000">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.password') }}</label>
                    <input type="password" name="password" required class="form-input" placeholder="{{ __('admin.min_6_chars') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.vehicle_type') }}</label>
                    <select name="vehicle_type" required class="form-input form-select">
                        <option value="bike" @selected(old('vehicle_type')==='bike')>{{ __('admin.bike') }}</option>
                        <option value="car" @selected(old('vehicle_type')==='car')>{{ __('admin.car') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.plate_number') }}</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" class="form-input" placeholder="A 12345">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.fleet_optional') }}</label>
                    <select name="fleet_id" class="form-input form-select">
                        <option value="">{{ __('admin.independent') }}</option>
                        @foreach($fleets as $fleet)
                            <option value="{{ $fleet->id }}" @selected(old('fleet_id') == $fleet->id)>{{ $fleet->company_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <a href="{{ route('admin.drivers') }}" class="btn btn-ghost">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.create_driver') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
