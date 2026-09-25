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
    /* Адрес /filter/… , у которого есть посадочная, подменяем на её красивый адрес.
       Иначе покупатель сначала попадал на /filter/… и только оттуда шёл 301 на посадочную —
       лишний переход, на телефоне заметный (Ирина, 23 сентября 2026). Редирект остаётся
       страховкой для старых ссылок и роботов.

       SEF_SET_FILTER_URL — адрес кнопки «Показать»; он же в JS_FILTER_PARAMS, откуда его
       берёт ajax-фильтр. FILTER_URL и FORM_ACTION подменялись и раньше. */
    $ndChpu = function ($url) {
        $url = (string)$url;
        if ($url === '' || strpos($url, '/filter/') === false) {
            return '';
        }
        $row = \Sotbit\Seometa\Orm\SeometaUrlTable::getRow([
            'filter' => ['=REAL_URL' => html_entity_decode($url, ENT_QUOTES, 'UTF-8')],
            'select' => ['NEW_URL'],
            'cache' => ['ttl' => 300],
        ]);
        return $row && $row['NEW_URL'] ? $row['NEW_URL'] : '';
    };

    foreach (array('FILTER_URL', 'FORM_ACTION', 'SEF_SET_FILTER_URL') as $ndKey) {
        if (!empty($arResult[$ndKey]) && ($ndNew = $ndChpu($arResult[$ndKey])) !== '') {
            $arResult[$ndKey] = $ndNew;
        }
    }

    if (!empty($arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL'])
        && ($ndNew = $ndChpu($arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL'])) !== '') {
        $arResult['JS_FILTER_PARAMS']['SEF_SET_FILTER_URL'] = $ndNew;
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

        /* Остальные бренды родителя — ссылками под своим брендом, чтобы из раздела
           бренда можно было переключиться на соседний (Ирина, 25.09.2026). */
        $ndSibBrands = LatitudoFilterRedirect::siblingBrands($arParams['SECTION_ID'], $ndBrandId);
        if ($ndSibBrands && $ndBrand && $ndBrand['CODE'] !== '') {
            /* Бренд ДОБАВЛЯЕТСЯ к текущему, а не заменяет его (Ирина, 25.09.2026:
               «выбираем ещё бренд — первый сбрасывается»): ссылка ведёт на фильтр
               родителя «текущий или этот бренд», с остальными уже выбранными
               пунктами фильтра этой страницы. */
            $ndOwnCode = strtolower($ndBrand['CODE']);
            $ndCurPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            $ndKeep = array();
            if (preg_match('~/filter/(.+?)/(?:apply/)?$~', $ndCurPath, $ndFm)) {
                foreach (explode('/', $ndFm[1]) as $ndPart) {
                    if ($ndPart !== '' && $ndPart !== 'clear' && $ndPart !== 'apply' && strpos($ndPart, 'brand-is-') !== 0) {
                        $ndKeep[] = $ndPart;
                    }
                }
            }
            $ndSibLogos = $ndBrandLogos(array_column($ndSibBrands, 'ID'));
            foreach ($ndSibBrands as $ndI => $ndSb) {
                $ndCodes = array($ndOwnCode, strtolower($ndSb['CODE']));
                sort($ndCodes);
                $ndUrl = $ndSb['PARENT_URL'] . 'filter/' . implode('/', array_merge($ndKeep, array('brand-is-' . implode('-or-', $ndCodes)))) . '/apply/';
                if (isset($ndChpu) && ($ndNew = $ndChpu($ndUrl)) !== '') {
                    $ndUrl = $ndNew;
                }
                $ndSibBrands[$ndI]['URL'] = $ndUrl;
                $ndSibBrands[$ndI]['LOGO'] = $ndSibLogos[$ndSb['ID']] ?? '';
            }
            $arResult['ND_BRAND_SIBLINGS'] = $ndSibBrands;
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
