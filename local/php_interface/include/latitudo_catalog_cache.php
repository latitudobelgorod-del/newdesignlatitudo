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
 * Текущий адрес страницы — параметром компонента, чей шаблон что-то отмечает
 * по адресу (текущий тег посадочной в landings_list): так адрес входит в ключ
 * кеша. Без этого первый открытый тег застревал «текущим» на всех соседних
 * посадочных (28.09.2026, «Черные» горели на «Коричневых»). Путь и из
 * REQUEST_URI, и GetCurDir — как сверяет шаблон: на посадочной Сотбит
 * подменяет REQUEST_URI техническим адресом фильтра.
 */
function ndCurrentPathKey(): string
{
    global $APPLICATION;
    $path = rtrim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/') . '/';
    $dir = is_object($APPLICATION) ? rtrim((string)$APPLICATION->GetCurDir(), '/') . '/' : '';
    return $path . '|' . $dir;
}

/**
 * Признак «посетитель с utm-меткой» + город — параметром компонента, чей шаблон
 * подменяет контакты по метке (showrooms_newdesign на «О компании»). Иначе
 * кеш, собранный посетителем с меткой, показал бы подменные телефон и почту
 * всем остальным, а обратной замены на выходе нет. Город — потому что
 * подменная почта у каждого региона своя.
 */
function ndUtmCacheKey(): string
{
    if (!function_exists('ndIsUtmVisit') || !ndIsUtmVisit()) {
        return 'n';
    }
    global $arRegion;
    return 'utm-' . (is_array($arRegion) ? (int)($arRegion['ID'] ?? 0) : 0);
}

