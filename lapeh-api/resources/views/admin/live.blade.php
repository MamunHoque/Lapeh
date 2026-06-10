@extends('admin.layout')
@section('title', __('admin.live_deliveries'))

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
        <span class="badge badge-green" style="font-size:12px;">● {{ __('admin.live') }}</span>
        <span style="font-size:13px;color:var(--slate);margin-inline-start:8px;">{{ __('admin.auto_refresh') }}</span>
    </div>
    <div style="display:flex;gap:10px;">
        <span class="badge badge-blue">{{ __('admin.active_orders_count', ['count' => $orders->count()]) }}</span>
        <span class="badge badge-indigo">{{ __('admin.online_drivers_count', ['count' => $drivers->count()]) }}</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start;">
    <div class="card">
        <div class="card-head">
            <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.active_orders') }}</h3>
        </div>
        <table class="table">
            <thead>
                <tr><th>{{ __('admin.order_no') }}</th><th>{{ __('admin.sender') }}</th><th>{{ __('admin.customer') }}</th><th>{{ __('admin.driver') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.distance') }}</th><th>{{ __('admin.fee') }}</th></tr>
            </thead>
            <tbody id="live-orders">
                @forelse($orders as $order)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="mono">{{ $order->order_no }}</a></td>
                    <td style="font-size:12.5px;">{{ $order->sender?->displayName() }}</td>
                    <td style="font-size:12.5px;">{{ $order->customer_name }}</td>
                    <td style="font-size:12.5px;">{{ $order->driver?->user?->name ?? '—' }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $order->status])</td>
                    <td style="font-size:12.5px;">{{ $order->distance_km ? $order->distance_km.' km' : '—' }}</td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;">{{ $order->delivery_fee ? 'AED '.number_format($order->delivery_fee,2) : '—' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_active_orders') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-head">
            <h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.online_drivers') }}</h3>
        </div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:10px;">
            @forelse($drivers as $driver)
            <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg);border-radius:10px;">
                <div class="avatar" style="font-size:11px;width:34px;height:34px;">{{ strtoupper(substr($driver->user->name,0,2)) }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;">{{ $driver->user->name }}</div>
                    <div style="font-size:11.5px;color:var(--slate);">{{ __('admin.'.$driver->vehicle_type) }}</div>
                </div>
                <span class="badge {{ $driver->status === 'on_delivery' ? 'badge-blue' : 'badge-green' }}">
                    {{ $driver->status === 'on_delivery' ? __('admin.delivering') : __('admin.online') }}
                </span>
            </div>
            @empty
            <p style="text-align:center;color:var(--slate-2);padding:20px;">{{ __('admin.no_online_drivers') }}</p>
            @endforelse
        </div>
    </div>
</div>

<script>
setTimeout(() => location.reload(), 15000);
</script>
@endsection
