@extends('admin.layout')
@section('title', __('admin.driver_ratings'))
@section('content')
<div class="card">
    <table class="table">
        <thead><tr><th>{{ __('admin.order') }}</th><th>{{ __('admin.driver') }}</th><th>{{ __('admin.sender') }}</th><th>{{ __('admin.rating') }}</th><th>{{ __('admin.tags') }}</th><th>{{ __('admin.date') }}</th></tr></thead>
        <tbody>
            @forelse($ratings as $rating)
            <tr>
                <td><span class="mono">{{ $rating->order?->order_no }}</span></td>
                <td style="font-size:13px;font-weight:600;">{{ $rating->driver?->user?->name }}</td>
                <td style="font-size:12.5px;">{{ $rating->sender?->displayName() }}</td>
                <td>
                    @for($i=1;$i<=5;$i++)<span style="color:{{ $i <= $rating->rating ? '#E08600' : '#DDD' }}">★</span>@endfor
                </td>
                <td>
                    @foreach((array)$rating->tags as $tag)
                        <span class="badge badge-blue" style="font-size:10px;margin-inline-end:3px;">{{ config('lapeh.rating_tags.'.$tag.'.'.app()->getLocale()) ?? str_replace('_',' ',$tag) }}</span>
                    @endforeach
                </td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $rating->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_ratings') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $ratings->links() }}</div>
</div>
@endsection
