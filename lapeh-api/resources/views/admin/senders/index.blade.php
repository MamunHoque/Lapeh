@extends('admin.layout')
@section('title', __('admin.senders'))

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_sender_placeholder') }}" class="form-input" style="width:240px;">
        <button type="submit" class="btn btn-ghost">{{ __('admin.search') }}</button>
    </form>
    <a href="{{ route('admin.senders.create') }}" class="btn btn-primary">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('admin.add_sender') }}
    </a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr><th>{{ __('admin.name') }}</th><th>{{ __('admin.sender_type') }}</th><th>{{ __('admin.phone') }}</th><th>{{ __('admin.default_pickup') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.created') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($senders as $sender)
            <tr>
                <td>
                    <div style="font-size:13.5px;font-weight:600;">{{ $sender->displayName() }}</div>
                    @if($sender->isBusiness() && $sender->business_category)
                        <div style="font-size:12px;color:var(--slate);">{{ $sender->business_category }}</div>
                    @endif
                </td>
                <td><span class="badge {{ $sender->isBusiness() ? 'badge-indigo' : 'badge-grey' }}">{{ $sender->isBusiness() ? __('admin.business') : __('admin.individual') }}</span></td>
                <td style="font-size:13px;">{{ $sender->user?->phone }}</td>
                <td style="font-size:12px;color:var(--slate);max-width:220px;">{{ $sender->default_pickup_address ?? '—' }}</td>
                <td><span class="badge {{ $sender->status === 'active' ? 'badge-green' : ($sender->status === 'pending' ? 'badge-amber' : 'badge-grey') }}">{{ __('admin.'.$sender->status) }}</span></td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $sender->created_at->format('d M Y') }}</td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('admin.senders.edit', $sender) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">{{ __('admin.edit') }}</a>
                        <form method="POST" action="{{ route('admin.senders.destroy', $sender) }}" onsubmit="return confirm('{{ __('admin.confirm_delete_sender') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:6px 12px;font-size:12px;">{{ __('admin.delete') }}</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_senders_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $senders->links() }}</div>
</div>
@endsection
