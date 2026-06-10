{{--
  Copy-to-clipboard button.
  Params: $link (required), $iconOnly (bool, default false).
  Uses the async Clipboard API with an execCommand fallback for non-secure origins.
--}}
@php($iconOnly = $iconOnly ?? false)
<button type="button"
    x-data="{ copied: false, link: @js($link),
        copy() {
            const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(this.link).then(done).catch(() => this.fallback(done));
            } else { this.fallback(done); }
        },
        fallback(done) {
            const t = document.createElement('textarea');
            t.value = this.link; t.style.position = 'fixed'; t.style.opacity = '0';
            document.body.appendChild(t); t.focus(); t.select();
            try { document.execCommand('copy'); } catch (e) {}
            t.remove(); done();
        }
    }"
    @click="copy()"
    class="btn btn-ghost"
    style="padding:6px 12px;font-size:12px;gap:6px;"
    :title="copied ? @js(__('admin.copied')) : @js(__('admin.copy_link'))">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!copied">
        <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
    </svg>
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--green)" stroke-width="2.5" x-show="copied" x-cloak>
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    @unless($iconOnly)
    <span x-text="copied ? @js(__('admin.copied')) : @js(__('admin.copy_link'))"></span>
    @endunless
</button>
