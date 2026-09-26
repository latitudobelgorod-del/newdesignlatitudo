<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/* Раздел бренда («Ограждения Polivan», «Террасная доска NEXTWOOD»…): в блоке
   «Производитель / Бренд» кроме своего бренда — остальные бренды родителя
   галочками. Отмечать можно несколько, «Показать»/«Применить» ведёт на фильтр
   РОДИТЕЛЯ brand-is-<бренды через -or-> + остальные выбранные пункты (Ирина,
   25.09.2026: «невозможно выбрать и то и то со страницы бренда»).

   Живёт в эпилоге, а не в шаблоне: шаблон фильтра кешируется компонентом на
   двое суток, и любая его правка требовала сброса кеша фильтра по всему
   сайту — после такого сброса 25.09 сайт заметно тормозил, пока кеш
   собирался заново. Эпилог выполняется на каждом хите мимо кеша; данные
   берутся из своего кеша LatitudoFilterRedirect (сутки, теги ИБ 19/12). */

if (empty($arParams['SECTION_ID']) || !empty($_REQUEST['ajax'])) {
    return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_filter_redirect.php';

$ndBrandId = LatitudoFilterRedirect::brandOfSection($arParams['SECTION_ID']);
if ($ndBrandId <= 0) {
    return;
}
$ndSibs = LatitudoFilterRedirect::siblingBrands($arParams['SECTION_ID'], $ndBrandId);
if (!$ndSibs || !\Bitrix\Main\Loader::includeModule('iblock')) {
    return;
}
$ndOwn = CIBlockElement::GetList(array(), array('ID' => $ndBrandId), false, array('nTopCount' => 1), array('ID', 'CODE'))->Fetch();
$ndOwnCode = $ndOwn ? strtolower(trim((string)$ndOwn['CODE'])) : '';
if ($ndOwnCode === '') {
    return;
}

/* Значок бренда — тот же файл, что у пунктов фильтра (result_modifier). */
$ndLogoDir = SITE_TEMPLATE_PATH . '/images/newdesign/brands/';
$ndItems = array();
foreach ($ndSibs as $ndSb) {
    $ndLogo = '';
    foreach (array('svg', 'png') as $ndExt) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $ndLogoDir . $ndSb['CODE'] . '.' . $ndExt)) {
            $ndLogo = $ndLogoDir . $ndSb['CODE'] . '.' . $ndExt;
            break;
        }
    }
    $ndItems[] = array(
        'code' => strtolower($ndSb['CODE']),
        'name' => $ndSb['NAME'],
        'parent' => $ndSb['PARENT_URL'],
        'logo' => $ndLogo,
        /* Хвост имени галочки в фильтре (NEXT_SMART_FILTER_134_<ctl>) — так его
           считает catalog.smart.filter для значения-привязки. */
        'ctl' => (string)abs(crc32((string)$ndSb['ID'])),
    );
}
?>
<script>
(function () {
    var data = <?=json_encode(array('own' => $ndOwnCode, 'items' => $ndItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)?>;
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]; }); }

    function build() {
        var box = document.querySelector('.bx_filter_parameters_box[data-prop_code=brand] .bx_filter_parameters_box_container');
        if (!box) return;
        /* Ссылки прежней версии из закешированной разметки фильтра — убираем. */
        [].forEach.call(box.querySelectorAll('a.nd-filter__brandlink'), function (a) { a.parentNode.removeChild(a); });
        if (box.querySelector('label.nd-filter__brandlink')) return;
        var clb = box.querySelector('.clb'), last = null;
        /* Каждый бренд — настоящая галочка перед label, как у пунктов компонента:
           тогда на них действуют те же стили (input + label), и свой бренд не
           стоит особняком (Ирина, 26.09.2026). form="…" уводит галочку из формы
           фильтра — компонент её не видит и в свой запрос не кладёт. */
        [].forEach.call(box.querySelectorAll('label.last'), function (l) { l.classList.remove('last'); });
        data.items.forEach(function (it) {
            var id = 'nd_sib_brand_' + it.code.replace(/[^a-z0-9_]/g, '_');
            var inp = document.createElement('input');
            inp.type = 'checkbox';
            inp.id = id;
            inp.className = 'nd-filter__sibinput';
            inp.setAttribute('form', 'nd_sib_brand_none');
            inp.setAttribute('data-nd-code', it.code);
            inp.setAttribute('data-nd-ctl', it.ctl);
            var l = document.createElement('label');
            l.className = 'bx_filter_param_label nd-filter__brandlink';
            l.setAttribute('for', id);
            l.setAttribute('data-role', 'label_' + id);
            l.innerHTML = '<span class="bx_filter_input_checkbox">'
                + (it.logo ? '<img class="nd-filter__logo" src="' + esc(it.logo) + '" width="20" height="20" alt="' + esc(it.name) + '" loading="lazy" />' : '')
                + '<span class="bx_filter_param_text" title="' + esc(it.name) + '">' + esc(it.name) + '</span></span>';
            inp.addEventListener('change', recount);
            var before = clb && clb.parentNode === box ? clb : null;
            box.insertBefore(inp, before);
            box.insertBefore(l, before);
            last = l;
        });
        if (last) last.classList.add('last');
    }

    function checkedSibs() { return document.querySelectorAll('input.nd-filter__sibinput:checked'); }

    function ownInput() {
        return document.querySelector('.bx_filter_parameters_box[data-prop_code=brand] input[type=checkbox]:not(.nd-filter__sibinput)');
    }

    /* Плашка «Выбрано N | Показать» у пункта, как после клика по обычной
       галочке. Без неё клик по соседнему бренду ничем не отзывался, кроме
       галочки, — казалось, что сайт завис (Ирина, 26.09.2026). N считаем тем же
       запросом, что и сам фильтр, но у родителя: значения формы + галочки
       соседних брендов. */
    var seq = 0;
    function recount() {
        var modef = document.getElementById('modef'), sf = window.smartFilter, own = ownInput();
        if (!modef || !own) return;
        var on = checkedSibs(), my = ++seq;
        if (!on.length) {
            /* Соседних не осталось — возвращаем число текущего раздела. */
            if (sf && sf.reload) sf.reload(own);
            return;
        }
        var holder = own.closest('.bx_filter_parameters_box');
        holder = holder && holder.querySelector('.bx_filter_container_modef');
        if (holder && modef.parentNode !== holder) holder.appendChild(modef);
        setNum('…');
        modef.style.display = 'inline-block';
        if (!sf || !sf.gatherInputsValues || !window.BX || !BX.ajax) return;

        var values = [{name: 'ajax', value: 'y'}];
        sf.gatherInputsValues(values, BX.findChildren(own.form, {'tag': new RegExp('^(input|select)$', 'i')}, true));
        values = values.filter(function (v) { return v.name; });
        var prefix = own.name.replace(/_\d+$/, '');
        [].forEach.call(on, function (i) { values.push({name: prefix + '_' + i.getAttribute('data-nd-ctl'), value: 'Y'}); });
        BX.ajax.loadJSON(data.items[0].parent, sf.values2post(values), function (res) {
            if (my !== seq || !res || res.ELEMENT_COUNT === undefined) return;
            var n = parseInt(res.ELEMENT_COUNT, 10) || 0;
            setNum(n);
            if (typeof window.mobileFilterNum === 'function') window.mobileFilterNum(n);
        });
    }
    function setNum(n) {
        ['modef_num', 'modef_num_mobile'].forEach(function (id) { var e = document.getElementById(id); if (e) e.innerHTML = n; });
    }
    /* Пересчёт обычной галочкой вписывает число текущего раздела — пока
       отмечен соседний бренд, пересчитываем у родителя. */
    if (window.BX && BX.addCustomEvent) BX.addCustomEvent('onSmartFilterAjaxCompleted', function () {
        if (checkedSibs().length) recount();
    });

    function target() {
        var on = checkedSibs();
        if (!on.length) return '';
        var codes = [].map.call(on, function (i) { return i.getAttribute('data-nd-code'); });
        var own = ownInput();
        if (!own || own.checked) codes.push(data.own);
        codes.sort();
        /* Прочие выбранные пункты — из адреса, который компонент уже посчитал. */
        var a = document.querySelector('#modef a[href], #modef_mobile a[href]');
        var cur = (a && a.getAttribute('href').indexOf('/filter/') !== -1) ? a.getAttribute('href') : location.pathname;
        var keep = [], m = cur.split('?')[0].match(/\/filter\/(.+?)\/?(apply\/?)?$/);
        if (m) m[1].split('/').forEach(function (p) {
            if (p && p !== 'clear' && p !== 'apply' && p.indexOf('brand-is-') !== 0) keep.push(p);
        });
        keep.push('brand-is-' + codes.join('-or-'));
        return data.items[0].parent + 'filter/' + keep.join('/') + '/apply/';
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('#set_filter, .nd-fm-bar__apply, #modef a, #modef_mobile a');
        if (!b) return;
        var url = target();
        if (!url) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        location.href = url;
    }, true);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', build); else build();
})();
</script>
