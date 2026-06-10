@extends('admin.layout')
@section('title', __('admin.add_restaurant'))

@section('content')
<div style="max-width:700px;">
    <div style="margin-bottom:18px;">
        <a href="{{ route('admin.restaurants') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_restaurants') }}</a>
    </div>

    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.new_restaurant') }}</h3></div>
        <form method="POST" action="{{ route('admin.restaurants.store') }}" style="padding:24px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.name_en') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Al Safadi">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.name_ar') }}</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar') }}" class="form-input" dir="rtl" placeholder="الصفدي">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required class="form-input" placeholder="+971 4 000 0000">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.area') }}</label>
                    <input type="text" name="area" value="{{ old('area') }}" required class="form-input" placeholder="Jumeirah">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">{{ __('admin.address') }}</label>
                    <input type="text" name="address" value="{{ old('address') }}" required class="form-input" placeholder="123 Beach Road, Jumeirah">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.latitude') }}</label>
                    <input type="number" name="lat" value="{{ old('lat') }}" required step="any" class="form-input" placeholder="25.2048">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.longitude') }}</label>
                    <input type="number" name="lng" value="{{ old('lng') }}" required step="any" class="form-input" placeholder="55.2708">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.zone') }}</label>
                    <select name="zone_id" class="form-input form-select">
                        <option value="">{{ __('admin.no_zone') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('zone_id') == $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="border-top:1px solid var(--line);margin:20px 0;padding-top:20px;">
                <h4 class="sora" style="font-size:14px;font-weight:700;margin-bottom:16px;">{{ __('admin.login_account') }}</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.contact_name') }}</label>
                        <input type="text" name="user_name" value="{{ old('user_name') }}" required class="form-input" placeholder="Manager Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.phone_login') }}</label>
                        <input type="text" name="user_phone" value="{{ old('user_phone') }}" required class="form-input" placeholder="+971 50 000 0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.password') }}</label>
                        <input type="password" name="user_password" required class="form-input" placeholder="{{ __('admin.min_6_chars') }}">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('admin.restaurants') }}" class="btn btn-ghost">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.create_restaurant') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
