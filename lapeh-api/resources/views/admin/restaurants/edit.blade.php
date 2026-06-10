@extends('admin.layout')
@section('title', __('admin.edit').' '.__('admin.restaurant'))

@section('content')
<div style="max-width:700px;">
    <div style="margin-bottom:18px;">
        <a href="{{ route('admin.restaurants') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_restaurants') }}</a>
    </div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.edit_prefix') }} {{ $restaurant->name }}</h3></div>
        <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}" style="padding:24px;">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.name_en') }}</label>
                    <input type="text" name="name" value="{{ old('name', $restaurant->name) }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.name_ar') }}</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $restaurant->name_ar) }}" class="form-input" dir="rtl">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $restaurant->phone) }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.area') }}</label>
                    <input type="text" name="area" value="{{ old('area', $restaurant->area) }}" required class="form-input">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">{{ __('admin.address') }}</label>
                    <input type="text" name="address" value="{{ old('address', $restaurant->address) }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.latitude') }}</label>
                    <input type="number" name="lat" value="{{ old('lat', $restaurant->lat) }}" required step="any" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.longitude') }}</label>
                    <input type="number" name="lng" value="{{ old('lng', $restaurant->lng) }}" required step="any" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.zone') }}</label>
                    <select name="zone_id" class="form-input form-select">
                        <option value="">{{ __('admin.no_zone') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('zone_id', $restaurant->zone_id) == $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.status') }}</label>
                    <select name="status" class="form-input form-select">
                        <option value="active" @selected($restaurant->status === 'active')>{{ __('admin.active') }}</option>
                        <option value="inactive" @selected($restaurant->status === 'inactive')>{{ __('admin.inactive') }}</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <a href="{{ route('admin.restaurants') }}" class="btn btn-ghost">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
