@extends('admin.layout')
@section('title', __('admin.reports'))
@section('content')
@php
    $senderTotal = (float) ($commission->sender_total ?? 0);
    $driverTotal = (float) ($commission->driver_total ?? 0);
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:18px;">
    <div class="kpi" style="background:linear-gradient(135deg,var(--pink),var(--pink-deep));">
        <div style="font-size:12px;opacity:.85;">{{ __('admin.commission_revenue') }}</div>
        <div class="sora" style="font-size:24px;font-weight:800;margin-top:6px;">AED {{ number_format($senderTotal + $driverTotal, 2) }}</div>
    </div>
    <div class="kpi" style="background:linear-gradient(135deg,#3457D5,#1E3A8A);">
        <div style="font-size:12px;opacity:.85;">{{ __('admin.commission_from_senders') }}</div>
        <div class="sora" style="font-size:24px;font-weight:800;margin-top:6px;">AED {{ number_format($senderTotal, 2) }}</div>
    </div>
    <div class="kpi" style="background:linear-gradient(135deg,#7C5CFC,#4C1D95);">
        <div style="font-size:12px;opacity:.85;">{{ __('admin.commission_from_drivers') }}</div>
        <div class="sora" style="font-size:24px;font-weight:800;margin-top:6px;">AED {{ number_format($driverTotal, 2) }}</div>
    </div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;">
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.daily_orders_30') }}</h3></div>
        <table class="table">
            <thead><tr><th>{{ __('admin.date') }}</th><th>{{ __('admin.orders') }}</th><th>{{ __('admin.delivered') }}</th><th>{{ __('admin.cancelled') }}</th><th>{{ __('admin.revenue') }}</th><th>{{ __('admin.commission') }}</th></tr></thead>
            <tbody>
                @foreach($daily as $row)
                <tr>
                    <td style="font-size:12.5px;">{{ $row->date }}</td>
                    <td>{{ $row->orders }}</td>
                    <td><span class="badge badge-green">{{ $row->delivered }}</span></td>
                    <td><span class="badge badge-grey">{{ $row->cancelled }}</span></td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;">AED {{ number_format($row->revenue, 2) }}</span></td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;color:var(--pink);">AED {{ number_format($row->commission, 2) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card">
        <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.top_drivers') }}</h3></div>
        <table class="table">
            <thead><tr><th>{{ __('admin.driver') }}</th><th>{{ __('admin.deliveries') }}</th><th>{{ __('admin.earnings') }}</th></tr></thead>
            <tbody>
                @forelse($topDrivers as $row)
                <tr>
                    <td style="font-size:13px;font-weight:600;">{{ $row->driver?->user?->name ?? '—' }}</td>
                    <td>{{ $row->deliveries }}</td>
                    <td><span class="sora" style="font-weight:600;color:var(--pink);">AED {{ number_format($row->earnings, 2) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
