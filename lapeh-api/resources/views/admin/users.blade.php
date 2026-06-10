@extends('admin.layout')
@section('title', __('admin.users'))
@section('content')
<div style="display:flex;gap:10px;margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_placeholder') }}" class="form-input" style="width:220px;">
        <select name="role" class="form-input form-select" style="width:150px;">
            <option value="">{{ __('admin.all_roles') }}</option>
            @foreach(['admin','sender','driver','fleet'] as $r)
                <option value="{{ $r }}" @selected(request('role')===$r)>{{ __('admin.role_'.$r) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-ghost">{{ __('admin.filter') }}</button>
    </form>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>{{ __('admin.name') }}</th><th>{{ __('admin.phone') }}</th><th>{{ __('admin.role') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.joined') }}</th></tr></thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="avatar" style="width:32px;height:32px;font-size:11px;">{{ strtoupper(substr($user->name,0,2)) }}</div>
                        <div>
                            <div style="font-size:13.5px;font-weight:600;">{{ $user->name }}</div>
                            <div style="font-size:12px;color:var(--slate);">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size:13px;">{{ $user->phone }}</td>
                <td><span class="badge {{ $user->role === 'admin' ? 'badge-pink' : ($user->role === 'driver' ? 'badge-blue' : 'badge-indigo') }}">{{ __('admin.role_'.$user->role) }}</span></td>
                <td><span class="badge {{ $user->status === 'active' ? 'badge-green' : 'badge-red' }}">{{ __('admin.'.$user->status) }}</span></td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $user->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_users_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $users->links() }}</div>
</div>
@endsection
