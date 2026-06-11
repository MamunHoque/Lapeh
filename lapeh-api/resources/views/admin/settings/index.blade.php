@extends('admin.layout')
@section('title', __('admin.settings'))
@section('content')
<style>
    .field-help { font-size:11.5px; color:var(--slate); margin-top:5px; }
    .settings-crumb { font-size:12.5px; color:var(--slate); margin-bottom:16px; }
    .settings-crumb b { color:var(--ink); font-weight:600; }
    .settings-hub { display:grid; grid-template-columns:230px 1fr; gap:22px; align-items:start; }
    .settings-nav { background:#fff; border:1px solid var(--line); border-radius:16px; padding:8px; position:sticky; top:78px; }
    .settings-nav-item { display:flex; align-items:center; gap:10px; padding:9px 11px; border-radius:10px; font-size:13px; font-weight:500; color:var(--slate); text-decoration:none; transition:.15s; }
    .settings-nav-item:hover { background:var(--bg); color:var(--ink); }
    .settings-nav-item.active { background:var(--pink-soft); color:var(--pink); font-weight:700; }
    .settings-nav-item svg { flex:none; }
    .settings-section-head { padding:18px 20px 4px; }
    .settings-section-head h3 { font-size:16px; font-weight:700; }
    .settings-section-head p { font-size:12.5px; color:var(--slate); margin-top:4px; max-width:560px; }
    .settings-body { padding:18px 20px 20px; }
    .settings-foot { display:flex; align-items:center; gap:12px; padding:16px 20px; border-top:1px solid var(--line); background:#FAFBFE; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
    .switch { display:flex; align-items:center; gap:12px; padding:14px 0; border-bottom:1px solid var(--line); }
    .switch:last-child { border-bottom:none; }
    .switch-meta { flex:1; }
    .switch-meta b { font-size:13.5px; font-weight:600; display:block; }
    .switch-meta span { font-size:12px; color:var(--slate); }
    .toggle { position:relative; width:44px; height:24px; flex:none; }
    .toggle input { opacity:0; width:0; height:0; }
    .toggle .track { position:absolute; inset:0; background:#CBD2E0; border-radius:999px; transition:.2s; cursor:pointer; }
    .toggle .track::before { content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
    .toggle input:checked + .track { background:var(--pink); }
    .toggle input:checked + .track::before { transform:translateX(20px); }
    [dir="rtl"] .toggle input:checked + .track::before { transform:translateX(-20px); }
    .info-row { display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--line); font-size:13px; }
    .info-row:last-child { border-bottom:none; }
    .info-row span:first-child { color:var(--slate); font-weight:600; }
    .help-box { background:var(--bg); border-radius:12px; padding:13px 15px; font-size:12.5px; color:var(--slate); margin-bottom:16px; }
    .help-box b { color:var(--ink); }
    .color-row { display:flex; align-items:center; gap:12px; }
    .color-row input[type=color] { width:48px; height:40px; border:1px solid var(--line); border-radius:10px; padding:2px; background:#fff; }
    @media (max-width:900px){ .settings-hub{ grid-template-columns:1fr; } .settings-nav{ position:static; display:flex; flex-wrap:wrap; } .grid-2{ grid-template-columns:1fr; } }
</style>

@if(session('success'))<div class="alert alert-success">✓ {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

<div class="settings-crumb">{{ __('admin.settings') }} → <b>{{ __('admin.settings_tab_'.$tab) }}</b></div>

@php
$icons = [
  'general'=>'M3 7h18M3 12h18M3 17h18','pricing'=>'M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6','commission'=>'M12 2a10 10 0 100 20 10 10 0 000-20M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01','branding'=>'M12 2l2.4 7.4H22l-6 4.6 2.3 7.4L12 17l-6.3 4.4L8 14 2 9.4h7.6z',
  'registration'=>'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8M22 11h-6','mail'=>'M4 4h16v16H4zM4 6l8 6 8-6',
  'sms'=>'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','payment'=>'M2 7h20v12H2zM2 11h20',
  'maps'=>'M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7zM12 11a2 2 0 100-4 2 2 0 000 4',
  'fcm'=>'M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0',
  'otp'=>'M12 1l9 4v6c0 5-4 9-9 11-5-2-9-6-9-11V5z','support'=>'M21 11.5a8.5 8.5 0 01-12 7.7L3 21l1.8-6A8.5 8.5 0 1121 11.5z',
  'database'=>'M12 2c5 0 9 1.3 9 3v14c0 1.7-4 3-9 3s-9-1.3-9-3V5c0-1.7 4-3 9-3zM3 5c0 1.7 4 3 9 3s9-1.3 9-3',
  'system'=>'M12 8v4l3 3M12 2a10 10 0 100 20 10 10 0 000-20',
];
@endphp

<div class="settings-hub">
    <aside class="settings-nav">
        @foreach($tabs as $t)
            <a href="{{ route('admin.settings.tab', $t) }}" class="settings-nav-item {{ $t===$tab ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="{{ $icons[$t] ?? $icons['system'] }}"/></svg>
                {{ __('admin.settings_tab_'.$t) }}
            </a>
        @endforeach
    </aside>

    <section>
        @include('admin.settings.tabs.'.$tab)
    </section>
</div>

<script>
(function(){
    let dirty = false;
    document.querySelectorAll('form.settings-form').forEach(function(f){
        f.addEventListener('input', function(){ dirty = true; });
        f.addEventListener('submit', function(){ dirty = false; });
    });
    window.addEventListener('beforeunload', function(e){
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>
@endsection
