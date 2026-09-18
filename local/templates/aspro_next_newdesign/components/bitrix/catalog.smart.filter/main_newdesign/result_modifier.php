<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParams["POPUP_POSITION"] = (isset($arParams["POPUP_POSITION"]) && in_array($arParams["POPUP_POSITION"], array("left", "right"))) ? $arParams["POPUP_POSITION"] : "left";

foreach($arResult["ITEMS"] as $key => $arItem)
{
	/* Группу «Наши предложения» (свойство HIT) в фильтре не показываем — Ирина,
	   4 сентября 2026. Это служебная пометка: её единственное живое значение
	   «Хиты месяца» отбирает товары для вкладки блока «Может заинтересовать»,
	   покупателю оно ничего не говорит (по той же причине эта метка не
	   печатается на карточке — см. catalog_blockcolors_newdesign/template.php).

	   Отсекаем по CODE, а не по названию: название правится из админки.
	   Свойство при этом остаётся рабочим — прячем только его блок в фильтре
	   нового дизайна, старый шаблон фильтра не трогаем. */
	if($arItem["CODE"] === "HIT")
	{
		unset($arResult["ITEMS"][$key]);
		continue;
	}

	/*unset empty values*/
	if (
		(
		 ($arItem["DISPLAY_TYPE"] == "A" || isset($arItem["PRICE"]))
		 && ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] <= 0)
		)
		|| !$arItem["VALUES"]
	)
		unset($arResult["ITEMS"][$key]);
	/**/
	
	if ($arItem["CODE"] === "IN_STOCK") {
		if (
			isset($arResult["ITEMS"][$key]["VALUES"]) 
			&& is_array($arResult["ITEMS"][$key]["VALUES"])
			&& $arResult["ITEMS"][$key]["VALUES"]
		) {
			sort($arResult["ITEMS"][$key]["VALUES"]);
			$arResult["ITEMS"][$key]["VALUES"][0]["VALUE"] = $arItem["NAME"];
		}
	}
}

/* ======================= логотипы брендов в фильтре =======================
   Макет Figma «Чистовик»: «Фильтры» 21408:72598 и «Бренды» 21408:72662 —
   у LATITUDO и EasyDecking перед названием стоит квадратный значок 20×20.

   Сам компонент картинку не отдаёт: FILE он заполняет только у свойств типа
   «справочник» (catalog.smart.filter/class.php, ветка "Ux"), а «Производитель
   / Бренд» — привязка к элементу инфоблока брендов. Ключи $arItem["VALUES"] у
   свойства типа E — это ID элементов, по ним и забираем данные брендов.

   Картинку берём НЕ из инфоблока: там у брендов лежат широкие надписи
   (LATITUDO 40×12, EasyDecking 90×48, Nextwood 150×15) — в квадрат 20×20 они
   превращаются в нечитаемую полоску. Значки макета — отдельные квадратные
   марки, они выгружены из Figma в images/newdesign/brands/<символьный код>.png.
   Чтобы завести значок новому бренду, достаточно положить туда файл с именем
   его символьного кода; бренды без файла остаются просто текстом — так и в
   макете. */
$ndBrandLogos = function(array $ids) {
	$ids = array_values(array_unique(array_map('intval', $ids)));
	sort($ids);
	if (!$ids)
		return array();

	$cache = \Bitrix\Main\Data\Cache::createInstance();
	$cacheId = 'nd_brand_logos_'.md5(implode(',', $ids));
	$cacheDir = '/nd/brand_logos';

	if ($cache->initCache(86400, $cacheId, $cacheDir))
		return $cache->getVars();

	$dir = SITE_TEMPLATE_PATH.'/images/newdesign/brands/';
	$logos = array();
	$rs = CIBlockElement::GetList(array(), array('ID' => $ids), false, false, array('ID', 'CODE'));
	while ($el = $rs->Fetch()) {
		$code = trim((string)$el['CODE']);
		if ($code === '')
			continue;
		foreach (array('svg', 'png') as $ext) {
			$path = $dir.$code.'.'.$ext;
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
				$logos[$el['ID']] = $path;
				break;
			}
		}
	}

	$cache->startDataCache();
	$cache->endDataCache($logos);

	return $logos;
};

foreach ($arResult['ITEMS'] as $key => $arItem) {
	if (!isset($arItem['PROPERTY_TYPE']) || $arItem['PROPERTY_TYPE'] !== 'E')
		continue;
	if (empty($arItem['VALUES']) || !is_array($arItem['VALUES']))
		continue;

	$logos = $ndBrandLogos(array_keys($arItem['VALUES']));
	if (!$logos)
		continue;

	foreach ($arItem['VALUES'] as $val => $ar) {
		if (isset($logos[(int)$val]))
			$arResult['ITEMS'][$key]['VALUES'][$val]['ND_LOGO'] = $logos[(int)$val];
	}
}

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

// sort
include 'sort.php';

global $sotbitFilterResult;
$sotbitFilterResult = $arResult;

