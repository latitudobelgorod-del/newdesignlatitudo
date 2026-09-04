<?php
/**
 * Сборка одного товара для API коротких ссылок.
 *
 * Вынесено из index.php, когда к одиночному режиму добавился режим «список
 * товаров раздела» (просьба команды приложения, 4 сентября 2026): оба обязаны
 * отдавать товар ОДНОЙ И ТОЙ ЖЕ структурой, а значит собирать её должен один
 * код, а не две похожие ветки.
 *
 * Точка входа — nd_short_link_product(): принимает строку CIBlockElement с
 * полями ID, ~NAME, ~XML_ID, ~DETAIL_PAGE_URL, ACTIVE, DATE_ACTIVE_FROM,
 * DATE_ACTIVE_TO и возвращает готовый массив ответа.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** Поля элемента, без которых сборка не соберётся. Один список на оба режима. */
function nd_short_link_fields(): array
{
    return ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'XML_ID', 'DETAIL_PAGE_URL', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO'];
}

/**
 * Справочник единиц измерения: id => символ.
 *
 * Читаем один раз за запрос: в списке раздела до полусотни товаров, и тянуть
 * справочник на каждый было бы расточительно.
 */
function nd_short_link_measures(): array
{
    static $measures = null;
    if ($measures !== null) {
        return $measures;
    }

    $measures = [];
    if (!\CModule::IncludeModule('catalog')) {
        return $measures;
    }

    $rs = \CCatalogMeasure::GetList([], []);
    while ($row = $rs->Fetch()) {
        $measures[(int) $row['ID']] = $row['SYMBOL_RUS'] ?: $row['MEASURE_TITLE'];
    }

    return $measures;
}

/** Связка «товар — торговые предложения» инфоблока каталога. Тоже один раз. */
function nd_short_link_sku()
{
    static $sku = null;
    if ($sku === null) {
        $sku = \CModule::IncludeModule('catalog')
            ? \CCatalogSKU::GetInfoByProductIBlock(ND_SHORT_LINK_IBLOCK)
            : false;
    }

    return $sku;
}

/**
 * Одна запись offers: цены по единицам измерения, остаток, длина.
 *
 * Годится и для торгового предложения, и для самого товара каталога. Это
 * важно: у штучных позиций (крепёж, регулируемые опоры) предложений нет вовсе,
 * а цена лежит прямо на элементе — и приложение всё равно должно получить её
 * в том же виде, в prices[], а не отдельным полем.
 *
 * Как это устроено на сайте:
 * - базовая единица — поле MEASURE карточки каталога (b_catalog_product),
 *   символ берём из справочника CCatalogMeasure;
 * - дополнительные единицы задаёт множественное свойство UNIT_KOEF модуля
 *   maxyss.measureunits: ЗНАЧЕНИЕ — коэффициент, ОПИСАНИЕ — id единицы из того
 *   же справочника. Так его читает и сам модуль (см. component.php: он получает
 *   свойство параметром MEASURE_RESULT и раскладывает VALUE/DESCRIPTION).
 *
 * @param int   $iblockId инфоблок элемента: предложений (20) или товаров (19)
 * @param array $element  строка CIBlockElement::GetList с ID, ~NAME, ~XML_ID
 * @param array $measures справочник единиц: id => символ
 * @return array<string,mixed>
 */
