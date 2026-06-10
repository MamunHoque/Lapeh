@extends('admin.layout')
@section('title', __('admin.dashboard'))

@section('content')
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
    <div class="kpi" style="background:linear-gradient(135deg,var(--pink),var(--pink-deep));">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;opacity:.9;text-transform:uppercase;">{{ __('admin.orders_today') }}</div>
        <div class="sora" style="font-size:30px;font-weight:800;margin:10px 0 2px;">{{ $stats['orders_today'] }}</div>
        <div style="font-size:11.5px;opacity:.9;">{{ __('admin.this_month', ['count' => $stats['orders_month']]) }}</div>
    </div>
    <div class="kpi" style="background:linear-gradient(135deg,#0E9E6E,#07825A);">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;opacity:.9;text-transform:uppercase;">{{ __('admin.delivered_today') }}</div>
        <div class="sora" style="font-size:30px;font-weight:800;margin:10px 0 2px;">{{ $stats['delivered_today'] }}</div>
        <div style="font-size:11.5px;opacity:.9;">{{ __('admin.revenue_today', ['amount' => number_format($stats['revenue_today'], 2)]) }}</div>
    </div>
    <div class="kpi" style="background:linear-gradient(135deg,#3457D5,#1E3AA8);">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;opacity:.9;text-transform:uppercase;">{{ __('admin.online_drivers') }}</div>
        <div class="sora" style="font-size:30px;font-weight:800;margin:10px 0 2px;">{{ $stats['online_drivers'] }}</div>
        <div style="font-size:11.5px;opacity:.9;">{{ __('admin.on_delivery_count', ['count' => $stats['on_delivery']]) }}</div>
    </div>
    <div class="kpi" style="background:linear-gradient(135deg,#7C5CFC,#5A3CDA);">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;opacity:.9;text-transform:uppercase;">{{ __('admin.restaurants') }}</div>
        <div class="sora" style="font-size:30px;font-weight:800;margin:10px 0 2px;">{{ $stats['total_restaurants'] }}</div>
        <div style="font-size:11.5px;opacity:.9;">{{ __('admin.open_complaints_count', ['count' => $stats['open_complaints']]) }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;">
    <div>
        <div class="card">
            <div class="card-head">
                <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.live_deliveries') }}</h3>
                <a href="{{ route('admin.live') }}" style="color:var(--pink);font-size:12.5px;font-weight:600;text-decoration:none;">{{ __('admin.view_all') }}</a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('admin.order') }}</th>
                        <th>{{ __('admin.restaurant') }}</th>
                        <th>{{ __('admin.customer') }}</th>
                        <th>{{ __('admin.driver') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.fee') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($liveOrders as $order)
                    <tr>
                        <td><span class="mono">{{ $order->order_no }}</span></td>
                        <td style="font-size:12.5px;">{{ $order->restaurant->name }}</td>
                        <td style="font-size:12.5px;">{{ $order->customer_name }}</td>
                        <td style="font-size:12.5px;">{{ $order->driver?->user?->name ?? '—' }}</td>
                        <td>@include('admin.partials.status-badge', ['status' => $order->status])</td>
                        <td><span class="sora" style="font-weight:600;">{{ $order->delivery_fee ? 'AED '.number_format($order->delivery_fee,2) : '—' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_active_deliveries') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-head">
                <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.recent_orders') }}</h3>
                <a href="{{ route('admin.orders') }}" style="color:var(--pink);font-size:12.5px;font-weight:600;text-decoration:none;">{{ __('admin.view_all') }}</a>
            </div>
            <table class="table">
                <thead>
                    <tr><th>{{ __('admin.order') }}</th><th>{{ __('admin.restaurant') }}</th><th>{{ __('admin.customer') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.amount') }}</th><th>{{ __('admin.time') }}</th></tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="mono">{{ $order->order_no }}</a></td>
                        <td style="font-size:12.5px;">{{ $order->restaurant->name }}</td>
                        <td style="font-size:12.5px;">{{ $order->customer_name }}</td>
                        <td>@include('admin.partials.status-badge', ['status' => $order->status])</td>
                        <td><span class="sora" style="font-weight:600;font-size:13px;">{{ $order->total_amount ? 'AED '.number_format($order->total_amount,2) : '—' }}</span></td>
                        <td style="font-size:12px;color:var(--slate-2);">{{ $order->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:18px;">
            <div class="card-head">
                <h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.quick_actions') }}</h3>
            </div>
            <div style="padding:16px;display:flex;flex-direction:column;gap:10px;">
                <a href="{{ route('admin.restaurants.create') }}" class="btn btn-primary" style="justify-content:center;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('admin.add_restaurant') }}
                </a>
                <a href="{{ route('admin.drivers.create') }}" class="btn btn-ghost" style="justify-content:center;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('admin.add_driver') }}
                </a>
                <a href="{{ route('admin.pricing') }}" class="btn btn-ghost" style="justify-content:center;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ __('admin.edit_pricing') }}
                </a>
                <a href="{{ route('admin.complaints') }}" class="btn" style="justify-content:center;background:var(--amber-s);color:var(--amber);">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    {{ __('admin.open_complaints') }} ({{ $stats['open_complaints'] }})
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.driver_network') }}</h3>
            </div>
            <div style="padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <span style="font-size:13px;color:var(--slate);">{{ __('admin.online') }}</span>
                    <span class="badge badge-green">{{ $stats['online_drivers'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <span style="font-size:13px;color:var(--slate);">{{ __('admin.on_delivery') }}</span>
                    <span class="badge badge-blue">{{ $stats['on_delivery'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--slate);">{{ __('admin.active_drivers') }}</span>
                    <span class="badge badge-indigo">{{ $stats['active_drivers'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
