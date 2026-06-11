@php($active = $data['activeGateway'])
@php($webhookUrl = url('/api/webhooks/payment'))

<div class="card">
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_payment') }}
            @include('admin.settings.partials.pill', ['state'=>'ok', 'label'=>__('admin.active').': '.ucfirst($active)])
        </h3>
        <p>{{ __('admin.payment_desc') }}</p>
    </div>
    <form method="POST" action="{{ route('admin.settings.update', 'payment') }}" class="settings-form">
        @csrf @method('PUT')
        <div class="settings-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('admin.active_gateway') }}</label>
                    <select name="gateway" class="form-input form-select">
                        <option value="stripe" @selected($active==='stripe')>Stripe</option>
                        <option value="telr" @selected($active==='telr')>Telr (UAE)</option>
                    </select>
                    <p class="field-help">{{ __('admin.active_gateway_help') }}</p>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('admin.default_currency') }}</label>
                    <input type="text" name="currency" value="{{ $s->get('payment.currency') }}" class="form-input" maxlength="3">
                </div>
            </div>
        </div>
        <div class="settings-foot"><button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button></div>
    </form>
</div>

{{-- Stripe --}}
<form method="POST" action="{{ route('admin.settings.update', 'payment') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">Stripe
            @include('admin.settings.partials.pill', ['state'=>$s->has('payment.stripe_secret_key')?'ok':'off', 'label'=>$s->has('payment.stripe_secret_key')?__('admin.configured'):__('admin.not_configured')])
        </h3>
    </div>
    <div class="settings-body">
        <input type="hidden" name="gateway" value="{{ $active }}">
        <input type="hidden" name="currency" value="{{ $s->get('payment.currency') }}">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.mode') }}</label>
                <select name="stripe_mode" class="form-input form-select">
                    <option value="test" @selected($s->get('payment.stripe_mode')==='test')>{{ __('admin.sandbox') }}</option>
                    <option value="live" @selected($s->get('payment.stripe_mode')==='live')>{{ __('admin.live') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.stripe_publishable') }}</label>
                <input type="text" name="stripe_publishable_key" value="{{ $s->get('payment.stripe_publishable_key') }}" class="form-input" placeholder="pk_test_...">
            </div>
            @include('admin.settings.partials.secret', ['key'=>'payment.stripe_secret_key', 'label'=>__('admin.stripe_secret')])
            @include('admin.settings.partials.secret', ['key'=>'payment.stripe_webhook_secret', 'label'=>__('admin.webhook_secret')])
        </div>
        <div class="help-box">{{ __('admin.webhook_url') }}: <code>{{ $webhookUrl }}</code></div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
        <button type="submit" form="stripe-test" class="btn btn-ghost">{{ __('admin.test_connection') }}</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.settings.test.payment') }}" id="stripe-test" style="display:none;">@csrf<input type="hidden" name="gateway" value="stripe"></form>

{{-- Telr --}}
<form method="POST" action="{{ route('admin.settings.update', 'payment') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">Telr — UAE
            @include('admin.settings.partials.pill', ['state'=>$s->has('payment.telr_auth_key')?'ok':'off', 'label'=>$s->has('payment.telr_auth_key')?__('admin.configured'):__('admin.not_configured')])
        </h3>
        <p>{{ __('admin.telr_desc') }}</p>
    </div>
    <div class="settings-body">
        <input type="hidden" name="gateway" value="{{ $active }}">
        <input type="hidden" name="currency" value="{{ $s->get('payment.currency') }}">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">{{ __('admin.mode') }}</label>
                <select name="telr_mode" class="form-input form-select">
                    <option value="test" @selected($s->get('payment.telr_mode')==='test')>{{ __('admin.sandbox') }}</option>
                    <option value="live" @selected($s->get('payment.telr_mode')==='live')>{{ __('admin.live') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Store ID</label>
                <input type="text" name="telr_store_id" value="{{ $s->get('payment.telr_store_id') }}" class="form-input">
            </div>
            @include('admin.settings.partials.secret', ['key'=>'payment.telr_auth_key', 'label'=>__('admin.telr_auth_key')])
            @include('admin.settings.partials.secret', ['key'=>'payment.telr_api_secret', 'label'=>__('admin.telr_api_secret')])
        </div>
        <div class="help-box">{{ __('admin.callback_url') }}: <code>{{ $webhookUrl }}</code> · {{ __('admin.telr_currency_note') }}</div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
        <button type="submit" form="telr-test" class="btn btn-ghost">{{ __('admin.test_connection') }}</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.settings.test.payment') }}" id="telr-test" style="display:none;">@csrf<input type="hidden" name="gateway" value="telr"></form>
