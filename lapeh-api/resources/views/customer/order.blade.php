<!doctype html>
<html lang="{{ $locale ?? 'en' }}" dir="{{ ($rtl ?? false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('customer.order') }} {{ $order->order_no }} — Lapeh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/customer.js'])
    <style>
        :root{--pink:#FB0E72;--pink-deep:#D1005C;--ink:#14192B;--slate:#6B748A;--line:#EAECF2;--bg:#F4F6FB;--green:#0E9E6E;--green-s:#E3F7EF;--amber:#E08600;--amber-s:#FFF2D9;--blue:#3457D5;--blue-s:#E8EEFF;}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;-webkit-font-smoothing:antialiased}
        h1,h2,h3,b,.sora{font-family:'Sora',sans-serif}
        .card{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 10px 26px -18px rgba(20,25,43,.4);overflow:hidden}
        .btn-primary{display:block;width:100%;padding:15px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));color:#fff;border:none;border-radius:14px;font-family:'Sora',sans-serif;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 12px 22px -10px rgba(251,14,114,.65);text-align:center;text-decoration:none}
        .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:999px;font-size:12.5px;font-weight:600}
        .badge-green{background:var(--green-s);color:var(--green)}
        .badge-amber{background:var(--amber-s);color:var(--amber)}
        .badge-blue{background:var(--blue-s);color:var(--blue)}
        .badge-pink{background:#FFF0F6;color:var(--pink)}
        .timeline-dot{width:10px;height:10px;border-radius:50%;background:var(--line);flex:none;margin-top:4px}
        .timeline-dot.done{background:var(--green)}
        .timeline-dot.active{background:var(--pink)}
    </style>
</head>
<body x-data="orderApp()" x-init="init()">

    <div style="background:linear-gradient(135deg,var(--ink),#1C2336);padding:20px;text-align:center;position:relative;">
        <a href="?lang={{ ($rtl ?? false) ? 'en' : 'ar' }}" style="position:absolute;top:16px;{{ ($rtl ?? false) ? 'left' : 'right' }}:16px;color:#fff;font-size:13px;font-weight:600;text-decoration:none;background:rgba(255,255,255,.12);padding:6px 12px;border-radius:999px;">{{ __('customer.switch_lang') }}</a>
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));display:grid;place-items:center;margin:0 auto 12px;">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/></svg>
        </div>
        <h1 style="font-size:20px;font-weight:700;color:#fff;letter-spacing:-.02em;">{{ __('customer.brand_tagline') }}</h1>
        <p style="font-size:13px;color:#9CA4B8;margin-top:4px;">{{ __('customer.order') }} <span class="sora" style="color:var(--pink);">{{ $order->order_no }}</span></p>
    </div>

    <div style="max-width:480px;margin:0 auto;padding:20px 16px;">

        {{-- STEP 1: Confirm Location --}}
        @if(in_array($order->status, ['waiting_for_location', 'location_confirmed']))
        <div class="card" style="margin-bottom:20px;" x-show="step === 'location'">
            <div style="padding:20px;">
                <h2 class="sora" style="font-size:18px;font-weight:700;margin-bottom:4px;">{{ __('customer.confirm_location_title') }}</h2>
                <p style="font-size:13.5px;color:var(--slate);margin-bottom:20px;">{{ __('customer.confirm_location_sub') }}</p>

                <form action="{{ route('customer.confirm', $order->location_token) }}" method="POST" x-ref="locationForm" @submit.prevent="submitLocation">
                    @csrf
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:var(--slate);margin-bottom:6px;">{{ __('customer.your_address') }}</label>
                        <input type="text" name="address" id="address-input" x-model="address"
                            placeholder="{{ __('customer.address_placeholder') }}"
                            style="width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-family:inherit;color:var(--ink);outline:none;">
                    </div>
                    <input type="hidden" name="lat" x-model="lat">
                    <input type="hidden" name="lng" x-model="lng">

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:var(--slate);margin-bottom:6px;">{{ __('customer.or_use_gps') }}</label>
                        <button type="button" @click="getGPS()"
                            style="width:100%;padding:12px;background:var(--blue-s);color:var(--blue);border:none;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;font-family:inherit;">
                            {{ __('customer.use_current_location') }}
                        </button>
                    </div>

                    <div x-show="gpsStatus" style="font-size:13px;color:var(--slate);margin-bottom:14px;" x-text="gpsStatus"></div>

                    <button type="submit" class="btn-primary" :disabled="!address && !lat">{{ __('customer.confirm_location_btn') }}</button>
                </form>
            </div>
        </div>
        @endif

        {{-- STEP 2: Payment --}}
        @if($order->status === 'location_confirmed')
        <div class="card" style="margin-bottom:20px;">
            <div style="padding:20px;">
                <h2 class="sora" style="font-size:18px;font-weight:700;margin-bottom:4px;">{{ __('customer.pay_title') }}</h2>
                <p style="font-size:13.5px;color:var(--slate);margin-bottom:20px;">{{ __('customer.pay_sub') }}</p>

                <div style="background:var(--bg);border-radius:14px;padding:16px;margin-bottom:18px;">
                    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:10px;">
                        <span style="color:var(--slate);">{{ __('customer.order_value') }}</span>
                        <span class="sora" style="font-weight:600;">AED {{ number_format($order->order_value, 2) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:10px;">
                        <span style="color:var(--slate);">{{ __('customer.distance') }}</span>
                        <span>{{ $order->distance_km }} km</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:10px;">
                        <span style="color:var(--slate);">{{ __('customer.delivery_fee') }}</span>
                        <span class="sora" style="font-weight:600;">AED {{ number_format($order->delivery_fee, 2) }}</span>
                    </div>
                    <div style="border-top:1px solid var(--line);padding-top:10px;display:flex;justify-content:space-between;font-size:16px;font-weight:700;">
                        <span>{{ __('customer.total') }}</span>
                        <span style="color:var(--pink);">AED {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                <form action="{{ route('customer.pay', $order->location_token) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary">{{ __('customer.pay_btn', ['amount' => number_format($order->total_amount, 2)]) }}</button>
                </form>
            </div>
        </div>
        @endif

        {{-- STEP 3+: Track --}}
        @if(in_array($order->status, ['paid', 'searching_driver', 'driver_assigned', 'arrived_at_restaurant', 'picked_up', 'on_the_way', 'delivered', 'cancelled']))
        <div class="card" style="margin-bottom:20px;">
            <div style="padding:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h2 class="sora" style="font-size:18px;font-weight:700;">{{ __('customer.track_title') }}</h2>
                    @php
                        $statusBadge = match($order->status) {
                            'searching_driver' => ['badge-pink', __('customer.status_searching')],
                            'driver_assigned' => ['badge-blue', __('customer.status_assigned')],
                            'arrived_at_restaurant' => ['badge-blue', __('customer.status_at_restaurant')],
                            'picked_up' => ['badge-blue', __('customer.status_picked_up')],
                            'on_the_way' => ['badge-blue', __('customer.status_on_the_way')],
                            'delivered' => ['badge-green', __('customer.status_delivered')],
                            'cancelled' => ['badge-grey', __('customer.status_cancelled')],
                            default => ['badge-amber', ucfirst(str_replace('_', ' ', $order->status))],
                        };
                    @endphp
                    <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                </div>

                @if($order->driver && in_array($order->status, ['driver_assigned','arrived_at_restaurant','picked_up','on_the_way']))
                <div style="background:var(--bg);border-radius:14px;padding:16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));display:grid;place-items:center;font-family:'Sora';font-weight:700;color:#fff;font-size:14px;flex:none;">
                        {{ strtoupper(substr($order->driver->user->name ?? 'D', 0, 2)) }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;">{{ $order->driver->user->name ?? __('customer.your_driver') }}</div>
                        <div style="font-size:12.5px;color:var(--slate);">{{ ucfirst($order->driver->vehicle_type) }}</div>
                    </div>
                    <div style="margin-inline-start:auto;">
                        <a href="tel:{{ $order->driver->user->phone }}" style="background:var(--green-s);color:var(--green);padding:8px 14px;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none;">{{ __('customer.call') }}</a>
                    </div>
                </div>
                @endif

                @if($order->status === 'on_the_way' || $order->status === 'delivered')
                <div style="background:var(--pink-soft);border-radius:14px;padding:16px;margin-bottom:16px;text-align:center;">
                    <div style="font-size:12px;font-weight:700;color:var(--pink);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">{{ __('customer.delivery_otp') }}</div>
                    <div class="sora" style="font-size:36px;font-weight:800;color:var(--pink);letter-spacing:.12em;">{{ $order->otp_code }}</div>
                    <div style="font-size:12px;color:var(--slate);margin-top:4px;">{{ __('customer.show_to_driver') }}</div>
                </div>
                @endif

                {{-- Timeline --}}
                <div>
                    @php
                        $statuses = ['paid','searching_driver','driver_assigned','arrived_at_restaurant','picked_up','on_the_way','delivered'];
                        $statusLabels = [__('customer.tl_paid'),__('customer.tl_searching'),__('customer.tl_assigned'),__('customer.tl_at_restaurant'),__('customer.tl_picked_up'),__('customer.tl_on_the_way'),__('customer.tl_delivered')];
                        $currentIdx = array_search($order->status, $statuses);
                    @endphp
                    @foreach($statuses as $i => $s)
                    <div style="display:flex;gap:12px;margin-bottom:12px;">
                        <div class="timeline-dot {{ $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'active' : '') }}" style="margin-top:4px;"></div>
                        <div>
                            <div style="font-size:13.5px;font-weight:{{ $i === $currentIdx ? '700' : '500' }};color:{{ $i <= $currentIdx ? 'var(--ink)' : 'var(--slate-2)' }};">{{ $statusLabels[$i] }}</div>
                            @if($i === $currentIdx && $order->status !== 'delivered')
                            <div style="font-size:12px;color:var(--pink);font-weight:600;">{{ __('customer.now') }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Order summary --}}
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
                <div class="sora" style="font-size:13px;font-weight:700;color:var(--slate);">{{ __('customer.from_restaurant', ['name' => ($rtl ?? false) ? ($order->restaurant->name_ar ?? $order->restaurant->name) : $order->restaurant->name]) }}</div>
            </div>
            <div style="padding:16px 20px;">
                <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:8px;">
                    <span style="color:var(--slate);">{{ __('customer.hello') }}</span>
                    <span style="font-weight:600;">{{ $order->customer_name }}</span>
                </div>
                @if($order->customer_address)
                <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:8px;">
                    <span style="color:var(--slate);">{{ __('customer.delivering_to') }}</span>
                    <span style="font-size:12.5px;text-align:end;max-width:200px;">{{ $order->customer_address }}</span>
                </div>
                @endif
                @if($order->delivery_fee)
                <div style="display:flex;justify-content:space-between;font-size:13.5px;">
                    <span style="color:var(--slate);">{{ __('customer.delivery_fee_label') }}</span>
                    <span class="sora" style="font-weight:700;color:var(--pink);">AED {{ number_format($order->delivery_fee, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

<script>
function orderApp() {
    return {
        step: '{{ in_array($order->status, ["waiting_for_location","location_confirmed"]) ? "location" : "track" }}',
        lat: '',
        lng: '',
        address: '',
        gpsStatus: '',

        init() {
            @if(in_array($order->status, ['paid','searching_driver','driver_assigned','arrived_at_restaurant','picked_up','on_the_way']))
            // Poll for status updates every 15s
            setInterval(() => this.pollStatus(), 15000);
            @endif
        },

        getGPS() {
            this.gpsStatus = @json(__('customer.gps_getting'));
            if (!navigator.geolocation) {
                this.gpsStatus = @json(__('customer.gps_unsupported'));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.address = `${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`;
                    this.gpsStatus = @json(__('customer.gps_captured'));
                },
                () => { this.gpsStatus = @json(__('customer.gps_failed')); }
            );
        },

        async submitLocation() {
            const form = this.$refs.locationForm;
            const data = new FormData(form);
            if (this.lat) data.set('lat', this.lat);
            if (this.lng) data.set('lng', this.lng);
            if (this.address) data.set('address', this.address);

            const res = await fetch(form.action, { method: 'POST', body: data });
            if (res.ok || res.redirected) {
                location.reload();
            }
        },

        async pollStatus() {
            try {
                const res = await fetch('/c/{{ $order->location_token }}/track');
                const data = await res.json();
                if (data.status !== '{{ $order->status }}') {
                    location.reload();
                }
            } catch(e) {}
        }
    }
}
</script>
</body>
</html>
