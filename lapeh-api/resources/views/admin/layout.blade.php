<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.dashboard')) — {{ __('admin.portal_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    <style>
        :root {
            --pink: #FB0E72; --pink-deep: #D1005C; --pink-soft: #FFF0F6;
            --ink: #14192B; --ink-2: #1C2336; --ink-3: #283149;
            --slate: #6B748A; --slate-2: #9CA4B8; --line: #EAECF2;
            --bg: #F4F6FB; --card: #fff;
            --green: #0E9E6E; --green-s: #E3F7EF;
            --blue: #3457D5; --blue-s: #E8EEFF;
            --amber: #E08600; --amber-s: #FFF2D9;
            --red: #E03131; --red-s: #FDE7E7;
            --indigo: #7C5CFC; --indigo-s: #EFE9FF;
        }
        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        body { font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--ink); -webkit-font-smoothing: antialiased; }
        h1,h2,h3,b,.sora { font-family: 'Sora', sans-serif; }
        .sidebar { background: var(--ink); color: #fff; width: 262px; min-height: 100vh; display: flex; flex-direction: column; position: fixed; top: 0; inset-inline-start: 0; overflow-y: auto; z-index: 40; }
        .sidebar::-webkit-scrollbar { width: 0; }
        .main-area { margin-inline-start: 262px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { position: sticky; top: 0; z-index: 30; background: rgba(255,255,255,.9); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 16px; padding: 13px 28px; }
        .content { padding: 26px 28px 60px; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 10px; color: #AEB6C8; font-size: 13.5px; font-weight: 500; cursor: pointer; text-decoration: none; transition: .15s; }
        .nav-item:hover { background: var(--ink-3); color: #fff; }
        .nav-item.active { background: linear-gradient(90deg, var(--pink), var(--pink-deep)); color: #fff; font-weight: 600; box-shadow: 0 8px 18px -8px rgba(251,14,114,.7); }
        .nav-badge { margin-inline-start: auto; background: var(--pink); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; }
        .nav-item.active .nav-badge { background: rgba(255,255,255,.25); }
        .nav-group { font-size: 10px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: #566077; margin: 16px 12px 6px; }
        .kpi { border-radius: 18px; padding: 18px 18px 16px; color: #fff; position: relative; overflow: hidden; box-shadow: 0 16px 30px -18px rgba(20,25,43,.5); }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 10px 26px -22px rgba(20,25,43,.5); margin-bottom: 18px; overflow: hidden; }
        .card-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 18px; border-bottom: 1px solid var(--line); }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: start; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--slate-2); padding: 12px 18px; border-bottom: 1px solid var(--line); }
        .table td { padding: 13px 18px; font-size: 13px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        .table tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background: #FAFBFE; }
        .mono { font-family: 'Sora'; font-weight: 600; color: var(--pink); font-size: 12.5px; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
        .badge-green { background: var(--green-s); color: var(--green); }
        .badge-blue { background: var(--blue-s); color: var(--blue); }
        .badge-amber { background: var(--amber-s); color: var(--amber); }
        .badge-red { background: var(--red-s); color: var(--red); }
        .badge-grey { background: #F1F3F8; color: var(--slate); }
        .badge-pink { background: var(--pink-soft); color: var(--pink); }
        .badge-indigo { background: var(--indigo-s); color: var(--indigo); }
        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: .15s; }
        .btn-primary { background: linear-gradient(135deg, var(--pink), var(--pink-deep)); color: #fff; box-shadow: 0 10px 20px -10px rgba(251,14,114,.65); }
        .btn-primary:hover { opacity: .92; }
        .btn-ghost { background: #fff; color: var(--ink); border: 1px solid var(--line); }
        .btn-ghost:hover { background: var(--bg); }
        .btn-danger { background: var(--red-s); color: var(--red); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--slate); margin-bottom: 6px; }
        .form-input { width: 100%; padding: 10px 13px; border: 1px solid var(--line); border-radius: 10px; font-size: 14px; font-family: inherit; color: var(--ink); background: #fff; outline: none; transition: border-color .15s; }
        .form-input:focus { border-color: var(--pink); }
        .form-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236B748A' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 13px center; padding-inline-end: 36px; }
        [dir="rtl"] .form-select { background-position: left 13px center; }
        .avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--pink), var(--pink-deep)); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 12px; font-family: 'Sora'; flex: none; }
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 13.5px; margin-bottom: 18px; }
        .alert-success { background: var(--green-s); color: var(--green); }
        .alert-error { background: var(--red-s); color: var(--red); }

        /* ── Shared list toolbar ───────────────────────────────────────── */
        .admin-toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .admin-toolbar-search { width: 220px; }
        .admin-toolbar-field { width: 158px; }
        .admin-toolbar .form-input { min-width: 0; }

        /* ── Mobile drawer + responsive ────────────────────────────────── */
        .hamburger { display: none; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--line); background: #fff; cursor: pointer; color: var(--ink); flex: none; }
        .sidebar-overlay { display: none; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s ease; box-shadow: 0 0 50px rgba(20,25,43,.35); }
            [dir="rtl"] .sidebar { transform: translateX(100%); }
            .sidebar.sidebar-open { transform: translateX(0); }
            .main-area { margin-inline-start: 0; }
            .hamburger { display: flex; }
            .sidebar-overlay { display: block; position: fixed; inset: 0; background: rgba(20,25,43,.5); z-index: 39; }
        }
        @media (max-width: 720px) {
            .content { padding: 16px 14px 50px; }
            .topbar { padding: 11px 14px; gap: 10px; }
            .topbar-date { display: none; }
            .card-head { flex-direction: column; align-items: stretch; gap: 12px; }
            .admin-toolbar { width: 100%; }
            .admin-toolbar .form-input,
            .admin-toolbar-search,
            .admin-toolbar-field { width: 100%; }
            .admin-toolbar-btn { flex: 1; justify-content: center; }
            .card .table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body x-data="{ sidebar: false }" class="h-full">

<div class="sidebar-overlay" x-show="sidebar" @click="sidebar = false" x-cloak></div>

<div class="sidebar" :class="{ 'sidebar-open': sidebar }">
    <div style="padding: 20px 20px 18px; display: flex; align-items: center; gap: 11px;">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));display:grid;place-items:center;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/></svg>
        </div>
        <div>
            <b style="font-size:16px;display:block;letter-spacing:-.02em;">Lapeh</b>
            <span style="font-size:11px;color:var(--slate-2);">{{ __('admin.dispatch_portal') }}</span>
        </div>
    </div>

    <nav style="padding: 6px 12px 24px; display: flex; flex-direction: column; gap: 2px; flex: 1;">
        <span class="nav-group">{{ __('admin.nav_overview') }}</span>
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            {{ __('admin.dashboard') }}
        </a>
        <a href="{{ route('admin.live') }}" class="nav-item {{ request()->routeIs('admin.live') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></svg>
            {{ __('admin.live_deliveries') }}
            @if($liveCount ?? 0) <span class="nav-badge">{{ $liveCount }}</span> @endif
        </a>

        <span class="nav-group">{{ __('admin.nav_operations') }}</span>
        <a href="{{ route('admin.orders') }}" class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            {{ __('admin.orders') }}
        </a>
        <a href="{{ route('admin.senders') }}" class="nav-item {{ request()->routeIs('admin.senders*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
            {{ __('admin.senders') }}
        </a>
        <a href="{{ route('admin.drivers') }}" class="nav-item {{ request()->routeIs('admin.drivers*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
            {{ __('admin.drivers') }}
        </a>
        <a href="{{ route('admin.zones') }}" class="nav-item {{ request()->routeIs('admin.zones') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="3,11 22,2 13,21 11,13 3,11"/></svg>
            {{ __('admin.zones') }}
        </a>

        <span class="nav-group">{{ __('admin.nav_finance') }}</span>
        <a href="{{ route('admin.payments') }}" class="nav-item {{ request()->routeIs('admin.payments') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            {{ __('admin.payments') }}
        </a>
        <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            {{ __('admin.reports') }}
        </a>

        <span class="nav-group">{{ __('admin.nav_support') }}</span>
        <a href="{{ route('admin.complaints') }}" class="nav-item {{ request()->routeIs('admin.complaints*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            {{ __('admin.complaints') }}
            @if($openComplaints ?? 0) <span class="nav-badge">{{ $openComplaints }}</span> @endif
        </a>
        <a href="{{ route('admin.ratings') }}" class="nav-item {{ request()->routeIs('admin.ratings') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26 12,2"/></svg>
            {{ __('admin.ratings') }}
        </a>

        <span class="nav-group">{{ __('admin.nav_system') }}</span>
        <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            {{ __('admin.users') }}
        </a>
        <a href="{{ route('admin.sms') }}" class="nav-item {{ request()->routeIs('admin.sms') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            {{ __('admin.sms') }}
        </a>
        <a href="{{ route('admin.activity-logs') }}" class="nav-item {{ request()->routeIs('admin.activity-logs') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
            {{ __('admin.activity_log') }}
        </a>
        <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            {{ __('admin.settings') }}
        </a>
    </nav>

    <div style="padding: 16px 20px; border-top: 1px solid #283149; display: flex; align-items: center; gap: 10px;">
        <div class="avatar" style="font-size:11px;">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}</div>
        <div style="flex:1;min-width:0;">
            <b style="font-size:12.5px;display:block;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name ?? 'Admin' }}</b>
            <span style="font-size:11px;color:var(--slate-2);">{{ __('admin.administrator') }}</span>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" title="{{ __('admin.logout') }}" style="background:none;border:none;cursor:pointer;color:var(--slate-2);padding:4px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            </button>
        </form>
    </div>
</div>

<div class="main-area">
    <div class="topbar">
        <button class="hamburger" @click="sidebar = true" aria-label="Menu">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="sora" style="font-weight:600;font-size:15px;color:var(--ink);">@yield('title', __('admin.dashboard'))</div>
        <div style="flex:1;"></div>
        @if(session('success'))
            <div class="badge badge-green">✓ {{ session('success') }}</div>
        @endif
        <a href="{{ request()->fullUrlWithQuery(['lang' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}"
           style="color:var(--slate);font-size:12.5px;font-weight:600;text-decoration:none;border:1px solid var(--line);padding:5px 12px;border-radius:999px;background:#fff;">
            {{ __('admin.switch_lang') }}
        </a>
        <div class="topbar-date" style="display:flex;align-items:center;gap:8px;color:var(--slate);font-size:13px;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
            {{ now()->timezone('Asia/Dubai')->format('D, d M Y · H:i') }} GST
        </div>
    </div>

    <div class="content">
        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif
        @yield('content')
    </div>
</div>

</body>
</html>