/** ID текущего региона — в ключ кеша компонентов, чей шаблон выводит что-то своё для города. */
function ndRegionCacheKey(): int
{
    global $arRegion;
    return is_array($arRegion) ? (int)($arRegion['ID'] ?? 0) : 0;
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

    /* #ND_V:/путь/к/файлу# — версия файла (filemtime) для ?v= у css/js, которые
       подключает кешированный шаблон: версия, записанная в кеш, оставалась
       старой после выкладки, и браузер брал из своего кеша прежний скрипт
       (14.10.2026, кнопка «ещё» в линейке опор). */
    if (strpos($content, '#ND_V:') !== false) {
        $content = preg_replace_callback('~#ND_V:(/[\w./-]+)#~', static function ($m) {
            $file = $_SERVER['DOCUMENT_ROOT'] . $m[1];
            return (strpos($m[1], '..') === false && is_file($file)) ? (string)filemtime($file) : '';
        }, $content);
    }
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

/**
 * OnEndBufferContent: живые остатки по складам в плитках списков.
 *
 * Плитки (catalog_blockcolors*, products_slider) берут склады в
 * result_modifier, а он на попадании в кеш не выполняется: цифры в плитке
 * жили до сброса кеша (ndStockCacheFlush, до 10 минут), тогда как карточка
 * товара читает склады из базы на каждом показе. 10.11.2026 в списке, в
 * карточке и в metatool одновременно видели три разных числа.
 *
 * Здесь на отдаче страницы (и ajax-порций «Показать ещё») берём одним
 * запросом остатки всех предложений, что есть на странице, и переписываем:
 *   - data-offers-stores — из него JS рисует «В наличии ▾» у товаров с
 *     предложениями и при смене длины;
 *   - плашки складов в .stores-list — из них JS рисует «В наличии» у товаров
 *     без предложений. Плашки трогаем, только если точно знаем, чьи они:
 *     у товара без предложений — сам товар, иначе — data-current-offer, если
 *     он есть среди предложений (у старых копий шаблона там бывает ID товара).
 */
/**
 * Порядок складов в плитках — как в карточке (main_detail_store_newdesign):
 * Москва, Краснодар, Воронеж, Белгород (Ирина, 10.11.2026). Остальные — в конец.
 */
function ndStorePos(int $storeId): int
{
    static $order = array(3 => 0, 4 => 1, 2 => 2, 1 => 3);
    return $order[$storeId] ?? 100 + $storeId;
}

function ndLiveListStores(&$content)
{
    if (!is_string($content) || strpos($content, 'class="product-item-stores"') === false) {
        return;
    }

    $re = '~(<div class="product-item-stores"\s+data-product-id="(\d+)"\s+data-current-offer="(\d*)"\s+'
        . 'data-offers-stores=\')([^\']*)(\'>\s*<div class="stores-list" id="stores-list-\d+">)(.*?)(</div>)~s';
    if (!preg_match_all($re, $content, $all, PREG_SET_ORDER)) {
        return;
    }

    // Все ID, чьи остатки нужны странице
    $ids = array();
    foreach ($all as $m) {
        $ids[(int)$m[2]] = true;
        $offers = json_decode(htmlspecialchars_decode($m[4], ENT_QUOTES), true);
        if (is_array($offers)) {
            foreach ($offers as $oid => $_) {
                $ids[(int)$oid] = true;
            }
        }
    }
    unset($ids[0]);
    if (!$ids) {
        return;
    }

    try {
        $db = \Bitrix\Main\Application::getConnection();
        $amounts = array();
        $rs = $db->query('SELECT PRODUCT_ID, STORE_ID, AMOUNT FROM b_catalog_store_product WHERE PRODUCT_ID IN ('
            . implode(',', array_map('intval', array_keys($ids))) . ')');
        while ($row = $rs->fetch()) {
            $amounts[(int)$row['PRODUCT_ID']][(int)$row['STORE_ID']] = (float)$row['AMOUNT'];
        }
        // Название склада в плашке → ID склада (так же режет название result_modifier)
        $storeByName = array();
        $rs = $db->query('SELECT ID, TITLE FROM b_catalog_store');
        while ($row = $rs->fetch()) {
            $name = trim((string)$row['TITLE']);
            foreach (array('(', ',') as $cut) {
                $pos = strpos($name, $cut);
                if ($pos !== false) {
                    $name = trim(substr($name, 0, $pos));
                }
            }
            if ($name !== '') {
                $storeByName[$name] = (int)$row['ID'];
            }
        }
    } catch (\Throwable $e) {
        return;   // нет базы — оставляем то, что в кеше
    }

    $replace = array();
    foreach ($all as $m) {
        $productId = (int)$m[2];
        $currentId = (int)$m[3];
        $json = $m[4];
        $badges = $m[6];

        $offers = json_decode(htmlspecialchars_decode($json, ENT_QUOTES), true);
        if (is_array($offers) && $offers) {
            foreach ($offers as $oid => &$stores) {
                if (!is_array($stores)) {
                    continue;
                }
                foreach ($stores as &$store) {
                    $amount = $amounts[(int)$oid][(int)($store['ID'] ?? 0)] ?? 0.0;
                    $store['AMOUNT'] = $amount;
                    $store['REAL_AMOUNT'] = $amount;
                }
                unset($store);
                usort($stores, static function ($a, $b) {
                    return ndStorePos((int)($a['ID'] ?? 0)) <=> ndStorePos((int)($b['ID'] ?? 0));
                });
            }
            unset($stores);
            $json = htmlspecialchars(json_encode($offers), ENT_QUOTES);
        }

        // Чьи плашки
        $badgeId = 0;
        if (!is_array($offers) || !$offers) {
            $badgeId = $productId;
        } elseif ($currentId && isset($offers[$currentId])) {
            $badgeId = $currentId;
        }
        if ($badgeId && preg_match_all('~<span class="store-badge store-badge-\w+">\s*(.*?):\s*(\d*)\s*</span>~s', $badges, $bm, PREG_SET_ORDER)) {
            $list = array();
            foreach ($bm as $b) {
                $name = htmlspecialchars_decode(trim($b[1]), ENT_QUOTES);
                $sid = $storeByName[$name] ?? 0;
                if (!$sid) {
                    $list = array();   // незнакомый склад — плашки не трогаем
                    break;
                }
                $amount = (int)($amounts[$badgeId][$sid] ?? 0);
                $class = $amount >= 30 ? 'green' : ($amount > 0 ? 'orange' : 'gray');
                $list[] = array(ndStorePos($sid), '<span class="store-badge store-badge-' . $class . '">' . $b[1] . ': '
                    . ($amount > 0 ? $amount . ' ' : '') . '</span>');
            }
            if ($list) {
                usort($list, static function ($a, $b) {
                    return $a[0] <=> $b[0];
                });
                $badges = implode('', array_column($list, 1));
            }
        }

        $new = $m[1] . $json . $m[5] . $badges . $m[7];
        if ($new !== $m[0]) {
            $replace[$m[0]] = $new;
        }
    }

    if ($replace) {
        $content = strtr($content, $replace);
    }
}
