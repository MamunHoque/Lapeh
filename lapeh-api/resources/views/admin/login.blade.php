<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('admin.portal_title') }} — {{ __('admin.sign_in') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root { --pink:#FB0E72;--pink-deep:#D1005C;--ink:#14192B;--line:#EAECF2;--bg:#F4F6FB;--slate:#6B748A; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; display:grid; place-items:center; }
        h1,b { font-family:'Sora',sans-serif; }
    </style>
</head>
<body>
    <div style="width:100%;max-width:420px;padding:24px 16px;">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="width:52px;height:52px;border-radius:15px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));display:grid;place-items:center;margin:0 auto 16px;">
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="white"/></svg>
            </div>
            <h1 style="font-size:24px;font-weight:700;letter-spacing:-.02em;color:var(--ink);">{{ __('admin.portal_title') }}</h1>
            <p style="color:var(--slate);font-size:14px;margin-top:4px;">{{ __('admin.sign_in_sub') }}</p>
            <a href="?lang={{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}" style="display:inline-block;margin-top:10px;color:var(--slate);font-size:12.5px;font-weight:600;text-decoration:none;border:1px solid var(--line);padding:4px 12px;border-radius:999px;background:#fff;">{{ __('admin.switch_lang') }}</a>
        </div>

        @if($errors->any())
            <div style="background:#FDE7E7;color:#E03131;padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:13.5px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" style="background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px;">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:var(--slate);margin-bottom:6px;">{{ __('admin.phone_number') }}</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required
                    placeholder="+971 50 000 0000"
                    style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;font-family:inherit;color:var(--ink);outline:none;">
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:var(--slate);margin-bottom:6px;">{{ __('admin.password') }}</label>
                <input type="password" name="password" required
                    placeholder="••••••••"
                    style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;font-family:inherit;color:var(--ink);outline:none;">
            </div>
            <button type="submit" style="width:100%;padding:13px;background:linear-gradient(135deg,var(--pink),var(--pink-deep));color:#fff;border:none;border-radius:12px;font-family:'Sora',sans-serif;font-weight:600;font-size:14px;cursor:pointer;box-shadow:0 12px 22px -10px rgba(251,14,114,.65);">
                {{ __('admin.sign_in') }}
            </button>
        </form>
    </div>
</body>
</html>
