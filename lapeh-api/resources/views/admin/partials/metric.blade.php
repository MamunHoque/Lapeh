{{-- Plain metric cell for a metrics strip. Props: $label, $value --}}
<div style="padding:16px 18px;border-inline-end:1px solid var(--line);border-bottom:1px solid var(--line);">
    <div style="font-size:11.5px;color:var(--slate);font-weight:600;">{{ $label }}</div>
    <div class="sora" style="font-size:17px;font-weight:700;color:var(--ink);margin-top:3px;">{{ $value }}</div>
</div>
