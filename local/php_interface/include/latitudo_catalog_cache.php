<?php
/**
 * Кеш каталога: что остаётся живым поверх него.
 *
 * До 28 сентября 2026 на сайте было выключено автокеширование компонентов
 * (main.component_cache_on = N), а в /catalog/index.php — кеш каталога
 * (CACHE_TYPE = N). Детальная товара делала ~710 запросов к базе на каждый
 * показ. С кешем — ~26, страница 0.8 → 0.2 с.
 *
 * Но шаблон, попавший в кеш, не выполняется. Всё, что он делал «мимо вывода»
 * (метатеги, robots, выбор склада по городу, хост в ссылках, акции по датам),
 * на попадании в кеш пропало бы или приехало бы от чужого города. Поэтому:
 *   - хост текущего поддомена шаблон печатает меткой #ND_HOST#, подставляем
 *     её здесь, на отдаче страницы;
 *   - акции карточки (свои у каждого города и у каждой даты) считает
 *     component_epilog — он выполняется всегда, — а в шаблоне стоят метки
 *     #ND_PD_SALES# / #ND_PD_NOSALES#;
 *   - склад города уходит в параметры компонента (ND_STORE_ID) — значит, и в
 *     ключ кеша: складов пять, а не 66 городов;
 *   - остатки из 1С (local/stock-sync) пишутся в базу напрямую, мимо Битрикса,
 *     и кеш о них не знает. Скрипт ставит отметку, а мы по ней сбрасываем кеш
 *     каталога — не чаще раза в 10 минут: 1С шлёт остатки каждые 10–30 секунд,
 *     и сброс на каждое обновление оставил бы кеш пустым весь день. Точечно по
 *     товару сбросить нельзя: Битрикс держит кеш всех карточек в одной папке, и
 *     сброс по любому тегу удаляет её целиком.
 *
 * Подключается из local/init.php.
 */

/**
 * Склад города для блока наличия в карточке. Регион не из списка — склад 1
 * (Белгород), как было в шаблоне.
 */
function ndRegionStoreId(): int
{
    static $map = array(
        9277  => 1, // Белгород
        9278  => 2, // Воронеж
        9568  => 4, // Краснодар
        10039 => 3, // Москва
    );

    global $arRegion;
    $regionId = is_array($arRegion) ? (int)($arRegion['ID'] ?? 0) : 0;

    return $map[$regionId] ?? 1;
}

/**
 * Хост текущего поддомена — для меток #ND_HOST#. Из заголовка берём только
 * допустимые символы: он уходит в разметку.
 */
