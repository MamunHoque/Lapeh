@extends('admin.layout')
@section('title', __('admin.complaints'))
@section('content')
<div style="display:flex;gap:10px;margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;">
        <select name="status" class="form-input form-select" style="width:160px;">
            <option value="">{{ __('admin.all_statuses') }}</option>
            <option value="open" @selected(request('status')==='open')>{{ __('admin.open') }}</option>
            <option value="under_review" @selected(request('status')==='under_review')>{{ __('admin.under_review') }}</option>
            <option value="resolved" @selected(request('status')==='resolved')>{{ __('admin.resolved') }}</option>
        </select>
        <button type="submit" class="btn btn-ghost">{{ __('admin.filter') }}</button>
    </form>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>{{ __('admin.restaurant') }}</th><th>{{ __('admin.order') }}</th><th>{{ __('admin.type') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.created') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($complaints as $complaint)
            <tr>
                <td style="font-size:13.5px;font-weight:600;">{{ $complaint->restaurant->name }}</td>
                <td><span class="mono">{{ $complaint->order?->order_no ?? '—' }}</span></td>
                <td><span class="badge badge-amber">{{ config('lapeh.complaint_types.'.$complaint->type.'.'.app()->getLocale()) ?? ucfirst(str_replace('_',' ',$complaint->type)) }}</span></td>
                <td><span class="badge {{ match($complaint->status) { 'open' => 'badge-red', 'under_review' => 'badge-amber', 'resolved' => 'badge-green' } }}">{{ __('admin.'.$complaint->status) }}</span></td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $complaint->created_at->format('d M Y') }}</td>
                <td><a href="{{ route('admin.complaints.show', $complaint) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">{{ __('admin.review') }}</a></td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_complaints_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $complaints->links() }}</div>
</div>
@endsection
