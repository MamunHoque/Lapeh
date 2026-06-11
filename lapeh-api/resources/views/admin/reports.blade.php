@extends('admin.layout')
@section('title', __('admin.reports'))
@section('content')
@php
    $delivered = (int) ($overview->delivered ?? 0);
    $cancelled = (int) ($overview->cancelled ?? 0);
    $total = (int) ($overview->total ?? 0);
    $finished = $delivered + $cancelled;
    $successRate = $finished > 0 ? round($delivered / $finished * 100, 1) : 0;
    $grossFees = (float) ($overview->gross_fees ?? 0);
    $commission = (float) ($overview->commission ?? 0);
    $driverNet = (float) ($overview->driver_net ?? 0);
    $orderValue = (float) ($overview->order_value ?? 0);
    $collected = (float) ($payments->collected ?? 0);
    $maxRevenue = collect($daily)->max('revenue') ?: 1;
    $exportQuery = http_build_query(request()->only(['range', 'from', 'to']));
@endphp

{{-- Date range scope --}}
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <form method="GET" class="admin-toolbar">
        <x-admin.date-range/>
        <button type="submit" class="btn btn-primary admin-toolbar-btn">{{ __('admin.apply') }}</button>
        @if(request()->hasAny(['range','from','to']))
            <a href="{{ route('admin.reports') }}" class="btn btn-ghost admin-toolbar-btn">{{ __('admin.clear') }}</a>
        @endif
    </form>
    <span style="font-size:12.5px;color:var(--slate);">{{ __('admin.reports_scope') }}: <b style="color:var(--ink);">{{ request('range') ? __('admin.dr_'.request('range')) : __('admin.dr_all_time') }}</b></span>
</div>

