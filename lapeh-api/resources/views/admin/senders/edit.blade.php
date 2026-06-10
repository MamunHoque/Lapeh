@extends('admin.layout')
@section('title', __('admin.edit').' '.__('admin.sender'))

@section('content')
<div style="max-width:700px;">
    <div style="margin-bottom:18px;">
        <a href="{{ route('admin.senders') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_senders') }}</a>
    </div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.edit_prefix') }} {{ $sender->displayName() }}</h3></div>
        <form method="POST" action="{{ route('admin.senders.update', $sender) }}" style="padding:24px;" x-data="{ type: '{{ old('type', $sender->type) }}' }">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.sender_type') }}</label>
                    <select name="type" x-model="type" class="form-input form-select">
                        <option value="individual" @selected($sender->type === 'individual')>{{ __('admin.individual') }}</option>
                        <option value="business" @selected($sender->type === 'business')>{{ __('admin.business') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.status') }}</label>
                    <select name="status" class="form-input form-select">
                        <option value="active" @selected($sender->status === 'active')>{{ __('admin.active') }}</option>
                        <option value="inactive" @selected($sender->status === 'inactive')>{{ __('admin.inactive') }}</option>
                        <option value="pending" @selected($sender->status === 'pending')>{{ __('admin.pending') }}</option>
                    </select>
                </div>
            </div>

            <div x-show="type === 'business'" style="border-top:1px solid var(--line);margin:8px 0 16px;padding-top:16px;">
                <h4 class="sora" style="font-size:14px;font-weight:700;margin-bottom:14px;">{{ __('admin.business_details') }}</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.business_name') }}</label>
                        <input type="text" name="business_name" value="{{ old('business_name', $sender->business_name) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.business_category') }}</label>
                        <input type="text" name="business_category" value="{{ old('business_category', $sender->business_category) }}" class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">{{ __('admin.contact_person') }}</label>
                        <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $sender->contact_person_name) }}" class="form-input">
                    </div>
                </div>
            </div>

            <div style="border-top:1px solid var(--line);margin:8px 0 16px;padding-top:16px;">
                <h4 class="sora" style="font-size:14px;font-weight:700;margin-bottom:14px;">{{ __('admin.default_pickup') }}</h4>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.address') }}</label>
                    <input type="text" name="default_pickup_address" value="{{ old('default_pickup_address', $sender->default_pickup_address) }}" class="form-input">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.latitude') }}</label>
                        <input type="number" name="default_pickup_lat" value="{{ old('default_pickup_lat', $sender->default_pickup_lat) }}" step="any" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('admin.longitude') }}</label>
                        <input type="number" name="default_pickup_lng" value="{{ old('default_pickup_lng', $sender->default_pickup_lng) }}" step="any" class="form-input">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('admin.senders') }}" class="btn btn-ghost">{{ __('admin.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
