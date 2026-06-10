@extends('admin.layout')
@section('title', __('admin.payments'))
@section('content')
<div style="display:flex;gap:10px;margin-bottom:18px;">
    <form method="GET" style="display:flex;gap:10px;">
        <select name="status" class="form-input form-select" style="width:160px;">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach(['pending','paid','failed','refunded'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ __('admin.'.$s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-ghost">{{ __('admin.filter') }}</button>
    </form>
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