{{-- KPI grid --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_orders'), 'value'=>number_format($total), 'sub'=>$delivered.' '.__('admin.delivered').' · '.$cancelled.' '.__('admin.cancelled'), 'grad'=>'linear-gradient(135deg,#3457D5,#1E3A8A)'])
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_success'), 'value'=>$successRate.'%', 'sub'=>__('admin.kpi_success_sub'), 'grad'=>'linear-gradient(135deg,#0E9E6E,#0A6B4A)'])
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_gross_fees'), 'value'=>'AED '.number_format($grossFees,2), 'sub'=>__('admin.kpi_gross_sub'), 'grad'=>'linear-gradient(135deg,var(--pink),var(--pink-deep))'])
    @include('admin.partials.kpi', ['label'=>__('admin.commission_revenue'), 'value'=>'AED '.number_format($commission,2), 'sub'=>__('admin.kpi_commission_sub'), 'grad'=>'linear-gradient(135deg,#7C5CFC,#4C1D95)'])
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_driver_net'), 'value'=>'AED '.number_format($driverNet,2), 'sub'=>__('admin.kpi_driver_net_sub'), 'grad'=>'linear-gradient(135deg,#14192B,#283149)'])
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_collected'), 'value'=>'AED '.number_format($collected,2), 'sub'=>(int)($payments->paid_count ?? 0).' '.__('admin.kpi_payments'), 'grad'=>'linear-gradient(135deg,#E08600,#A35F00)'])
</div>

{{-- Secondary metrics --}}
<div class="card">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
        @include('admin.partials.metric', ['label'=>__('admin.avg_fee'), 'value'=>'AED '.number_format((float)($overview->avg_fee ?? 0),2)])
        @include('admin.partials.metric', ['label'=>__('admin.avg_distance'), 'value'=>number_format((float)($overview->avg_distance ?? 0),1).' km'])
        @include('admin.partials.metric', ['label'=>__('admin.order_value_label'), 'value'=>'AED '.number_format($orderValue,2)])
        @include('admin.partials.metric', ['label'=>__('admin.pending'), 'value'=>'AED '.number_format((float)($payments->pending ?? 0),2)])
        @include('admin.partials.metric', ['label'=>__('admin.failed'), 'value'=>(int)($payments->failed_count ?? 0)])
        @include('admin.partials.metric', ['label'=>__('admin.refunded'), 'value'=>'AED '.number_format((float)($payments->refunded ?? 0),2)])
    </div>
</div>

{{-- Gateway split --}}
<div class="card" style="padding:18px;">
    <h3 class="sora" style="font-size:14px;font-weight:700;margin-bottom:12px;">{{ __('admin.gateway_breakdown') }}</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="background:var(--bg);border-radius:12px;padding:14px;">
            <div style="font-size:12px;color:var(--slate);">Stripe</div>
            <div class="sora" style="font-size:20px;font-weight:800;color:var(--ink);">AED {{ number_format((float)($payments->stripe_total ?? 0),2) }}</div>
        </div>
        <div style="background:var(--bg);border-radius:12px;padding:14px;">
            <div style="font-size:12px;color:var(--slate);">Telr</div>
            <div class="sora" style="font-size:20px;font-weight:800;color:var(--ink);">AED {{ number_format((float)($payments->telr_total ?? 0),2) }}</div>
        </div>
    </div>
</div>

{{-- Daily trend --}}
<div class="card">
    <div class="card-head">
        <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.daily_orders_30') }}</h3>
        <a href="{{ route('admin.reports.export','daily') }}?{{ $exportQuery }}" class="btn btn-ghost">{{ __('admin.export_csv') }}</a>
    </div>
    @if(collect($daily)->isNotEmpty())
    <div style="display:flex;align-items:flex-end;gap:6px;height:120px;padding:18px 18px 0;overflow-x:auto;">
        @foreach(collect($daily)->reverse() as $row)
            <div style="flex:1;min-width:14px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;" title="{{ $row->date }}: AED {{ number_format($row->revenue,2) }}">
                <div style="width:100%;background:linear-gradient(180deg,var(--pink),var(--pink-deep));border-radius:5px 5px 0 0;height:{{ max(3, round(($row->revenue / $maxRevenue) * 96)) }}px;"></div>
            </div>
        @endforeach
    </div>
    @endif
    <div style="overflow-x:auto;">
        <table class="table">
            <thead><tr><th>{{ __('admin.date') }}</th><th>{{ __('admin.orders') }}</th><th>{{ __('admin.delivered') }}</th><th>{{ __('admin.cancelled') }}</th><th>{{ __('admin.revenue') }}</th><th>{{ __('admin.commission') }}</th></tr></thead>
            <tbody>
                @forelse($daily as $row)
                <tr>
                    <td style="font-size:12.5px;">{{ $row->date }}</td>
                    <td>{{ $row->orders }}</td>
                    <td><span class="badge badge-green">{{ $row->delivered }}</span></td>
                    <td><span class="badge badge-grey">{{ $row->cancelled }}</span></td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;">AED {{ number_format($row->revenue, 2) }}</span></td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;color:var(--pink);">AED {{ number_format($row->commission, 2) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;">
    {{-- Top drivers --}}
    <div class="card">
        <div class="card-head">
            <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.top_drivers') }}</h3>
            <a href="{{ route('admin.reports.export','drivers') }}?{{ $exportQuery }}" class="btn btn-ghost">{{ __('admin.export_csv') }}</a>
        </div>
        <div style="overflow-x:auto;">
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

    {{-- Top senders --}}
    <div class="card">
        <div class="card-head">
            <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.top_senders') }}</h3>
            <a href="{{ route('admin.reports.export','senders') }}?{{ $exportQuery }}" class="btn btn-ghost">{{ __('admin.export_csv') }}</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead><tr><th>{{ __('admin.sender') }}</th><th>{{ __('admin.orders') }}</th><th>{{ __('admin.revenue') }}</th></tr></thead>
                <tbody>
                    @forelse($topSenders as $row)
                    <tr>
                        <td style="font-size:13px;font-weight:600;">{{ $row->sender?->displayName() ?? '—' }}</td>
                        <td>{{ $row->delivered }}/{{ $row->orders }}</td>
                        <td><span class="sora" style="font-weight:600;">AED {{ number_format($row->fees, 2) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--slate-2);padding:30px;">{{ __('admin.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
