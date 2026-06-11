@props(['action' => null, 'search' => null])
{{-- Responsive GET filter bar for admin list pages. Place search/selects and
    <x-admin.date-range/> inside; a Filter button is appended automatically. --}}
<form method="GET" action="{{ $action }}" class="admin-toolbar">
    @if($search !== null)
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $search }}"
               class="form-input admin-toolbar-search">
    @endif
    {{ $slot }}
    <button type="submit" class="btn btn-primary admin-toolbar-btn">{{ __('admin.filter') }}</button>
    @if(request()->hasAny(['search','status','role','type','range','from','to','sender_id']))
        <a href="{{ url()->current() }}" class="btn btn-ghost admin-toolbar-btn">{{ __('admin.clear') }}</a>
    @endif
</form>
