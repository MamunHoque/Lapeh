{{-- KPI tile. Props: $label, $value, $sub (optional), $grad (CSS background) --}}
<div class="kpi" style="background:{{ $grad ?? 'linear-gradient(135deg,var(--pink),var(--pink-deep))' }};">
    <div style="font-size:12px;opacity:.85;">{{ $label }}</div>
    <div class="sora" style="font-size:22px;font-weight:800;margin-top:6px;line-height:1.1;">{{ $value }}</div>
    @if(!empty($sub))<div style="font-size:11px;opacity:.8;margin-top:4px;">{{ $sub }}</div>@endif
</div>