function nd_offer_view(int $iblockId, array $element, array $measures): array
{
    $elementId = (int) $element['ID'];

    // Базовая единица и остаток — карточка каталога.
    $product = \CCatalogProduct::GetByID($elementId) ?: [];

    /* Единицы читаем поэлементно. Пакетный GetPropertyValues тут не годится:
       даже в расширенном режиме он отдаёт по UNIT_KOEF пусто, а нам нужны ОБА
       поля — значение (коэффициент) и описание (id единицы). Проверено;
       предложений у товара единицы, так что цикл дешёвый.

       Сигнатура: GetProperty($iblock, $element, $by, $order, $filter) — фильтр
       ПЯТЫЙ аргумент. Четвёртым он молча игнорируется, и метод отдаёт первое
       попавшееся свойство элемента. */
    $units = [];
    $propRes = \CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => 'UNIT_KOEF']);
    while ($pv = $propRes->Fetch()) {
        $ratio = (float) str_replace(',', '.', (string) $pv['VALUE']);
        $measureId = (int) $pv['DESCRIPTION'];
        if ($ratio > 0 && $measureId > 0) {
            $units[] = ['ratio' => $ratio, 'measure_id' => $measureId];
        }
    }

    /* Длина. Свойство списочное, поэтому берём не VALUE (там id значения), а
       VALUE_ENUM — уже «3000». Приложению этот размер нужен, чтобы не выкусывать
       его из названия товара. У штучных позиций свойства нет — останется null. */
    $length = null;
    $lenRes = \CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => 'DLINA']);
    while ($lv = $lenRes->Fetch()) {
        $len = trim((string) ($lv['VALUE_ENUM'] !== null && $lv['VALUE_ENUM'] !== '' ? $lv['VALUE_ENUM'] : $lv['VALUE']));
        if ($len !== '') {
            $length = is_numeric($len) ? (float) $len : $len;
            break;
        }
    }

    /* Цену берём как гость (группа 2) и с учётом скидок — GetOptimalPrice
       отдаёт ровно то число, что видит посетитель. Отдаём ОБЕ цены: базовую и
       со скидкой, иначе приложение не может решить, что печатать, когда акция
       кончится (замечание команды приложения, 3 сентября 2026). Сегодня они
       часто равны — это значит, что скидки на товар просто нет. */
    $optimal = \CCatalogProduct::GetOptimalPrice($elementId, 1, [2], 'N');
    $value = $optimal ? (float) $optimal['RESULT_PRICE']['DISCOUNT_PRICE'] : 0.0;
    $base = $optimal ? (float) $optimal['RESULT_PRICE']['BASE_PRICE'] : 0.0;
    $currency = $optimal ? (string) $optimal['RESULT_PRICE']['CURRENCY'] : '';

    $baseMeasureId = (int) ($product['MEASURE'] ?? 0);

    $prices = [];
    if ($value > 0) {
        // Первой идёт базовая единица — та, в которой товар кладут в корзину.
        $prices[] = [
            'unit' => $measures[$baseMeasureId] ?? '',
            'measure_id' => $baseMeasureId ?: null,
            'ratio' => 1,
            'value' => round($value, 2),
            'base' => round($base, 2),
        ];
        /* Пересчёт: цена за единицу = базовая цена × коэффициент. Коэффициент —
           это «сколько базовых единиц в одной такой»: у доски 3 м коэффициент
           п.м равен 1/3, а м² — 2,33 (в квадратном метре 2,33 доски). Сверено
           с витриной: 1575 ₽/шт × 2,38 = 3750 ₽/м², ровно как на странице. */
        foreach ($units as $u) {
            $prices[] = [
                'unit' => $measures[$u['measure_id']] ?? '',
                'measure_id' => $u['measure_id'],
                'ratio' => $u['ratio'],
                'value' => round($value * $u['ratio'], 2),
                'base' => round($base * $u['ratio'], 2),
            ];
        }
    }

    return [
        'id' => $elementId,
        'name' => $element['~NAME'],
        'xml_id' => $element['~XML_ID'],
        'length_mm' => $length,
        'quantity' => $product ? (float) $product['QUANTITY'] : null,
        'available' => $product && (float) $product['QUANTITY'] > 0,
        'currency' => $currency,
        'has_discount' => $base > 0 && $value > 0 && $base > $value,
        'prices' => $prices,
    ];
}

/**
 * Торговые предложения товара, а если их нет — сам товар одной записью.
 *
 * Штучные позиции — саморезы, комплекты крепежа, регулируемые опоры — торговых
 * предложений не имеют: цена, остаток и единицы измерения лежат на самом
 * элементе каталога. Раньше у таких товаров offers приходил пустым, и цену
 * приложению было взять неоткуда, хотя на странице сайта она есть (замечание
 * команды приложения, 4 сентября 2026).
 *
 * Оговорка «нечего показать» оставлена намеренно: если у элемента нет ни
 * карточки каталога, ни цены, пустой массив честнее выдуманной записи.
 *
 * @return array<int,array<string,mixed>>
 */
