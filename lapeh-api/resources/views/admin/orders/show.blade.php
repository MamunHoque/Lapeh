@extends('admin.layout')
@section('title', $order->order_no)

@section('content')
<div style="margin-bottom:18px;">
    <a href="{{ route('admin.orders') }}" style="color:var(--slate);font-size:13px;text-decoration:none;">{{ __('admin.back_to_orders') }}</a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start;">
    <div>
        <div class="card">
            <div class="card-head">
                <div>
                    <span class="mono" style="font-size:16px;">{{ $order->order_no }}</span>
                    <div style="margin-top:4px;">@include('admin.partials.status-badge', ['status' => $order->status])</div>
                </div>
                <div style="text-align:end;font-size:13px;color:var(--slate);">
                    {{ $order->created_at->format('d M Y, H:i') }}
                </div>
            </div>
            @php($customerLink = url('/c/'.$order->location_token))
            <div style="padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">{{ __('admin.customer_link') }}</div>
                    <a href="{{ $customerLink }}" target="_blank" style="font-size:12.5px;color:var(--pink);text-decoration:none;word-break:break-all;">{{ $customerLink }}</a>
                </div>
                @include('admin.partials.copy-link', ['link' => $customerLink])
            </div>
            <div style="padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                <div>
                    <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('admin.sender') }}</div>
                    <div style="font-size:14px;font-weight:600;">{{ $order->sender?->displayName() }}</div>
                    <div style="font-size:12.5px;color:var(--slate);">{{ $order->sender?->isBusiness() ? __('admin.business') : __('admin.individual') }}</div>
                    @if($order->pickup_address)
                        <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin:8px 0 2px;">{{ __('admin.pickup') }}</div>
                        <div style="font-size:12.5px;color:var(--slate);">{{ $order->pickup_address }}</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('admin.customer') }}</div>
                    <div style="font-size:14px;font-weight:600;">{{ $order->customer_name }}</div>
                    <div style="font-size:12.5px;color:var(--slate);">{{ $order->customer_phone }}</div>
                    @if($order->customer_address)
                        <div style="font-size:12.5px;color:var(--slate);">{{ $order->customer_address }}</div>
                    @endif
                </div>
                @if($order->driver)
                <div>
                    <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('admin.driver') }}</div>
                    <div style="font-size:14px;font-weight:600;">{{ $order->driver->user->name }}</div>
                    <div style="font-size:12.5px;color:var(--slate);">{{ $order->driver->user->phone }}</div>
                    <div style="font-size:12.5px;color:var(--slate);">{{ __('admin.'.$order->driver->vehicle_type) }} · {{ $order->driver->vehicle_plate }}</div>
                </div>
                @endif
                <div>
                    <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">{{ __('admin.amounts') }}</div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:var(--slate);">{{ __('admin.order_value') }}</span>
                        <span class="sora" style="font-weight:600;">AED {{ number_format($order->order_value, 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:var(--slate);">{{ __('admin.distance') }}</span>
                        <span>{{ $order->distance_km ? $order->distance_km.' km' : '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:var(--slate);">{{ __('admin.delivery_fee') }}</span>
                        <span class="sora" style="font-weight:600;">{{ $order->delivery_fee ? 'AED '.number_format($order->delivery_fee,2) : '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:600;border-top:1px solid var(--line);padding-top:6px;margin-top:4px;">
                        <span>{{ __('admin.total') }}</span>
                        <span style="color:var(--pink);">{{ $order->total_amount ? 'AED '.number_format($order->total_amount,2) : '—' }}</span>
                    </div>
                </div>
            </div>
            @if($order->notes)
            <div style="padding:0 18px 18px;">
                <div style="font-size:11px;font-weight:700;color:var(--slate-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">{{ __('admin.notes') }}</div>
                <p style="font-size:13.5px;color:var(--slate);">{{ $order->notes }}</p>
            </div>
            @endif
        </div>

        @if($order->items->count())
        <div class="card">
            <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.package_items') }}</h3></div>
            <table class="table">
                <thead><tr><th>{{ __('admin.item') }}</th><th>{{ __('admin.qty') }}</th><th>{{ __('admin.unit_price') }}</th><th>{{ __('admin.total') }}</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-size:13px;font-weight:600;">{{ $item->name }}</div>
                            @if($item->description)<div style="font-size:11.5px;color:var(--slate);">{{ $item->description }}</div>@endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>AED {{ number_format($item->unit_price, 2) }}</td>
                        <td><span class="sora" style="font-weight:600;">AED {{ number_format($item->total_price, 2) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:12px 18px;border-top:1px solid var(--line);display:flex;justify-content:space-between;font-size:13px;font-weight:700;">
                <span>{{ __('admin.total_item_value') }}</span>
                <span style="color:var(--pink);">AED {{ number_format($order->order_value, 2) }}</span>
            </div>
        </div>
        @endif

        @if($order->proof)
        <div class="card">
            <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.proof_of_delivery') }}</h3></div>
            <div style="padding:18px;display:flex;gap:16px;align-items:flex-start;">
                <span class="badge {{ $order->proof->otp_verified ? 'badge-green' : 'badge-red' }}">{{ $order->proof->otp_verified ? __('admin.otp_verified') : __('admin.otp_not_verified') }}</span>
                @if($order->proof->photo_path)
                    <a href="{{ asset('storage/'.$order->proof->photo_path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$order->proof->photo_path) }}" style="width:120px;height:90px;object-fit:cover;border-radius:10px;border:1px solid var(--line);">
                    </a>
                @endif
                <div style="font-size:12.5px;color:var(--slate);">{{ __('admin.captured') }} {{ $order->proof->captured_at?->format('d M Y, H:i') }}</div>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.status_timeline') }}</h3></div>
            <div style="padding:18px;">
                @foreach($order->statusLogs as $log)
                <div style="display:flex;gap:12px;margin-bottom:14px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--pink);flex:none;margin-top:5px;"></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;">{{ __('admin.st_'.$log->status) }}</div>
                        @if($log->note)
                            <div style="font-size:12px;color:var(--slate);">{{ $log->note }}</div>
                        @endif
                        <div style="font-size:11px;color:var(--slate-2);">{{ $log->created_at?->format('H:i · d M') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($order->payment)
        <div class="card">
            <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.payment') }}</h3></div>
            <div style="padding:18px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:var(--slate);font-size:13px;">{{ __('admin.gateway') }}</span>
                    <span style="font-size:13px;font-weight:600;">{{ ucfirst($order->payment->gateway) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:var(--slate);font-size:13px;">{{ __('admin.status') }}</span>
                    <span class="badge {{ $order->payment->status === 'paid' ? 'badge-green' : 'badge-amber' }}">{{ __('admin.'.$order->payment->status) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--slate);font-size:13px;">{{ __('admin.amount') }}</span>
                    <span class="sora" style="font-weight:600;">AED {{ number_format($order->payment->amount, 2) }}</span>
                </div>
            </div>
        </div>
        @endif

        @if($order->rating)
        <div class="card">
            <div class="card-head"><h3 class="sora" style="font-size:14px;font-weight:700;">{{ __('admin.driver_rating') }}</h3></div>
            <div style="padding:18px;">
                <div style="font-size:24px;margin-bottom:8px;">
                    @for($i=1;$i<=5;$i++)<span style="color:{{ $i <= $order->rating->rating ? '#E08600' : '#DDD' }}">★</span>@endfor
                </div>
                @if($order->rating->tags)
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        @foreach($order->rating->tags as $tag)
                            <span class="badge badge-blue">{{ config('lapeh.rating_tags.'.$tag.'.'.app()->getLocale()) ?? str_replace('_', ' ', $tag) }}</span>
                        @endforeach
                    </div>
                @endif
                @if($order->rating->comment)
                    <p style="font-size:13px;color:var(--slate);">{{ $order->rating->comment }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
