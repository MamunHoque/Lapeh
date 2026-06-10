@extends('admin.layout')
@section('title', __('admin.orders'))

@section('content')
<div class="card">
    <div class="card-head">
        <h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.orders') }}</h3>
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_order_placeholder') }}" class="form-input" style="width:220px;">
            <select name="status" class="form-input form-select" style="width:180px;">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach(['waiting_for_location','location_confirmed','waiting_for_payment','paid','searching_driver','driver_assigned','arrived_at_pickup','picked_up','on_the_way','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('admin.st_'.$s) }}</option>
                @endforeach
            </select>
            <select name="sender_id" class="form-input form-select" style="width:180px;">
                <option value="">{{ __('admin.all_senders') }}</option>
                @foreach($senders as $s)
                    <option value="{{ $s->id }}" @selected(request('sender_id') == $s->id)>{{ $s->displayName() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">{{ __('admin.filter') }}</button>
        </form>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('admin.order_no') }}</th>
                    <th>{{ __('admin.sender') }}</th>
                    <th>{{ __('admin.customer') }}</th>
                    <th>{{ __('admin.driver') }}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th>{{ __('admin.payment') }}</th>
                    <th>{{ __('admin.fee') }}</th>
                    <th>{{ __('admin.time') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><span class="mono">{{ $order->order_no }}</span></td>
                    <td style="font-size:12.5px;">{{ $order->sender?->displayName() }}</td>
                    <td>
                        <div style="font-size:13px;">{{ $order->customer_name }}</div>
                        <div style="font-size:11px;color:var(--slate-2);">{{ $order->customer_phone }}</div>
                    </td>
                    <td style="font-size:12.5px;">{{ $order->driver?->user?->name ?? '—' }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $order->status])</td>
                    <td>
                        <span class="badge {{ $order->payment_status === 'paid' ? 'badge-green' : 'badge-amber' }}">
                            {{ __('admin.'.$order->payment_status) }}
                        </span>
                    </td>
                    <td><span class="sora" style="font-weight:600;font-size:13px;">{{ $order->delivery_fee ? 'AED '.number_format($order->delivery_fee,2) : '—' }}</span></td>
                    <td style="font-size:12px;color:var(--slate-2);">{{ $order->created_at->format('d M, H:i') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            @include('admin.partials.copy-link', ['link' => url('/c/'.$order->location_token), 'iconOnly' => true])
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-ghost" style="padding:6px 12px;font-size:12px;">{{ __('admin.view') }}</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;color:var(--slate-2);padding:40px;">{{ __('admin.no_orders_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px 18px;border-top:1px solid var(--line);">
        {{ $orders->links() }}
    </div>
</div>
@endsection
