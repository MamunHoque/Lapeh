@extends('admin.layout')
@section('title', __('admin.complaint'))
@section('content')
<div style="max-width:700px;">
    <div style="margin-bottom:18px;"><a href="{{ route('admin.complaints') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_complaints') }}</a></div>
    <div class="card" style="margin-bottom:18px;">
        <div class="card-head">
            <div>
                <div style="font-size:14px;font-weight:600;">{{ $complaint->restaurant->name }}</div>
                <span class="badge badge-amber" style="margin-top:4px;">{{ config('lapeh.complaint_types.'.$complaint->type.'.'.app()->getLocale()) ?? ucfirst(str_replace('_',' ',$complaint->type)) }}</span>
            </div>
            <span class="badge {{ match($complaint->status) { 'open' => 'badge-red', 'under_review' => 'badge-amber', 'resolved' => 'badge-green' } }}">{{ __('admin.'.$complaint->status) }}</span>
        </div>
        <div style="padding:20px;">
            <p style="font-size:14px;margin-bottom:16px;">{{ $complaint->description }}</p>
            @if($complaint->attachments->count())
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                    @foreach($complaint->attachments as $att)
                        <a href="{{ asset('storage/'.$att->path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$att->path) }}" style="width:100px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--line);">
                        </a>
                    @endforeach
                </div>
            @endif
            @if($complaint->resolution_note)
                <div style="background:var(--green-s);border-radius:10px;padding:12px;font-size:13.5px;color:var(--green);">
                    <b>{{ __('admin.resolution') }}</b> {{ $complaint->resolution_note }}
                    @if($complaint->resolver) <span style="color:var(--slate);">— {{ $complaint->resolver->name }}</span> @endif
                </div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.update_status') }}</h3></div>
        <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" style="padding:20px;">
            @csrf @method('PATCH')
            <div class="form-group">
                <label class="form-label">{{ __('admin.status') }}</label>
                <select name="status" required class="form-input form-select">
                    <option value="open" @selected($complaint->status==='open')>{{ __('admin.open') }}</option>
                    <option value="under_review" @selected($complaint->status==='under_review')>{{ __('admin.under_review') }}</option>
                    <option value="resolved" @selected($complaint->status==='resolved')>{{ __('admin.resolved') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.resolution_note') }}</label>
                <textarea name="resolution_note" class="form-input" rows="3" placeholder="{{ __('admin.describe_resolution') }}">{{ old('resolution_note', $complaint->resolution_note) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('admin.update') }}</button>
        </form>
    </div>
</div>
@endsection
