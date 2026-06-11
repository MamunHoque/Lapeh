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

        {{-- Package summary: shown up-front so the receiver knows what's coming --}}
        @if($order->items->count())
        <div class="card" style="margin-bottom:20px;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
                <div class="sora" style="font-size:14px;font-weight:700;">{{ __('customer.package_items') }}</div>
                <span class="badge badge-pink">{{ $order->items->count() }}</span>
            </div>
            <div style="padding:8px 20px 14px;">
                @foreach($order->items as $item)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--ink);">{{ $item->name }} <span style="color:var(--slate);font-weight:500;">×{{ $item->quantity }}</span></div>
                        @if($item->description)<div style="font-size:12px;color:var(--slate);">{{ $item->description }}</div>@endif
                    </div>
                    <div class="sora" style="font-size:13px;font-weight:600;white-space:nowrap;">AED {{ number_format($item->total_price, 2) }}</div>
                </div>
                @endforeach
                <div style="display:flex;justify-content:space-between;padding-top:12px;font-size:14px;font-weight:700;">
                    <span>{{ __('customer.total_value') }}</span>
                    <span style="color:var(--pink);">AED {{ number_format($order->order_value, 2) }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 1: Confirm Location (hidden once the location is confirmed) --}}
        @if($order->status === 'waiting_for_location')
        <div class="card" style="margin-bottom:20px;" x-show="step === 'location'">
            <div style="padding:20px;">
                <h2 class="sora" style="font-size:18px;font-weight:700;margin-bottom:4px;">{{ __('customer.confirm_location_title') }}</h2>
                <p style="font-size:13.5px;color:var(--slate);margin-bottom:20px;">{{ __('customer.confirm_location_sub') }}</p>

                @php
                    $emirates = ['Dubai','Abu Dhabi','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
                    $fieldStyle = 'width:100%;padding:11px 13px;border:1.5px solid var(--line);border-radius:11px;font-size:14px;font-family:inherit;color:var(--ink);outline:none;background:#fff;';
                @endphp
                <form action="{{ route('customer.confirm', $order->location_token) }}" method="POST" x-ref="locationForm" @submit.prevent="submitLocation">
                    @csrf

                    {{-- Address search (Google Places autocomplete, UAE-restricted) --}}
                    <div style="margin-bottom:12px;">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:var(--slate);margin-bottom:6px;">{{ __('customer.your_address') }}</label>
                        <input type="text" id="address-search" x-model="searchText"
                            @keydown.enter.prevent="searchAddress()"
                            autocomplete="off"
                            placeholder="{{ __('customer.search_address') }}"
                            style="{{ $fieldStyle }}">
                    </div>

                    @if(!empty($mapsKey))
                    {{-- Map: defaults to the customer's current location; drag pin / tap to adjust --}}
                    <div id="pickmap" style="width:100%;height:220px;border-radius:14px;overflow:hidden;border:1.5px solid var(--line);margin-bottom:8px;"></div>
                    <p style="font-size:12px;color:var(--slate);margin-bottom:6px;">{{ __('customer.pin_hint') }}</p>
                    @endif

                    <button type="button" @click="getGPS()"
                        style="width:100%;padding:11px;background:var(--blue-s);color:var(--blue);border:none;border-radius:11px;font-weight:600;font-size:14px;cursor:pointer;font-family:inherit;margin-bottom:6px;">
                        {{ __('customer.use_current_location') }}
                    </button>
                    <div x-show="gpsStatus" style="font-size:13px;color:var(--slate);margin-bottom:8px;" x-text="gpsStatus"></div>

                    {{-- Structured UAE address so the driver can find the exact door --}}
                    <div style="border-top:1px solid var(--line);margin:14px 0;padding-top:14px;">
                        <div class="sora" style="font-size:13px;font-weight:700;margin-bottom:12px;">{{ __('customer.address_details') }}</div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.emirate') }}</label>
                                <select x-model="emirate" style="{{ $fieldStyle }}">
                                    <option value="">{{ __('customer.select_emirate') }}</option>
                                    @foreach($emirates as $em)
                                    <option value="{{ $em }}">{{ $em }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.area_community') }}</label>
                                <input type="text" x-model="area" style="{{ $fieldStyle }}">
                            </div>
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.street_road') }}</label>
                            <input type="text" x-model="street" style="{{ $fieldStyle }}">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.building_villa') }}</label>
                            <input type="text" x-model="building" style="{{ $fieldStyle }}">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.floor_apt') }} <span style="color:var(--slate-2);font-weight:500;">({{ __('customer.optional') }})</span></label>
                                <input type="text" x-model="floorApt" style="{{ $fieldStyle }}">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:var(--slate);margin-bottom:5px;">{{ __('customer.landmark') }} <span style="color:var(--slate-2);font-weight:500;">({{ __('customer.optional') }})</span></label>
                                <input type="text" x-model="landmark" style="{{ $fieldStyle }}">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="address" :value="composedAddress()">
                    <input type="hidden" name="lat" x-model="lat">
                    <input type="hidden" name="lng" x-model="lng">

                    @if(!empty($mapsKey))
                    <button type="submit" class="btn-primary" :disabled="!lat" :style="!lat ? 'opacity:.5;' : ''">{{ __('customer.confirm_location_btn') }}</button>
                    @else
                    <button type="submit" class="btn-primary" :disabled="!composedAddress() && !lat">{{ __('customer.confirm_location_btn') }}</button>
                    @endif
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
        @if(in_array($order->status, ['paid', 'searching_driver', 'driver_assigned', 'arrived_at_pickup', 'picked_up', 'on_the_way', 'delivered', 'cancelled']))
        <div class="card" style="margin-bottom:20px;">
            <div style="padding:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h2 class="sora" style="font-size:18px;font-weight:700;">{{ __('customer.track_title') }}</h2>
                    @php
                        $statusBadge = match($order->status) {
                            'searching_driver' => ['badge-pink', __('customer.status_searching')],
                            'driver_assigned' => ['badge-blue', __('customer.status_assigned')],
                            'arrived_at_pickup' => ['badge-blue', __('customer.status_at_pickup')],
                            'picked_up' => ['badge-blue', __('customer.status_picked_up')],
                            'on_the_way' => ['badge-blue', __('customer.status_on_the_way')],
                            'delivered' => ['badge-green', __('customer.status_delivered')],
                            'cancelled' => ['badge-grey', __('customer.status_cancelled')],
                            default => ['badge-amber', ucfirst(str_replace('_', ' ', $order->status))],
                        };
                    @endphp
                    <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                </div>

                @if($order->driver && in_array($order->status, ['driver_assigned','arrived_at_pickup','picked_up','on_the_way']))
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
                        $statuses = ['paid','searching_driver','driver_assigned','arrived_at_pickup','picked_up','on_the_way','delivered'];
                        $statusLabels = [__('customer.tl_paid'),__('customer.tl_searching'),__('customer.tl_assigned'),__('customer.tl_at_pickup'),__('customer.tl_picked_up'),__('customer.tl_on_the_way'),__('customer.tl_delivered')];
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
                <div class="sora" style="font-size:13px;font-weight:700;color:var(--slate);">{{ __('customer.from_sender', ['name' => $order->sender?->displayName()]) }}</div>
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
        step: '{{ $order->status === "waiting_for_location" ? "location" : "track" }}',
        lat: '',
        lng: '',
        gpsStatus: '',
        // Structured UAE address fields
        searchText: '',
        emirate: '',
        area: '',
        street: '',
        building: '',
        floorApt: '',
        landmark: '',
        _map: null,
        _marker: null,
        _geocoder: null,
        _autocomplete: null,

        init() {
            window._orderApp = this;
            @if(in_array($order->status, ['paid','searching_driver','driver_assigned','arrived_at_pickup','picked_up','on_the_way']))
            // Poll for status updates every 15s
            setInterval(() => this.pollStatus(), 15000);
            @endif
        },

        // Compose the full delivery address the driver will see.
        composedAddress() {
            const parts = [];
            if (this.building) parts.push(this.building);
            if (this.floorApt) parts.push(this.floorApt);
            if (this.street) parts.push(this.street);
            if (this.area) parts.push(this.area);
            if (this.emirate) parts.push(this.emirate);
            let addr = parts.join(', ');
            if (this.landmark) addr += (addr ? ' — ' : '') + this.landmark;
            // Fall back to the searched/geocoded address if no fields filled.
            return addr || this.searchText || '';
        },

        // Called by the Google Maps JS callback once the SDK has loaded.
        initMap() {
            const el = document.getElementById('pickmap');
            if (!el || !window.google) return;
            const fallback = { lat: {{ $order->pickup_lat ?? 25.2048 }}, lng: {{ $order->pickup_lng ?? 55.2708 }} };
            this._geocoder = new google.maps.Geocoder();
            this._map = new google.maps.Map(el, {
                center: fallback, zoom: 14, disableDefaultUI: true, gestureHandling: 'greedy',
            });
            this._marker = new google.maps.Marker({ map: this._map, draggable: true });
            this._marker.addListener('dragend', (e) => this.setPin(e.latLng.lat(), e.latLng.lng(), true));
            this._map.addListener('click', (e) => this.setPin(e.latLng.lat(), e.latLng.lng(), true));

            // Default the pin to the customer's current location.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => this.setPin(pos.coords.latitude, pos.coords.longitude, true),
                    () => this.setPin(fallback.lat, fallback.lng, false),
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            } else {
                this.setPin(fallback.lat, fallback.lng, false);
            }

            // Places Autocomplete on the search field, restricted to the UAE.
            const input = document.getElementById('address-search');
            if (input && google.maps.places) {
                this._autocomplete = new google.maps.places.Autocomplete(input, {
                    componentRestrictions: { country: 'ae' },
                    fields: ['geometry', 'formatted_address', 'address_components'],
                });
                this._autocomplete.addListener('place_changed', () => {
                    const place = this._autocomplete.getPlace();
                    if (!place.geometry) return;
                    const loc = place.geometry.location;
                    this.searchText = place.formatted_address || this.searchText;
                    this.fillComponents(place.address_components);
                    this.setPin(loc.lat(), loc.lng(), false);
                    this._map.setZoom(16);
                });
            }
        },

        // Move pin + camera; optionally reverse-geocode to prefill the fields.
        setPin(lat, lng, reverse) {
            this.lat = lat;
            this.lng = lng;
            const p = { lat: Number(lat), lng: Number(lng) };
            if (this._map && this._marker) {
                this._marker.setPosition(p);
                this._map.panTo(p);
            }
            if (reverse && this._geocoder) {
                this._geocoder.geocode({ location: p }, (res, status) => {
                    if (status === 'OK' && res[0]) {
                        this.searchText = res[0].formatted_address;
                        this.fillComponents(res[0].address_components);
                    }
                });
            }
        },

        // Auto-fill the structured fields from Google address components.
        // Location-derived fields (emirate/area/street/building) are OVERWRITTEN
        // on every new location so they always match the search bar / pin.
        // Floor/apartment + landmark are left to the customer.
        fillComponents(components) {
            if (!components) return;
            const get = (...types) => {
                const c = components.find(comp => types.some(t => comp.types.includes(t)));
                return c ? c.long_name : '';
            };
            this.emirate = this.matchEmirate(get('administrative_area_level_1'));
            this.area = get('sublocality_level_1', 'sublocality', 'neighborhood', 'locality', 'administrative_area_level_2');
            this.street = get('route');
            const premise = get('premise', 'street_number');
            if (premise) this.building = premise; // keep manual entry if Google has none
        },

        // Map Google's emirate name to our dropdown options ('' if none).
        matchEmirate(name) {
            if (!name) return '';
            const list = ['Dubai','Abu Dhabi','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
            const n = name.toLowerCase();
            return list.find(e => n.includes(e.toLowerCase().split(' ')[0])) || '';
        },

        // Enter key in the search field → geocode the typed text.
        searchAddress() {
            if (!this._geocoder || !this.searchText) return;
            this._geocoder.geocode(
                { address: this.searchText, componentRestrictions: { country: 'AE' } },
                (res, status) => {
                    if (status === 'OK' && res[0]) {
                        const loc = res[0].geometry.location;
                        this.searchText = res[0].formatted_address;
                        this.fillComponents(res[0].address_components);
                        this.setPin(loc.lat(), loc.lng(), false);
                        if (this._map) this._map.setZoom(16);
                    }
                }
            );
        },

        getGPS() {
            this.gpsStatus = @json(__('customer.gps_getting'));
            if (!navigator.geolocation) {
                this.gpsStatus = @json(__('customer.gps_unsupported'));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.setPin(pos.coords.latitude, pos.coords.longitude, true);
                    if (this._map) this._map.setZoom(16);
                    this.gpsStatus = @json(__('customer.gps_captured'));
                },
                () => { this.gpsStatus = @json(__('customer.gps_failed')); }
            );
        },

        async submitLocation() {
            const form = this.$refs.locationForm;
            const data = new FormData(form);
            data.set('lat', this.lat);
            data.set('lng', this.lng);
            data.set('address', this.composedAddress());

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

// Google Maps JS callback → forward to the Alpine component once mounted.
function lapehInitMap() {
    if (window._orderApp) {
        window._orderApp.initMap();
    } else {
        // Alpine not ready yet — retry shortly.
        const t = setInterval(() => {
            if (window._orderApp) { clearInterval(t); window._orderApp.initMap(); }
        }, 150);
    }
}
</script>
@if(!empty($mapsKey) && $order->status === 'waiting_for_location')
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=lapehInitMap&loading=async"></script>
@endif
</body>
</html>