function ndCurrentHost(): string
{
    return preg_replace('~[^a-z0-9.:\-]~i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
}

/**
 * Акции товара для нижнего ряда карточки: активные на сегодня, общие или
 * привязанные к текущему городу. Возвращает готовую разметку блока (или '').
 * Раньше считалось прямо в template.php — перенесено сюда без изменений.
 */
function ndProductSalesHtml(int $productId): string
{
    if ($productId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return '';
    }

    $filter = array(
        'IBLOCK_ID' => 17,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        'PROPERTY_LINK_GOODS' => $productId,
    );
    /* Регион: акция без привязки — общая. ИЛИ обязательно подгруппой, иначе оно
       распространится на весь фильтр вместе с IBLOCK_ID (та же грабля, что на главной). */
    if (class_exists('CNextRegionality')) {
        $region = CNextRegionality::getCurrentRegion();
        $regionId = is_array($region) ? (int)$region['ID'] : 0;
        if ($regionId) {
            $filter[] = array(
                'LOGIC' => 'OR',
                array('PROPERTY_LINK_REGION' => $regionId),
                array('PROPERTY_LINK_REGION' => false),
            );
        }
    }

    $sales = array();
    $rs = CIBlockElement::GetList(
        array('SORT' => 'ASC', 'ID' => 'DESC'),
        $filter,
        false,
        array('nTopCount' => 6),
        array('ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'PROPERTY_IMAGE_FOR_CATALOG')
    );
    /* GetNext, а не Fetch: подстановку #SITE_DIR#/#ELEMENT_CODE# в
       DETAIL_PAGE_URL делает только он — с Fetch ссылки на акции уходили
       с неразобранным шаблоном адреса и не открывались. */
    while ($sale = $rs->GetNext(false, false)) {
        /* Берём обычный баннер акции (как на /sale/), а не IMAGE_FOR_CATALOG:
           тот нарисован вертикальным — под вставку в сетку каталога. */
        $picId = (int)($sale['PREVIEW_PICTURE'] ?: $sale['PROPERTY_IMAGE_FOR_CATALOG_VALUE']);
        /* Качество 82 седьмым параметром: в настройках модуля стоит 100, и баннер
           акции весил под 200 КБ. */
        $sale['ND_PIC'] = $picId
            ? CFile::ResizeImageGet($picId, array('width' => 620, 'height' => 620), BX_RESIZE_IMAGE_PROPORTIONAL, true, false, false, 82)
            : false;
        $sales[] = $sale;
    }
    if (!$sales) {
        return '';
    }

    ob_start();
    ?>
    <div class="nd-pd__sales-block">
        <h2 class="nd-pd__h2">Акции</h2>
        <div class="nd-pd__sales">
            <? foreach ($sales as $sale): ?>
                <a class="nd-pd__sale" href="<?= $sale['DETAIL_PAGE_URL'] ?>">
                    <? if ($sale['ND_PIC']): ?>
                        <img src="<?= $sale['ND_PIC']['src'] ?>" alt="<?= htmlspecialcharsbx($sale['NAME']) ?>" loading="lazy">
                    <? else: ?>
                        <span class="nd-pd__sale-name"><?= htmlspecialcharsbx($sale['NAME']) ?></span>
                    <? endif; ?>
                </a>
            <? endforeach; ?>
        </div>
    </div>
    <?
    return ob_get_clean();
}

/**
 * OnEndBufferContent: метки, которые кешированный шаблон оставляет вместо
 * живых данных. Акции кладёт в $GLOBALS['ND_PD_SALES_HTML'] component_epilog
 * карточки; нет его (метка без карточки) — акций нет.
 */
function ndCatalogCacheTokens(&$content)
{
    if (!is_string($content) || strpos($content, '#ND_') === false) {
        return;
    }

    $sales = (string)($GLOBALS['ND_PD_SALES_HTML'] ?? '');
    $content = str_replace(
        array('#ND_HOST#', '#ND_PD_SALES#', '#ND_PD_NOSALES#'),
        array(ndCurrentHost(), $sales, $sales === '' ? ' nd-pd__bottom--nosales' : ''),
        $content
    );
}

/**
 * OnEndBufferContent: второй раз один и тот же файл нового дизайна не
 * подключаем.
 *
 * Шаблоны печатают свои стили и скрипты прямо в разметке и защищаются от
 * повтора константой (ND_CATALOG_ASSETS, ND_UI_JS…). Но на попадании в кеш
 * шаблон не выполняется, константа не встаёт, и следующий список на той же
 * странице подключил бы newdesign-catalog.js ещё раз — со вторыми
 * обработчиками кликов («Показать ещё» грузил бы страницу дважды).
 * Оставляем первое подключение каждого файла, как и было без кеша.
 */
function ndDedupNewdesignAssets(&$content)
{
    if (!is_string($content) || stripos($content, '</head>') === false) {
        return;   // ajax, json, xml — не трогаем
    }

    $seen = array();
    $keepFirst = function ($m) use (&$seen) {
        $key = strtolower($m[1]);
        if (isset($seen[$key])) {
            return '';
        }
        $seen[$key] = true;
        return $m[0];
    };

    $content = preg_replace_callback(
        '~<script\b[^>]*\bsrc="([^"?]*/js/newdesign-[\w.-]+\.js)(?:\?[^"]*)?"[^>]*>\s*</script>~i',
        $keepFirst,
        $content
    );
    $content = preg_replace_callback(
        '~<link\b(?=[^>]*\brel="stylesheet")[^>]*\bhref="([^"?]*/css/newdesign-[\w.-]+\.css)(?:\?[^"]*)?"[^>]*>~i',
        $keepFirst,
        $content
    );
}

/** Отметка «остатки менялись» — её ставит local/stock-sync/index.php. */
const ND_STOCK_DIRTY_FLAG = '/local/stock-sync/logs/cache_dirty.flag';

/** Не чаще раза в столько секунд сбрасываем кеш каталога по остаткам. */
const ND_STOCK_FLUSH_EVERY = 600;

/**
 * OnPageStart: остатки менялись → сбросить кеш каталога (не чаще раза в
 * ND_STOCK_FLUSH_EVERY). На обычном показе это одна проверка файла.
 */
function ndStockCacheFlush()
{
    $flag = $_SERVER['DOCUMENT_ROOT'] . ND_STOCK_DIRTY_FLAG;
    if (!is_file($flag)) {
        return;
    }

    $stamp = $flag . '.last';
    $last = is_file($stamp) ? (int)filemtime($stamp) : 0;
    if (time() - $last < ND_STOCK_FLUSH_EVERY) {
        return;
    }

    // Забираем отметку переименованием: параллельный запрос её уже не увидит,
    // а новая отметка от 1С, пришедшая после, дождётся следующего сброса.
    $work = $flag . '.' . getmypid();
    if (!@rename($flag, $work)) {
        return;
    }
    @unlink($work);
    @touch($stamp);

    if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
        return;
    }
    // Инфоблоки каталога — товары и предложения. Их тег сбрасывает кеш
    // карточек, списков и фильтра.
    $rs = \Bitrix\Catalog\CatalogIblockTable::getList(array('select' => array('IBLOCK_ID')));
    while ($row = $rs->fetch()) {
        CIBlock::clearIblockTagCache((int)$row['IBLOCK_ID']);
    }
}