function nd_short_link_offers(array $row): array
{
    $offers = [];
    $measures = nd_short_link_measures();
    $skuInfo = nd_short_link_sku();

    if ($skuInfo) {
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'PROPERTY_' . $skuInfo['SKU_PROPERTY_ID'] => (int) $row['ID'],
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'XML_ID']
        );
        while ($o = $res->GetNext()) {
            $offers[] = nd_offer_view((int) $skuInfo['IBLOCK_ID'], $o, $measures);
        }
    }

    if (!$offers && \CModule::IncludeModule('catalog')) {
        $self = nd_offer_view(ND_SHORT_LINK_IBLOCK, $row, $measures);
        if ($self['prices'] || $self['quantity'] !== null) {
            $offers[] = $self;
        }
    }

    return $offers;
}

/**
 * Характеристики, размеры и производитель.
 *
 * Отдаём ВСЕ заполненные пользовательские свойства, а не список кодов из
 * шаблона: там он свой для каждого раздела (в карточке доски это условие по
 * SECTION_ID 98/510), и повторять его здесь значило бы ломаться на любом другом
 * разделе. Пусть приложение берёт нужные по коду.
 *
 * Служебное отсекаем: файлы, привязки к элементам и разделам, обменные поля 1С
 * и наши собственные (короткая ссылка, коэффициенты единиц — они уже отданы
 * отдельно, в prices).
 *
 * Читаем одним запросом без фильтра: фильтр GetProperty по CODE принимает
 * только одну строку, массив кодов роняет запрос («real_escape_string():
 * Argument #1 must be of type string, array given»).
 *
 * @return array{sizes: array<string,mixed>, chars: array<int,array<string,mixed>>, brand: array<string,mixed>|null}
 */
