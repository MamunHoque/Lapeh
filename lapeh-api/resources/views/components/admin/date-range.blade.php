@php($r = request('range'))
{{-- Date-range preset + custom from/to. The custom inputs reveal when "Custom" is picked. --}}
<select name="range" class="form-input form-select admin-toolbar-field"
        onchange="this.closest('form').querySelectorAll('.dr-custom').forEach(el=>el.style.display=this.value==='custom'?'':'none')">
    <option value="">{{ __('admin.dr_all_time') }}</option>
    <option value="today" @selected($r==='today')>{{ __('admin.dr_today') }}</option>
    <option value="yesterday" @selected($r==='yesterday')>{{ __('admin.dr_yesterday') }}</option>
    <option value="7d" @selected($r==='7d')>{{ __('admin.dr_7d') }}</option>
    <option value="month" @selected($r==='month')>{{ __('admin.dr_month') }}</option>
    <option value="year" @selected($r==='year')>{{ __('admin.dr_year') }}</option>
    <option value="custom" @selected($r==='custom')>{{ __('admin.dr_custom') }}</option>
</select>
<input type="date" name="from" value="{{ request('from') }}" class="form-input admin-toolbar-field dr-custom"
       style="{{ $r==='custom' ? '' : 'display:none;' }}">
<input type="date" name="to" value="{{ request('to') }}" class="form-input admin-toolbar-field dr-custom"
       style="{{ $r==='custom' ? '' : 'display:none;' }}">
