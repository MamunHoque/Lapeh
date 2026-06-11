@php
    $chargeSender = $s->get('commission.charge_sender');
    $chargeDriver = $s->get('commission.charge_driver');
@endphp
<form method="POST" action="{{ route('admin.settings.update', 'commission') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_commission') }}</h3>
        <p>{{ __('admin.commission_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="help-box">{{ __('admin.commission_help') }}</div>

        {{-- Sender side --}}
        <div class="switch">
            <div class="switch-meta">
                <b>{{ __('admin.commission_charge_sender') }}
                    @include('admin.settings.partials.pill', ['state'=>$chargeSender?'ok':'off', 'label'=>$chargeSender?__('admin.commission_on'):__('admin.commission_off')])
                </b>
                <span>{{ __('admin.commission_sender_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="charge_sender" value="1" @checked($chargeSender)><span class="track"></span></label>
        </div>
        <div class="grid-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="form-label">{{ __('admin.commission_type') }}</label>
                <select name="sender_type" class="form-input form-select">
                    <option value="percent" @selected($s->get('commission.sender_type')==='percent')>{{ __('admin.commission_percent') }}</option>
                    <option value="fixed" @selected($s->get('commission.sender_type')==='fixed')>{{ __('admin.commission_fixed') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.commission_rate') }}</label>
                <input type="number" step="0.01" min="0" name="sender_rate" value="{{ $s->get('commission.sender_rate') }}" class="form-input">
                <p class="field-help">{{ __('admin.commission_rate_help') }}</p>
            </div>
        </div>

        {{-- Driver side --}}
        <div class="switch" style="margin-top:8px;">
            <div class="switch-meta">
                <b>{{ __('admin.commission_charge_driver') }}
                    @include('admin.settings.partials.pill', ['state'=>$chargeDriver?'ok':'off', 'label'=>$chargeDriver?__('admin.commission_on'):__('admin.commission_off')])
                </b>
                <span>{{ __('admin.commission_driver_help') }}</span>
            </div>
            <label class="toggle"><input type="checkbox" name="charge_driver" value="1" @checked($chargeDriver)><span class="track"></span></label>
        </div>
        <div class="grid-2" style="margin-top:10px;">
            <div class="form-group">
                <label class="form-label">{{ __('admin.commission_type') }}</label>
                <select name="driver_type" class="form-input form-select">
                    <option value="percent" @selected($s->get('commission.driver_type')==='percent')>{{ __('admin.commission_percent') }}</option>
                    <option value="fixed" @selected($s->get('commission.driver_type')==='fixed')>{{ __('admin.commission_fixed') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('admin.commission_rate') }}</label>
                <input type="number" step="0.01" min="0" name="driver_rate" value="{{ $s->get('commission.driver_rate') }}" class="form-input">
                <p class="field-help">{{ __('admin.commission_rate_help') }}</p>
            </div>
        </div>

        <div class="help-box" style="margin-top:6px;">
            <b>{{ __('admin.commission_example') }}</b> {{ __('admin.commission_example_body') }}
        </div>
    </div>
    <div class="settings-foot">
        <button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button>
    </div>
</form>