function nd_short_link_props(array $row): array
{
    $skipCodes = [
        ND_SHORT_LINK_PROP => true,   // короткая ссылка — она уже в поле short
        'UNIT_KOEF' => true,          // коэффициенты единиц — уже посчитаны в prices
        'BASE_KOEF' => true,
        'MINIMUM_PRICE' => true,      // цены отдаём предложениями, а не строкой
        'MAXIMUM_PRICE' => true,
        'LIST_PRISE' => true,         // «назначение фида» — внутренняя разметка выгрузок
        'COLOR_MAIN_EL' => true,      // технический код цвета для подбора
        'ND_BRAND_WEIGHT' => true,    // наш вес марки для сортировки выдачи
    ];

    $chars = [];
    $brandId = 0;
    $sizes = [
        'width_mm' => null,
        'thickness_mm' => null,
        'lengths_mm' => [],
    ];

    $propRes = \CIBlockElement::GetProperty(ND_SHORT_LINK_IBLOCK, (int) $row['ID'], 'sort', 'asc', ['ACTIVE' => 'Y']);

    while ($sp = $propRes->Fetch()) {
        $code = (string) $sp['CODE'];

        // Служебное и обменное наружу не отдаём.
        if (isset($skipCodes[$code]) || strncmp($code, 'CML2_', 5) === 0) {
            continue;
        }
        /* EDITOR1/EDITOR2/… — содержимое редактора блоков sprint.editor: там лежит
           json со всей вёрсткой описания, характеристикой это не является и весит
           десятки килобайт (Ирина, 3 сентября 2026). */
        if (preg_match('/^EDITOR\d*$/', $code)) {
            continue;
        }

        /* Производитель — привязка к элементу инфоблока брендов, поэтому в общий
           разбор ниже он не попадает (там отсеиваются все типы F/E/G). Забираем
           ID тут же, в этом цикле: отдельный запрос за свойством не нужен, а сам
           бренд дочитаем одним GetList после цикла (просьба команды приложения,
           4 сентября 2026 — производитель нужен в характеристиках). */
        if ($code === 'BRAND') {
            $brandId = (int) $sp['VALUE'];
            continue;
        }

        // Остальные файлы и привязки — не характеристики.
        if (in_array($sp['PROPERTY_TYPE'], ['F', 'E', 'G'], true)) {
            continue;
        }

        // У списочных свойств VALUE — это id значения, подпись лежит в VALUE_ENUM.
        $raw = $sp['VALUE_ENUM'] !== null && $sp['VALUE_ENUM'] !== '' ? $sp['VALUE_ENUM'] : $sp['VALUE'];
        if (is_array($raw)) {
            continue;
        }
        $raw = trim((string) $raw);
        if ($raw === '') {
            continue;
        }
        /* Страховка на случай, если вёрстка редактора приедет под другим кодом:
           характеристика — это короткое значение, а не json на килобайты. */
        if (strlen($raw) > 300 || strncmp($raw, '{"version"', 10) === 0) {
            continue;
        }

        $value = is_numeric($raw) ? (float) $raw : $raw;

        // Множественные свойства приходят несколькими строками — копим массивом.
        if (isset($chars[$code])) {
            $chars[$code]['value'] = array_merge((array) $chars[$code]['value'], [$value]);
        } else {
            $chars[$code] = [
                'code' => $code,
                'name' => (string) $sp['NAME'],
                'value' => $sp['MULTIPLE'] === 'Y' ? [$value] : $value,
            ];
        }

        // Размеры дублируем отдельно — они нужны почти всегда, а искать их по коду
        // в общем списке приложению неудобно.
        if ($code === 'IT_8') {
            $sizes['width_mm'] = $value;
        } elseif ($code === 'IT_6') {
            $sizes['thickness_mm'] = $value;
        } elseif ($code === 'DLINA_DOSKA_DPK') {
            $sizes['lengths_mm'][] = $value;
        }
    }

    sort($sizes['lengths_mm']);

    /**
     * Производитель. Отдаём его двумя способами сразу:
     * - отдельным полем brand — там есть ID, символьный код и адрес страницы
     *   бренда, приложению удобнее сопоставлять по коду, а не по названию;
     * - строкой в chars — чтобы производитель просто попал в общий список
     *   характеристик и его не пришлось выводить особым случаем.
     *
     * Название берём из инфоблока брендов (у свойства BRAND это привязка к
     * элементу), а не из значения свойства: в значении лежит ID.
     */
    $brand = null;
    if ($brandId > 0) {
        $brandRow = \CIBlockElement::GetList(
            [],
            ['ID' => $brandId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL']
        )->GetNext();

        if ($brandRow) {
            $brand = [
                'id'   => (int) $brandRow['ID'],
                'name' => $brandRow['~NAME'],
                'code' => $brandRow['~CODE'],
                'url'  => $brandRow['~DETAIL_PAGE_URL'] ? ND_SHORT_LINK_HOST . $brandRow['~DETAIL_PAGE_URL'] : '',
            ];

            $chars['BRAND'] = [
                'code'  => 'BRAND',
                'name'  => 'Производитель / Бренд',
                'value' => $brandRow['~NAME'],
            ];
        }
    }

    return [
        'sizes' => $sizes,
        'chars' => array_values($chars),
        'brand' => $brand,
    ];
}

/**
 * Активность товара.
 *
 * Возвращаем то же, что решает сам Битрикс при показе: галка «Активность» и
 * срок «Начало / Окончание активности». Оба поля отдаём и по отдельности —
 * приложению бывает нужно показать «в продаже с такого-то», а не только
 * итоговое да/нет. Даты переводим в ISO 8601 с зоной сайта: у Битрикса они
 * приходят строкой в формате сайта («04.09.2026 10:00:00»), разбирать её на
 * стороне приложения неудобно.
 *
 * @return array{active: bool, active_from: string|null, active_to: string|null}
 */
function nd_short_link_activity(array $row): array
{
    $iso = static function ($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $stamp = MakeTimeStamp($value);

        return $stamp ? date('c', $stamp) : null;
    };

    $from = $iso($row['DATE_ACTIVE_FROM']);
    $to   = $iso($row['DATE_ACTIVE_TO']);
    $now  = time();

    return [
        'active' => ($row['ACTIVE'] === 'Y')
            && (!$from || strtotime($from) <= $now)
            && (!$to || strtotime($to) >= $now),
        'active_from' => $from,
        'active_to' => $to,
    ];
}

/**
 * Короткая ссылка товара: заводим при первом обращении, чиним при переезде.
 *
 * Достаём код из уже сохранённого значения свойства: в нём лежит полная
 * ссылка, а для сверки с b_short_uri нужен только хвост после последнего «/».
 *
 * Тильда — ЧАСТЬ кода, а не разделитель: GenerateShortUri() собирает его как
 * "~" . randString(5), в колонке SHORT_URI лежит «~JH8uT», а GetShortUri()
 * возвращает уже готовый путь «/~JH8uT». Своей тильды дописывать не надо —
 * иначе выходит «/~~JH8uT» (поймано на первом же прогоне).
 *
 * @return string полный адрес вида https://latitudo.ru/~JH8uT или '' при сбое
 */
function nd_short_link_url(array $row, string $uri): string
{
    /* Сигнатура именно такая: GetProperty($iblock, $element, $by, $order, $filter).
       Третий и четвёртый аргументы — сортировка, фильтр только пятый. Если сунуть
       фильтр четвёртым, он молча игнорируется и метод отдаёт ПЕРВОЕ попавшееся
       свойство элемента — самолечение при этом не срабатывает, а ошибки не видно:
       ссылка каждый раз выглядит правильной, потому что GetShortUri() и сам
       идемпотентен по адресу (поймано на переезде товара). */
    $saved = \CIBlockElement::GetProperty(
        ND_SHORT_LINK_IBLOCK,
        (int) $row['ID'],
        'sort',
        'asc',
        ['CODE' => ND_SHORT_LINK_PROP]
    )->Fetch();

    $shortCode = '';
    /* Разделитель регулярки — «#», а не «~»: тильда есть внутри шаблона, и с ней
       в роли разделителя выражение просто не компилируется. */
    if ($saved && preg_match('#/(~[0-9a-zA-Z]+)$#', (string) $saved['VALUE'], $m)) {
        $shortCode = $m[1];
    }

    if ($shortCode !== '') {
        // Ссылка уже выдавалась. Проверяем, туда ли она ведёт сейчас.
        $exists = \CBXShortUri::GetList([], ['SHORT_URI' => $shortCode])->Fetch();
        if (!$exists) {
            $shortCode = '';                       // строку удалили — заведём заново
        } elseif ($exists['URI'] !== $uri) {
            \CBXShortUri::Update($exists['ID'], ['URI' => $uri, 'STATUS' => 301]);
        }
    }

    if ($shortCode === '') {
        $shortCode = ltrim((string) \CBXShortUri::GetShortUri($uri), '/');
        if ($shortCode === '') {
            return '';
        }
    }

    $short = ND_SHORT_LINK_HOST . '/' . $shortCode;

    // Витрина в админке. Пишем только при изменении: SetPropertyValuesEx поднимает
    // события инфоблока, а лишние сохранения сбрасывают кеш каталога.
    if (!$saved || (string) $saved['VALUE'] !== $short) {
        \CIBlockElement::SetPropertyValuesEx(
            (int) $row['ID'],
            ND_SHORT_LINK_IBLOCK,
            [ND_SHORT_LINK_PROP => $short]
        );
    }

    return $short;
}

/**
 * Готовый товар для ответа — одинаковый в обоих режимах ручки.
 *
 * @param array $row строка CIBlockElement::GetList (обязательно через GetNext:
 *                   нужны неэкранированные ~NAME, ~XML_ID, ~DETAIL_PAGE_URL)
 * @return array<string,mixed>
 */
function nd_short_link_product(array $row): array
{
    $uri = (string) $row['~DETAIL_PAGE_URL'];
    $props = nd_short_link_props($row);

    return [
        'id'     => (int) $row['ID'],
        'name'   => $row['~NAME'],
        'xml_id' => $row['~XML_ID'],
    ] + nd_short_link_activity($row) + [
        'url'    => $uri !== '' ? ND_SHORT_LINK_HOST . $uri : '',
        'short'  => $uri !== '' ? nd_short_link_url($row, $uri) : '',
        'sizes'  => $props['sizes'],
        'brand'  => $props['brand'],
        'chars'  => $props['chars'],
        'offers' => nd_short_link_offers($row),
    ];
}
