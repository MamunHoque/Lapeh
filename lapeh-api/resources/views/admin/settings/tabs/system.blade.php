@php($d = $data)
<div class="card">
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_system') }}
            @include('admin.settings.partials.pill', ['state'=>$d['environment']==='production'?'ok':'warn', 'label'=>$d['environment']])
        </h3>
        <p>{{ __('admin.system_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="info-row"><span>{{ __('admin.app_environment') }}</span><span>{{ $d['environment'] }}</span></div>
        <div class="info-row"><span>PHP</span><span>{{ $d['php_version'] }}</span></div>
        <div class="info-row"><span>Laravel</span><span>{{ $d['laravel_version'] }}</span></div>
        <div class="info-row"><span>{{ __('admin.queue_driver') }}</span><span>{{ $d['queue_driver'] }}</span></div>
        <div class="info-row"><span>{{ __('admin.cache_driver') }}</span><span>{{ $d['cache_driver'] }}</span></div>
        <div class="info-row"><span>{{ __('admin.broadcast') }}</span><span>{{ $d['broadcast_driver'] }}</span></div>
        <div class="info-row"><span>Redis / {{ __('admin.queue_status') }}</span>
            <span>@include('admin.settings.partials.pill', ['state'=>$d['redis_ok']?'ok':'red', 'label'=>$d['redis_ok']?__('admin.connected'):__('admin.disconnected')])</span>
        </div>
        <div class="info-row"><span>{{ __('admin.disk_free') }}</span>
            <span>{{ $d['disk_free_human'] }}
                @if($d['disk_free_pct'] < 10)<span class="badge badge-red">⚠ {{ $d['disk_free_pct'] }}%</span>@else({{ $d['disk_free_pct'] }}%)@endif
            </span>
        </div>
        <div class="info-row"><span>{{ __('admin.last_backup') }}</span>
            <span>{{ $d['last_backup'] ? $d['last_backup']->timezone('Asia/Dubai')->format('d M Y · H:i') : __('admin.never') }}</span>
        </div>
    </div>
    <div class="settings-foot" style="font-size:12px;color:var(--slate);">🔒 {{ __('admin.infra_note') }}</div>
</div>