if (\Bitrix\Main\Loader::includeModule('sotbit.seometa')) {
    $newFilterUrl = \Sotbit\Seometa\Orm\SeometaUrlTable::getRow([
        'filter' => ['=REAL_URL' => $arResult['FILTER_URL']],
        'select' => ['NEW_URL'],
        'cache' => ['ttl' => 300],
    ])['NEW_URL'];

    if ($newFilterUrl) {
        $arResult['FILTER_URL'] = $newFilterUrl;
    }

    $newActionUrl = \Sotbit\Seometa\Orm\SeometaUrlTable::getRow([
        'filter' => ['=REAL_URL' => $arResult['FORM_ACTION']],
        'select' => ['NEW_URL'],
        'cache' => ['ttl' => 300],
    ])['NEW_URL'];

    if ($newActionUrl) {
        $arResult['FORM_ACTION'] = $newActionUrl;
    }
}

/* Фильтр ровно по одному бренду, у которого есть свой раздел, ведёт сразу на
   адрес раздела, а не на /filter/brand-is-…/: страница фильтра была дублем
   раздела и отнимала у него запросы (Ирина, 18 сентября 2026). Решает
   LatitudoFilterRedirect — тот же класс держит 301 со старых адресов фильтра
   (local/init.php, ndFilterBrandToSection). Работает и для ajax-ответа
   фильтра (ajax.php отдаёт этот же $arResult), и для первой отрисовки. */
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_filter_redirect.php';
foreach (array('FILTER_URL', 'SEF_SET_FILTER_URL') as $ndUrlKey) {
    if (!empty($arResult[$ndUrlKey])) {
        $ndSectionUrl = LatitudoFilterRedirect::sectionForFilterUrl($arResult[$ndUrlKey]);
        if ($ndSectionUrl !== '') {
            $arResult[$ndUrlKey] = htmlspecialcharsbx($ndSectionUrl);
        }
    }
}
if (!empty($arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL'])) {
    $ndSectionUrl = LatitudoFilterRedirect::sectionForFilterUrl($arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL']);
    if ($ndSectionUrl !== '') {
        $arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL'] = $ndSectionUrl;
    }
}

/* Раздел бренда (все товары раздела — одного бренда), Ирина, 18 сентября 2026:
   - при первой отрисовке галочка этого бренда стоит сразу — сюда приводит выбор
     бренда в фильтре раздела выше, и посетитель должен видеть, что отбор
     применён (если бренд ещё не выбран);
   - «Сбросить фильтры» ведёт в РОДИТЕЛЬСКИЙ раздел: сброс внутри раздела бренда
     вёл на тот же раздел, где галочка снова стоит, и не работал;
   - сняли галочку бренда (ajax-пересчёт) — «Показать» ведёт в родителя с
     остальными выбранными пунктами (LatitudoFilterRedirect::moveToParent). */
if (!empty($arParams['SECTION_ID'])) {
    $ndBrandId = LatitudoFilterRedirect::brandOfSection($arParams['SECTION_ID']);
    if ($ndBrandId > 0 && \Bitrix\Main\Loader::includeModule('iblock')) {
        $ndBrand = CIBlockElement::GetList(array(), array('ID' => $ndBrandId), false, array('nTopCount' => 1), array('ID', 'CODE'))->Fetch();
        $ndIsAjax = !empty($_REQUEST['ajax']);
        $ndBrandChecked = false;
        foreach ($arResult['ITEMS'] as $ndPid => $ndItem) {
            if (($ndItem['CODE'] ?? '') !== 'BRAND' || empty($ndItem['VALUES'])) {
                continue;
            }
            foreach ($ndItem['VALUES'] as $ndVal) {
                if (!empty($ndVal['CHECKED'])) {
                    $ndBrandChecked = true;
                    break;
                }
            }
            if (!$ndBrandChecked && !$ndIsAjax) {
                foreach ($ndItem['VALUES'] as $ndKey => $ndVal) {
                    if ((string)($ndVal['FACET_VALUE'] ?? '') === (string)$ndBrandId
                        || ($ndBrand && ($ndVal['URL_ID'] ?? '') === $ndBrand['CODE'])) {
                        $arResult['ITEMS'][$ndPid]['VALUES'][$ndKey]['CHECKED'] = true;
                        $ndBrandChecked = true;
                    }
                }
            }
            break;
        }

        list($ndSectionUrl, $ndParentUrl) = LatitudoFilterRedirect::sectionAndParentUrl($arParams['SECTION_ID']);
        if ($ndParentUrl !== '') {
            $arResult['SEF_DEL_FILTER_URL'] = htmlspecialcharsbx($ndParentUrl);
            if (isset($arResult['JS_FILTER_PARAMS']) && is_array($arResult['JS_FILTER_PARAMS'])) {
                $arResult['JS_FILTER_PARAMS']['SEF_DEL_FILTER_URL'] = $ndParentUrl;
            }
            if ($ndIsAjax && !$ndBrandChecked) {
                foreach (array('FILTER_URL', 'SEF_SET_FILTER_URL') as $ndUrlKey) {
                    if (!empty($arResult[$ndUrlKey])) {
                        $arResult[$ndUrlKey] = LatitudoFilterRedirect::moveToParent($arResult[$ndUrlKey], $ndSectionUrl, $ndParentUrl);
                    }
                }
            }
        }
    }
}
