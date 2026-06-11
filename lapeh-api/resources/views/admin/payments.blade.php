@extends('admin.layout')
@section('title', __('admin.payments'))
@section('content')
@php($exportQuery = http_build_query(request()->only(['search','status','range','from','to'])))

{{-- Collection summary --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:18px;">
    @include('admin.partials.kpi', ['label'=>__('admin.kpi_collected'), 'value'=>'AED '.number_format((float)($summary->collected ?? 0),2), 'sub'=>(int)($summary->paid_count ?? 0).' '.__('admin.paid'), 'grad'=>'linear-gradient(135deg,#0E9E6E,#0A6B4A)'])
    @include('admin.partials.kpi', ['label'=>__('admin.pending'), 'value'=>'AED '.number_format((float)($summary->pending ?? 0),2), 'grad'=>'linear-gradient(135deg,#E08600,#A35F00)'])
    @include('admin.partials.kpi', ['label'=>__('admin.failed'), 'value'=>(int)($summary->failed_count ?? 0), 'grad'=>'linear-gradient(135deg,#E03131,#A11)'])
    @include('admin.partials.kpi', ['label'=>__('admin.refunded'), 'value'=>'AED '.number_format((float)($summary->refunded ?? 0),2), 'grad'=>'linear-gradient(135deg,#6B748A,#3B4252)'])
    @include('admin.partials.kpi', ['label'=>'Stripe', 'value'=>'AED '.number_format((float)($summary->stripe_total ?? 0),2), 'grad'=>'linear-gradient(135deg,#635BFF,#3A33B0)'])
    @include('admin.partials.kpi', ['label'=>'Telr', 'value'=>'AED '.number_format((float)($summary->telr_total ?? 0),2), 'grad'=>'linear-gradient(135deg,#14192B,#283149)'])
</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <x-admin.toolbar :search="__('admin.search_payment_placeholder')">
        <select name="status" class="form-input form-select admin-toolbar-field">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach(['pending','paid','failed','refunded'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ __('admin.'.$s) }}</option>
            @endforeach
        </select>
        <x-admin.date-range/>
    </x-admin.toolbar>
    <a href="{{ route('admin.payments.export') }}?{{ $exportQuery }}" class="btn btn-ghost">{{ __('admin.export_csv') }}</a>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>{{ __('admin.order') }}</th><th>{{ __('admin.sender') }}</th><th>{{ __('admin.gateway') }}</th><th>{{ __('admin.amount') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.date') }}</th></tr></thead>
        <tbody>
            @forelse($payments as $payment)
            <tr>
                <td><span class="mono">{{ $payment->order?->order_no }}</span></td>
                <td style="font-size:12.5px;">{{ $payment->order?->sender?->displayName() }}</td>
                <td style="font-size:12.5px;">{{ ucfirst($payment->gateway) }}</td>
                <td><span class="sora" style="font-weight:600;">AED {{ number_format($payment->amount, 2) }}</span></td>
                <td><span class="badge {{ $payment->status === 'paid' ? 'badge-green' : ($payment->status === 'failed' ? 'badge-red' : 'badge-amber') }}">{{ __('admin.'.$payment->status) }}</span></td>
                <td style="font-size:12px;color:var(--slate-2);">{{ $payment->created_at->format('d M Y, H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_payments_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">{{ $payments->links() }}</div>
</div>
@endsection
