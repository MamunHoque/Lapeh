@php($faq = $data['faq'])
<form method="POST" action="{{ route('admin.settings.update', 'support') }}" class="settings-form card">
    @csrf @method('PUT')
    <div class="settings-section-head">
        <h3 class="sora">{{ __('admin.settings_tab_support') }}</h3>
        <p>{{ __('admin.support_desc') }}</p>
    </div>
    <div class="settings-body">
        <div class="grid-2">
            <div class="form-group"><label class="form-label">{{ __('admin.support_phone') }}</label><input type="text" name="phone" value="{{ $s->get('support.phone') }}" class="form-input"></div>
            <div class="form-group"><label class="form-label">{{ __('admin.support_email') }}</label><input type="email" name="email" value="{{ $s->get('support.email') }}" class="form-input"></div>
            <div class="form-group"><label class="form-label">{{ __('admin.support_whatsapp') }}</label><input type="text" name="whatsapp" value="{{ $s->get('support.whatsapp') }}" class="form-input"></div>
        </div>

        <h4 class="sora" style="font-size:14px;font-weight:700;margin:14px 0 6px;">{{ __('admin.faq_manager') }}</h4>
        <p class="field-help" style="margin-bottom:12px;">{{ __('admin.faq_help') }}</p>

        <div id="faq-rows">
            @foreach($faq as $i => $row)
            <div class="faq-row" style="border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px;position:relative;">
                <button type="button" class="btn btn-danger faq-remove" style="position:absolute;top:10px;inset-inline-end:10px;padding:4px 10px;">✕</button>
                <div class="grid-2">
                    <div class="form-group"><label class="form-label">{{ __('admin.question') }} (EN)</label><input type="text" name="faq[{{ $i }}][q_en]" value="{{ $row['q_en'] ?? '' }}" class="form-input"></div>
                    <div class="form-group"><label class="form-label">{{ __('admin.question') }} (AR)</label><input type="text" name="faq[{{ $i }}][q_ar]" value="{{ $row['q_ar'] ?? '' }}" class="form-input" dir="rtl"></div>
                    <div class="form-group"><label class="form-label">{{ __('admin.answer') }} (EN)</label><textarea name="faq[{{ $i }}][a_en]" rows="2" class="form-input">{{ $row['a_en'] ?? '' }}</textarea></div>
                    <div class="form-group"><label class="form-label">{{ __('admin.answer') }} (AR)</label><textarea name="faq[{{ $i }}][a_ar]" rows="2" class="form-input" dir="rtl">{{ $row['a_ar'] ?? '' }}</textarea></div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" id="faq-add" class="btn btn-ghost">+ {{ __('admin.add_faq') }}</button>
    </div>
    <div class="settings-foot"><button type="submit" class="btn btn-primary">{{ __('admin.save_changes') }}</button></div>
</form>

<template id="faq-template">
    <div class="faq-row" style="border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px;position:relative;">
        <button type="button" class="btn btn-danger faq-remove" style="position:absolute;top:10px;inset-inline-end:10px;padding:4px 10px;">✕</button>
        <div class="grid-2">
            <div class="form-group"><label class="form-label">{{ __('admin.question') }} (EN)</label><input type="text" name="faq[__I__][q_en]" class="form-input"></div>
            <div class="form-group"><label class="form-label">{{ __('admin.question') }} (AR)</label><input type="text" name="faq[__I__][q_ar]" class="form-input" dir="rtl"></div>
            <div class="form-group"><label class="form-label">{{ __('admin.answer') }} (EN)</label><textarea name="faq[__I__][a_en]" rows="2" class="form-input"></textarea></div>
            <div class="form-group"><label class="form-label">{{ __('admin.answer') }} (AR)</label><textarea name="faq[__I__][a_ar]" rows="2" class="form-input" dir="rtl"></textarea></div>
        </div>
    </div>
</template>

<script>
(function(){
    let idx = {{ count($faq) }};
    const rows = document.getElementById('faq-rows');
    const tpl = document.getElementById('faq-template').innerHTML;
    document.getElementById('faq-add').addEventListener('click', function(){
        rows.insertAdjacentHTML('beforeend', tpl.replaceAll('__I__', idx++));
    });
    rows.addEventListener('click', function(e){
        if (e.target.classList.contains('faq-remove')) e.target.closest('.faq-row').remove();
    });
})();
</script>
