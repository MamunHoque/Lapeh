@php($backups = $data['backups'])
<div class="card">
    <div class="settings-section-head"><h3 class="sora">{{ __('admin.settings_tab_database') }}</h3><p>{{ __('admin.database_desc') }}</p></div>
    <div class="settings-body">
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('admin.settings.backup.create') }}" class="settings-form">@csrf
                <button type="submit" class="btn btn-primary">{{ __('admin.create_backup') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.cache.clear') }}" class="settings-form">@csrf<input type="hidden" name="what" value="cache">
                <button type="submit" class="btn btn-ghost">{{ __('admin.clear_app_cache') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.cache.clear') }}" class="settings-form">@csrf<input type="hidden" name="what" value="config">
                <button type="submit" class="btn btn-ghost">{{ __('admin.clear_config_cache') }}</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3 class="sora" style="font-size:15px;font-weight:700;">{{ __('admin.recent_backups') }} ({{ __('admin.retention_note') }})</h3></div>
    <table class="table">
        <thead><tr><th>{{ __('admin.file') }}</th><th>{{ __('admin.size') }}</th><th>{{ __('admin.created') }}</th><th></th></tr></thead>
        <tbody>
            @forelse($backups as $b)
            <tr>
                <td class="mono">{{ $b['name'] }}</td>
                <td>{{ $b['size'] }}</td>
                <td>{{ $b['created_at']->timezone('Asia/Dubai')->format('d M Y · H:i') }}</td>
                <td style="text-align:end;">
                    <a href="{{ route('admin.settings.backup.download', $b['name']) }}" class="btn btn-ghost" style="padding:5px 12px;">{{ __('admin.download') }}</a>
                    <form method="POST" action="{{ route('admin.settings.backup.delete', $b['name']) }}" class="settings-form" style="display:inline;" onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding:5px 12px;">{{ __('admin.delete') }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:var(--slate);padding:24px;">{{ __('admin.no_backups') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
