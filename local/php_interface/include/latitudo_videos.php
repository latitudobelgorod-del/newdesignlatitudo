<?php
/**
 * Видео о продукции (ТЗ Ирины, 28.10.2026).
 *
 * Ролик — одна запись инфоблока «Видео» (код videos, тип aspro_next_content),
 * без дублей: ищем по нормализованной ссылке URL_NORM. Точки ввода для
 * контент-менеджера остаются прежними — «Видео в попапе» товара (POPUP_VIDEO,
 * ИБ 19) и «Товарное видео» предложения (PRODUCT_VIDEO, ИБ 20). Обработчики
 * сохранения товара/предложения заводят ролик и привязывают к нему товар;
 * разовый перенос — local/tools/nd_videos_migrate.php, установка инфоблока —
 * local/tools/nd_videos_install.php.
 *
 * Классы:
 *   LatitudoVideoUrl        — разбор и нормализация ссылки (Rutube, YouTube, VK);
 *   LatitudoVideoHosting    — название, длительность, превью, дата с хостинга;
 *   LatitudoVideoSync       — товар → ролики (обработчики и перенос);
 *   LatitudoVideoRepository — выборка для вывода: блок на странице марки
 *                             (компонент latitudo:video.rail) и будущая
 *                             страница «Видео».
 */

class LatitudoVideoUrl
{
    /**
     * @return array{HOST:string, ID:string, NORM:string, EMBED:string}|null
     */
    public static function parse(string $url): ?array
    {
        $url = trim($url);
        if ($url === '' || !preg_match('~^(?:https?:)?//|^(?:www\.)?[a-z0-9-]+\.[a-z]{2,}/~i', $url)) {
            return null;
        }

        if (preg_match('~rutube\.ru/(?:video|play/embed|shorts)/(?:private/)?([0-9a-f]{32})~i', $url, $m)) {
            $id = strtolower($m[1]);
            return ['HOST' => 'rutube', 'ID' => $id, 'NORM' => 'rutube.ru/video/' . $id,
                'EMBED' => 'https://rutube.ru/play/embed/' . $id];
        }
        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([\w-]{11})~i', $url, $m)) {
            return ['HOST' => 'youtube', 'ID' => $m[1], 'NORM' => 'youtube.com/watch?v=' . $m[1],
                'EMBED' => 'https://www.youtube.com/embed/' . $m[1]];
        }
        if (preg_match('~(?:vk\.com|vkvideo\.ru)/(?:.*[?&]z=)?video(-?\d+)_(\d+)~i', $url, $m)
            || preg_match('~vk\.com/video_ext\.php\?(?=.*\boid=(-?\d+))(?=.*\bid=(\d+))~i', $url, $m)) {
            return ['HOST' => 'vk', 'ID' => $m[1] . '_' . $m[2], 'NORM' => 'vk.com/video' . $m[1] . '_' . $m[2],
                'EMBED' => 'https://vk.com/video_ext.php?oid=' . $m[1] . '&id=' . $m[2] . '&hd=2'];
        }

        // Прочее (свой файл, другой хостинг): хост без www и путь, без параметров и якоря
        $parts = parse_url(strpos($url, '//') === 0 ? 'https:' . $url : (preg_match('~^https?://~i', $url) ? $url : 'https://' . $url));
        if (empty($parts['host'])) {
            return null;
        }
        $host = preg_replace('~^www\.~i', '', strtolower($parts['host']));
        $path = rtrim((string)($parts['path'] ?? ''), '/');
        return ['HOST' => 'other', 'ID' => $host . $path, 'NORM' => $host . $path, 'EMBED' => $url];
    }

    /** Адрес встраиваемого плеера с автозапуском — его ставит модальное окно. */
    public static function embedAutoplay(array $parsed): string
    {
        $sep = strpos($parsed['EMBED'], '?') === false ? '?' : '&';
        switch ($parsed['HOST']) {
            case 'rutube':
            case 'youtube':
                return $parsed['EMBED'] . $sep . 'autoplay=1';
            case 'vk':
                return $parsed['EMBED'] . $sep . 'autoplay=1';
        }
        return $parsed['EMBED'];
    }
}

class LatitudoVideoHosting
{
    /**
     * Данные ролика с хостинга. Ошибка сети или ответа — пустой массив и запись
     * в журнал: сохранение товара из-за хостинга не должно падать.
     *
     * GONE — хостинг ответил, что ролика нет (удалён или закрыт): такой ролик
     * заводим выключенным, иначе в окне будет пустой плеер.
     *
     * @return array{TITLE?:string, DURATION?:int, THUMB?:string, DATE?:string, GONE?:bool}
     */
    public static function meta(array $parsed): array
    {
        try {
            switch ($parsed['HOST']) {
                case 'rutube':
                    $d = self::getJson('https://rutube.ru/api/video/' . $parsed['ID'] . '/?format=json', $status);
                    if (!$d || $status !== 200) {
                        return in_array($status, [403, 404, 410], true) ? ['GONE' => true] : [];
                    }
                    return [
                        'TITLE' => trim((string)($d['title'] ?? '')),
                        'DURATION' => (int)($d['duration'] ?? 0),
                        'THUMB' => (string)($d['thumbnail_url'] ?? ''),
                        'DATE' => self::isoDate((string)($d['publication_ts'] ?? $d['created_ts'] ?? '')),
                    ];
                case 'youtube':
                    $d = self::getJson('https://www.youtube.com/oembed?format=json&url='
                        . rawurlencode('https://www.youtube.com/watch?v=' . $parsed['ID']));
                    // maxresdefault есть не у всех роликов — тогда превью из oEmbed (hqdefault)
                    $thumb = 'https://img.youtube.com/vi/' . $parsed['ID'] . '/maxresdefault.jpg';
                    if (!self::exists($thumb)) {
                        $thumb = (string)($d['thumbnail_url'] ?? 'https://img.youtube.com/vi/' . $parsed['ID'] . '/hqdefault.jpg');
                    }
                    return ['TITLE' => trim((string)($d['title'] ?? '')), 'THUMB' => $thumb];
                case 'vk':
                    // открытого API без ключа у VK нет — название и превью заполняет контент-менеджер
                    return [];
            }
        } catch (\Throwable $e) {
            self::log('meta ' . $parsed['NORM'] . ': ' . $e->getMessage());
        }
        return [];
    }

    public static function log(string $message): void
    {
        if (function_exists('AddMessage2Log')) {
            AddMessage2Log($message, 'latitudo.videos');
        }
    }

    private static function getJson(string $url, ?int &$status = null): ?array
    {
        $http = new \Bitrix\Main\Web\HttpClient(['socketTimeout' => 5, 'streamTimeout' => 8, 'redirect' => true]);
        $body = $http->get($url);
        $status = (int)$http->getStatus();
        if ($status !== 200 || !$body) {
            self::log('HTTP ' . $http->getStatus() . ' ' . $url);
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private static function exists(string $url): bool
    {
        $http = new \Bitrix\Main\Web\HttpClient(['socketTimeout' => 5, 'streamTimeout' => 5]);
        $http->head($url);
        return $http->getStatus() === 200;
    }

    private static function isoDate(string $s): string
    {
        $t = $s !== '' ? strtotime($s) : false;
        return $t ? date('c', $t) : '';
    }
}

class LatitudoVideoSync
{
    public const IBLOCK_TYPE = 'aspro_next_content';
    public const IBLOCK_CODE = 'videos';
    public const CATALOG_IBLOCK = 19;
    public const OFFERS_IBLOCK = 20;
    public const PRODUCT_PROP = 'POPUP_VIDEO';
    public const OFFER_PROP = 'PRODUCT_VIDEO';

    /** Новый ролик из обработчика заводим выключенным: контент-менеджер проверит название и превью. */
    public const ACTIVATE_NEW = false;

    private static bool $busy = false;

    public static function iblockId(): int
    {
        static $id = null;
        if ($id === null) {
            $id = 0;
            if (\Bitrix\Main\Loader::includeModule('iblock')) {
                $ib = CIBlock::GetList([], ['TYPE' => self::IBLOCK_TYPE, 'CODE' => self::IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
                $id = $ib ? (int)$ib['ID'] : 0;
            }
        }
        return $id;
    }

    /** OnAfterIBlockElementAdd / OnAfterIBlockElementUpdate */
    public static function onElementSave(&$fields): void
    {
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        if (self::$busy || empty($fields['RESULT']) || !in_array($iblockId, [self::CATALOG_IBLOCK, self::OFFERS_IBLOCK], true)) {
            return;
        }
        // Правка без свойств (активность, цена из 1С) ролики не меняет
        if (!array_key_exists('PROPERTY_VALUES', $fields)) {
            return;
        }
        self::syncByElement((int)$fields['ID'], $iblockId);
    }

    /** OnAfterIBlockElementSetPropertyValuesEx — скрипты пишут свойства мимо Update */
    public static function onSetPropertyValuesEx($elementId, $iblockId, $values, $flags): void
    {
        $iblockId = (int)$iblockId;
        if (self::$busy || !in_array($iblockId, [self::CATALOG_IBLOCK, self::OFFERS_IBLOCK], true) || !is_array($values)) {
            return;
        }
        $code = $iblockId === self::CATALOG_IBLOCK ? self::PRODUCT_PROP : self::OFFER_PROP;
        $propId = (int)(CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code])->Fetch()['ID'] ?? 0);
        if (array_key_exists($code, $values) || ($propId && array_key_exists($propId, $values))) {
            self::syncByElement((int)$elementId, $iblockId);
        }
    }

    /** OnAfterIBlockElementDelete: удалённый товар снимаем с роликов */
    public static function onElementDelete($fields): void
    {
        if (!self::$busy && (int)($fields['IBLOCK_ID'] ?? 0) === self::CATALOG_IBLOCK && (int)($fields['ID'] ?? 0) > 0) {
            self::syncProduct((int)$fields['ID']);
        }
    }

    private static function syncByElement(int $elementId, int $iblockId): void
    {
        $productId = $elementId;
        if ($iblockId === self::OFFERS_IBLOCK) {
            $row = CIBlockElement::GetList([], ['IBLOCK_ID' => self::OFFERS_IBLOCK, 'ID' => $elementId], false, false, ['ID', 'PROPERTY_CML2_LINK'])->Fetch();
            $productId = (int)($row['PROPERTY_CML2_LINK_VALUE'] ?? 0);
        }
        if ($productId > 0) {
            self::syncProduct($productId);
        }
    }

    /**
     * Привести ролики в соответствие ссылкам товара и его предложений.
     *
     * @param array $stat счётчики для переноса: created, linked, unlinked, deactivated, errors
     * @return string[] журнал действий
     */
    public static function syncProduct(int $productId, bool $dry = false, array &$stat = [], ?bool $activateNew = null): array
    {
        $log = [];
        $videosIb = self::iblockId();
        if (!$videosIb || $productId <= 0) {
            return $log;
        }
        $activateNew = $activateNew ?? self::ACTIVATE_NEW;
        self::$busy = true;
        try {
            $product = CIBlockElement::GetList([], ['IBLOCK_ID' => self::CATALOG_IBLOCK, 'ID' => $productId, 'CHECK_PERMISSIONS' => 'N'], false, false,
                ['ID', 'NAME', 'PROPERTY_' . self::PRODUCT_PROP, 'PROPERTY_BRAND'])->Fetch();

            // ссылки товара и его предложений: нормализованная → разобранная
            $wanted = [];
            if ($product) {
                $urls = [(string)$product['PROPERTY_' . self::PRODUCT_PROP . '_VALUE']];
                $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => self::OFFERS_IBLOCK, 'PROPERTY_CML2_LINK' => $productId,
                    '!PROPERTY_' . self::OFFER_PROP => false, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'PROPERTY_' . self::OFFER_PROP]);
                while ($o = $rs->Fetch()) {
                    $urls[] = (string)$o['PROPERTY_' . self::OFFER_PROP . '_VALUE'];
                }
                foreach ($urls as $url) {
                    $p = LatitudoVideoUrl::parse($url);
                    if ($p && !isset($wanted[$p['NORM']])) {
                        $wanted[$p['NORM']] = $p + ['URL' => trim($url)];
                    }
                }
            }
            $brandId = (int)($product['PROPERTY_BRAND_VALUE'] ?? 0);

            // ролики, где товар уже стоит
            $current = [];
            $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $videosIb, 'PROPERTY_PRODUCTS' => $productId, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
            while ($v = $rs->Fetch()) {
                $current[(int)$v['ID']] = true;
            }
            $currentProps = self::props(array_keys($current));

            $changed = false;
            foreach ($wanted as $norm => $p) {
                $video = CIBlockElement::GetList([], ['IBLOCK_ID' => $videosIb, '=PROPERTY_URL_NORM' => $norm, 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 1], ['ID', 'NAME', 'ACTIVE'])->Fetch();
                if (!$video) {
                    $stat['created'] = ($stat['created'] ?? 0) + 1;
                    $gone = false;
                    if (!$dry) {
                        self::create($videosIb, $p, (array)$product, $brandId, $activateNew, $gone);
                        $changed = true;
                    }
                    $log[] = "новый ролик {$norm} ← товар {$productId}" . ($gone ? ' — на хостинге ролика НЕТ (удалён), заведён выключенным' : '');
                    if ($gone) {
                        $stat['gone'] = ($stat['gone'] ?? 0) + 1;
                    }
                    continue;
                }
                $vid = (int)$video['ID'];
                $props = self::props([$vid])[$vid];
                $set = [];
                if (!in_array($productId, $props['PRODUCTS'], true)) {
                    $set['PRODUCTS'] = array_merge($props['PRODUCTS'], [$productId]);
                    $log[] = "ролик {$vid} ← товар {$productId}" . ($video['ACTIVE'] !== 'Y' ? ' (ролик выключен)' : '');
                    $stat['linked'] = ($stat['linked'] ?? 0) + 1;
                }
                if (!$props['BRAND'] && $brandId) {
                    $set['BRAND'] = $brandId;
                }
                if ($set && !$dry) {
                    CIBlockElement::SetPropertyValuesEx($vid, $videosIb, $set);
                    $changed = true;
                }
            }

            // сменили или стёрли ссылку — товар снимаем; ролик без товаров выключаем, не удаляем
            foreach ($currentProps as $vid => $props) {
                if (isset($wanted[$props['URL_NORM']])) {
                    continue;
                }
                $left = array_values(array_diff($props['PRODUCTS'], [$productId]));
                $log[] = "ролик {$vid} без товара {$productId}" . ($left ? '' : ' — товаров не осталось, выключаю');
                $stat['unlinked'] = ($stat['unlinked'] ?? 0) + 1;
                if (!$left) {
                    $stat['deactivated'] = ($stat['deactivated'] ?? 0) + 1;
                }
                if (!$dry) {
                    CIBlockElement::SetPropertyValuesEx($vid, $videosIb, ['PRODUCTS' => $left ?: false]);
                    if (!$left) {
                        (new CIBlockElement())->Update($vid, ['ACTIVE' => 'N']);
                    }
                    $changed = true;
                }
            }

            if ($changed) {
                CIBlock::clearIblockTagCache($videosIb);
            }
        } catch (\Throwable $e) {
            $stat['errors'] = ($stat['errors'] ?? 0) + 1;
            $log[] = 'ОШИБКА товар ' . $productId . ': ' . $e->getMessage();
            LatitudoVideoHosting::log('sync ' . $productId . ': ' . $e->getMessage());
        } finally {
            self::$busy = false;
        }
        return $log;
    }

    /** @return array<int, array{URL_NORM:string, PRODUCTS:int[], BRAND:int}> */
    private static function props(array $ids): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        $values = [];
        CIBlockElement::GetPropertyValuesArray($values, self::iblockId(), ['ID' => $ids], ['CODE' => ['URL_NORM', 'PRODUCTS', 'BRAND']]);
        foreach ($ids as $id) {
            $v = $values[$id] ?? [];
            $out[$id] = [
                'URL_NORM' => (string)($v['URL_NORM']['VALUE'] ?? ''),
                'PRODUCTS' => array_values(array_map('intval', array_filter((array)($v['PRODUCTS']['VALUE'] ?? [])))),
                'BRAND' => (int)($v['BRAND']['VALUE'] ?? 0),
            ];
        }
        return $out;
    }

    private static function create(int $videosIb, array $p, array $product, int $brandId, bool $active, bool &$gone = false): int
    {
        $meta = LatitudoVideoHosting::meta($p);
        $gone = !empty($meta['GONE']);
        if ($gone) {
            $active = false;
        }
        // название с хостинга; нет — название товара без хвоста с цветом/размером после последней цифры
        $name = trim((string)($meta['TITLE'] ?? ''));
        if ($name === '') {
            $name = trim((string)($product['NAME'] ?? ''));
            $name = preg_replace('~^(.*\d[\dхxX×,.]*(?:\s*мм)?)\s+[А-ЯЁA-Z][^\d]*$~u', '$1', $name) ?: $name;
        }
        if ($name === '') {
            $name = $p['NORM'];
        }

        $fields = [
            'IBLOCK_ID' => $videosIb,
            'NAME' => $name,
            'CODE' => self::uniqueCode($videosIb, $name),
            'ACTIVE' => $active ? 'Y' : 'N',
            'SORT' => 500,
            'PROPERTY_VALUES' => [
                'URL' => $p['URL'],
                'URL_NORM' => $p['NORM'],
                'DURATION' => !empty($meta['DURATION']) ? LatitudoVideoRepository::formatDuration((int)$meta['DURATION']) : '',
                'UPLOAD_DATE' => (string)($meta['DATE'] ?? ''),
                'PRODUCTS' => !empty($product['ID']) ? [(int)$product['ID']] : false,
                'BRAND' => $brandId ?: false,
            ],
        ];
        if (!empty($meta['THUMB'])) {
            $file = CFile::MakeFileArray($meta['THUMB']);
            if (is_array($file) && !empty($file['tmp_name'])) {
                $file['name'] = 'video-' . preg_replace('~[^\w-]+~', '-', $p['ID']) . '.jpg';
                $file['MODULE_ID'] = 'iblock';
                $fields['PREVIEW_PICTURE'] = $file;
            }
        }
        $el = new CIBlockElement();
        $id = (int)$el->Add($fields);
        if (!$id) {
            throw new \RuntimeException('не создан ролик ' . $p['NORM'] . ': ' . $el->LAST_ERROR);
        }
        return $id;
    }

    public static function uniqueCode(int $iblockId, string $name): string
    {
        $base = CUtil::translit($name, 'ru', ['max_len' => 90, 'change_case' => 'L', 'replace_space' => '-', 'replace_other' => '-', 'delete_repeat_replace' => true]);
        $base = trim($base, '-') ?: 'video';
        $code = $base;
        for ($i = 2; CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'], []) > 0; $i++) {
            $code = $base . '-' . $i;
        }
        return $code;
    }
}

class LatitudoVideoRepository
{
    /** Короткие подписи разделов верхнего уровня для фильтра; нет в списке — название раздела как есть */
    public const CATEGORY_NAMES = [
        'Террасная доска (декинг) из ДПК' => 'Террасная доска',
        'Фасады из ДПК' => 'Фасады',
        'Заборная доска из ДПК' => 'Заборы',
        'Ограждения из ДПК' => 'Ограждения',
        'Ступени из ДПК' => 'Ступени',
        'Комплектующие для террасной доски ДПК' => 'Комплектующие',
    ];

    /** Серия по названию, если в ролике не задана вручную (SERIES) */
    public const SERIES_RULES = [
        '~вуд[-\s]?икс|wood[-\s]?x~iu' => 'Вуд-Икс',
        '~ко[-\s]?экс~iu' => 'Ко-Экструзия',
    ];

    /** Тип ролика (свойство TYPE, XML_ID значения списка) => подпись фильтра на /video/.
        Пусто — обзор продукции: так заведены все ролики из товаров (Ирина, 28.10.2026). */
    public const TYPES = [
        'product' => 'Видеообзоры продукции',
        'review' => 'Видеоотзывы клиентов',
        'expert' => 'Эксперт объясняет',
        'objects' => 'Наши объекты',
    ];

    /** Портфолио — ролики из карточек проектов ссылаются на них свойством PROJECT */
    public const PORTFOLIO_IBLOCK = 18;

    /** Группа покупателей для цены — розница, как в фидах и сверке цен */
    private const PRICE_GROUPS = [2];

    /**
     * Ролики для вывода.
     *
     * @param array{BRAND?:int, CATEGORY?:string, TYPE?:string, ONLY_AVAILABLE?:bool, LIMIT?:int} $filter
     *   BRAND — ID производителя (ИБ 12), без него — все марки; CATEGORY — короткая
     *   подпись категории; TYPE — ключ из TYPES; ONLY_AVAILABLE (по умолчанию да) — только
     *   ролики, у которых есть товар в продаже, или ролики без товаров с вручную заданной
     *   маркой или с проектом портфолио.
     * @return array<int, array> список роликов (простые массивы — годятся в кеш компонента)
     */
    public static function getList(array $filter = []): array
    {
        $videosIb = LatitudoVideoSync::iblockId();
        if (!$videosIb || !\Bitrix\Main\Loader::includeModule('catalog')) {
            return [];
        }
        $onlyAvailable = $filter['ONLY_AVAILABLE'] ?? true;

        $f = ['IBLOCK_ID' => $videosIb, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'CHECK_PERMISSIONS' => 'N'];
        if (!empty($filter['BRAND'])) {
            $f['PROPERTY_BRAND'] = (int)$filter['BRAND'];
        }
        $videos = [];
        $rs = CIBlockElement::GetList(['SORT' => 'ASC', 'DATE_CREATE' => 'DESC', 'ID' => 'DESC'], $f, false, false,
            ['ID', 'NAME', 'CODE', 'SORT', 'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'DATE_CREATE']);
        while ($v = $rs->Fetch()) {
            $videos[(int)$v['ID']] = $v;
        }
        if (!$videos) {
            return [];
        }
        $values = [];
        CIBlockElement::GetPropertyValuesArray($values, $videosIb, ['ID' => array_keys($videos)],
            ['CODE' => ['URL', 'URL_NORM', 'DURATION', 'PRODUCTS', 'BRAND', 'SERIES', 'UPLOAD_DATE', 'TYPE', 'PROJECT']]);

        $productIds = [];
        $projectIds = [];
        foreach ($videos as $id => &$v) {
            $p = $values[$id] ?? [];
            $v['URL'] = trim((string)($p['URL']['VALUE'] ?? ''));
            $v['PARSED'] = LatitudoVideoUrl::parse($v['URL']);
            $v['DURATION'] = trim((string)($p['DURATION']['VALUE'] ?? ''));
            $v['SERIES'] = trim((string)($p['SERIES']['VALUE'] ?? ''));
            $v['UPLOAD_DATE'] = trim((string)($p['UPLOAD_DATE']['VALUE'] ?? ''));
            $v['BRAND'] = (int)($p['BRAND']['VALUE'] ?? 0);
            $v['PRODUCT_IDS'] = array_values(array_map('intval', array_filter((array)($p['PRODUCTS']['VALUE'] ?? []))));
            $productIds = array_merge($productIds, $v['PRODUCT_IDS']);
            $type = (string)($p['TYPE']['VALUE_XML_ID'] ?? '');
            $v['TYPE'] = isset(self::TYPES[$type]) ? $type : 'product';
            $v['PROJECT_IDS'] = array_values(array_map('intval', array_filter((array)($p['PROJECT']['VALUE'] ?? []))));
            $projectIds = array_merge($projectIds, $v['PROJECT_IDS']);
        }
        unset($v);

        $products = self::products(array_values(array_unique($productIds)));
        $projects = self::projects(array_values(array_unique($projectIds)));

        $out = [];
        foreach ($videos as $id => $v) {
            if (!$v['PARSED'] || (!empty($filter['TYPE']) && $filter['TYPE'] !== $v['TYPE'])) {
                continue;
            }
            // проект — первая включённая карточка портфолио, где стоит ролик
            $project = null;
            foreach ($v['PROJECT_IDS'] as $prid) {
                if (isset($projects[$prid])) {
                    $project = $projects[$prid];
                    break;
                }
            }
            $items = [];
            foreach ($v['PRODUCT_IDS'] as $pid) {
                $pr = $products[$pid] ?? null;
                if (!$pr || ($onlyAvailable && !$pr['AVAILABLE'])) {
                    continue;
                }
                // у товара с предложениями покупаем то предложение, к которому привязан этот ролик
                $buy = $pr['OFFERS_BY_VIDEO'][$v['PARSED']['NORM']] ?? $pr['BUY'];
                $items[] = [
                    'ID' => $pid,
                    'NAME' => $pr['NAME'],
                    'URL' => $pr['URL'],
                    'SECTION_TOP' => $pr['SECTION_TOP'],
                    'BUY_ID' => $buy['ID'],
                    'PRICE' => $buy['PRICE'],
                    'PRICE_FROM' => $pr['PRICE_FROM'],
                    'UNIT' => $buy['UNIT'],
                ];
            }
            if ($onlyAvailable && !$items && ($v['PRODUCT_IDS'] || (!$v['BRAND'] && !$project))) {
                continue;
            }

            $labels = self::variantLabels(array_column($items, 'NAME'));
            foreach ($items as $i => &$it) {
                $it['LABEL'] = $labels[$i];
            }
            unset($it);

            $category = $items ? $items[0]['SECTION_TOP'] : '';
            if (!empty($filter['CATEGORY']) && $filter['CATEGORY'] !== $category) {
                continue;
            }
            $series = $v['SERIES'] !== '' ? $v['SERIES'] : self::detectSeries($v['NAME'] . ' ' . ($items[0]['NAME'] ?? ''));

            $min = null;
            foreach ($items as $it) {
                if (!empty($it['PRICE_FROM']) && ($min === null || $it['PRICE_FROM']['VALUE'] < $min['VALUE'])) {
                    $min = $it['PRICE_FROM'];
                }
            }

            $seconds = self::durationSeconds($v['DURATION']);
            $out[] = [
                'ID' => $id,
                'NAME' => $v['NAME'],
                'CODE' => $v['CODE'],
                'DESCRIPTION' => trim(strip_tags((string)$v['PREVIEW_TEXT'])),
                'PICTURE_ID' => (int)$v['PREVIEW_PICTURE'],
                'URL' => $v['URL'],
                'HOST' => $v['PARSED']['HOST'],
                'EMBED' => $v['PARSED']['EMBED'],
                'EMBED_AUTOPLAY' => LatitudoVideoUrl::embedAutoplay($v['PARSED']),
                'DURATION' => $v['DURATION'],
                'DURATION_ISO' => $seconds ? 'PT' . intdiv($seconds, 60) . 'M' . ($seconds % 60) . 'S' : '',
                'UPLOAD_DATE' => $v['UPLOAD_DATE'] !== '' ? $v['UPLOAD_DATE'] : self::isoFromBitrix((string)$v['DATE_CREATE']),
                'BRAND' => $v['BRAND'],
                'TYPE' => $v['TYPE'],
                'TYPE_NAME' => self::TYPES[$v['TYPE']],
                'PROJECT' => $project,
                'CATEGORY' => $category,
                'SERIES' => $series,
                'PRICE_MIN' => $min,
                'PRODUCTS' => $items,
            ];
            if (!empty($filter['LIMIT']) && count($out) >= (int)$filter['LIMIT']) {
                break;
            }
        }
        return $out;
    }

    /**
     * Марки, у которых есть ролики, с их числом — для будущего перечня на странице «Видео».
     *
     * @return array<int, array{ID:int, NAME:string, CODE:string, URL:string, COUNT:int}>
     */
    public static function getBrandsWithVideo(): array
    {
        $count = [];
        foreach (self::getList() as $v) {
            if ($v['BRAND']) {
                $count[$v['BRAND']] = ($count[$v['BRAND']] ?? 0) + 1;
            }
        }
        if (!$count) {
            return [];
        }
        $out = [];
        $rs = CIBlockElement::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], ['ID' => array_keys($count), 'CHECK_PERMISSIONS' => 'N'], false, false,
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL']);
        while ($b = $rs->GetNext()) {
            $out[(int)$b['ID']] = ['ID' => (int)$b['ID'], 'NAME' => $b['~NAME'], 'CODE' => $b['~CODE'],
                'URL' => $b['~DETAIL_PAGE_URL'], 'COUNT' => $count[(int)$b['ID']]];
        }
        return $out;
    }

    /**
     * Включённые карточки портфолио: название и адрес.
     *
     * @return array<int, array{NAME:string, URL:string}>
     */
    private static function projects(array $ids): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => self::PORTFOLIO_IBLOCK, 'ID' => $ids, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false, false, ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL']);
        while ($r = $rs->GetNext()) {
            $out[(int)$r['ID']] = ['NAME' => $r['~NAME'], 'URL' => $r['~DETAIL_PAGE_URL']];
        }
        return $out;
    }

    public static function formatDuration(int $seconds): string
    {
        return $seconds > 0 ? intdiv($seconds, 60) . ':' . str_pad((string)($seconds % 60), 2, '0', STR_PAD_LEFT) : '';
    }

    public static function durationSeconds(string $s): int
    {
        if (preg_match('~^(?:(\d+):)?(\d+):(\d{2})$~', trim($s), $m)) {
            return (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3];
        }
        return 0;
    }

    public static function detectSeries(string $text): string
    {
        foreach (self::SERIES_RULES as $re => $series) {
            if (preg_match($re, $text)) {
                return $series;
            }
        }
        return '';
    }

    /**
     * Товары роликов: название, ссылка, категория, наличие, что класть в корзину и цена «от».
     *
     * @return array<int, array>
     */
    private static function products(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $products = [];
        $sectionIds = [];
        $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::CATALOG_IBLOCK, 'ID' => $ids, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false, false, ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'CATALOG_AVAILABLE', 'CATALOG_TYPE', 'CATALOG_MEASURE']);
        while ($p = $rs->GetNext()) {
            $id = (int)$p['ID'];
            $products[$id] = [
                'NAME' => trim($p['~NAME']),
                'URL' => $p['~DETAIL_PAGE_URL'],
                'SECTION_ID' => (int)$p['IBLOCK_SECTION_ID'],
                'AVAILABLE' => $p['CATALOG_AVAILABLE'] === 'Y',
                'HAS_OFFERS' => (int)$p['CATALOG_TYPE'] === \Bitrix\Catalog\ProductTable::TYPE_SKU,
                'MEASURE' => (int)$p['CATALOG_MEASURE'],
                'OFFERS_BY_VIDEO' => [],
                'BUY' => null,
                'PRICE_FROM' => 0,
            ];
            $sectionIds[] = (int)$p['IBLOCK_SECTION_ID'];
        }
        if (!$products) {
            return [];
        }

        // что можно купить: простой товар сам, у товара с предложениями — предложения в продаже
        $positions = [];   // ID позиции => ID товара
        $measure = [];     // ID позиции => единица каталога
        $videoOf = [];     // ID предложения => нормализованная ссылка его ролика
        foreach ($products as $id => $p) {
            if (!$p['HAS_OFFERS']) {
                $positions[$id] = $id;
                $measure[$id] = $p['MEASURE'];
            }
        }
        $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::OFFERS_IBLOCK, 'PROPERTY_CML2_LINK' => array_keys($products),
            'ACTIVE' => 'Y', 'CATALOG_AVAILABLE' => 'Y', 'CHECK_PERMISSIONS' => 'N'], false, false,
            ['ID', 'PROPERTY_CML2_LINK', 'PROPERTY_' . LatitudoVideoSync::OFFER_PROP, 'CATALOG_MEASURE']);
        while ($o = $rs->Fetch()) {
            $oid = (int)$o['ID'];
            $positions[$oid] = (int)$o['PROPERTY_CML2_LINK_VALUE'];
            $measure[$oid] = (int)$o['CATALOG_MEASURE'];
            $parsed = LatitudoVideoUrl::parse((string)$o['PROPERTY_' . LatitudoVideoSync::OFFER_PROP . '_VALUE']);
            if ($parsed) {
                $videoOf[$oid] = $parsed['NORM'];
            }
        }

        // цена — розничная с учётом скидок, в основной единице товара (₽/м² у доски), как в плитках
        $koef = function_exists('ndBaseUnitKoef') ? ndBaseUnitKoef($positions) : [];
        $baseUnit = self::baseUnits($positions);
        $symbols = self::measureSymbols();
        foreach ($positions as $pos => $pid) {
            $price = CCatalogProduct::GetOptimalPrice($pos, 1, self::PRICE_GROUPS, 'N');
            $value = (float)($price['RESULT_PRICE']['DISCOUNT_PRICE'] ?? 0);
            if ($value <= 0) {
                continue;
            }
            $k = $koef[$pos] ?? 1.0;
            $unitId = ($k != 1.0 && !empty($baseUnit[$pos])) ? $baseUnit[$pos] : ($measure[$pos] ?? 0);
            $buy = ['ID' => $pos, 'PRICE' => ['VALUE' => round($value * $k), 'UNIT' => $symbols[$unitId] ?? 'шт'], 'UNIT' => $symbols[$unitId] ?? 'шт'];
            $p = &$products[$pid];
            if ($p['BUY'] === null || $buy['PRICE']['VALUE'] < $p['BUY']['PRICE']['VALUE']) {
                $p['BUY'] = $buy;
                $p['PRICE_FROM'] = $buy['PRICE'];
            }
            if (isset($videoOf[$pos])) {
                $cur = $p['OFFERS_BY_VIDEO'][$videoOf[$pos]] ?? null;
                if ($cur === null || $buy['PRICE']['VALUE'] < $cur['PRICE']['VALUE']) {
                    $p['OFFERS_BY_VIDEO'][$videoOf[$pos]] = $buy;
                }
            }
            unset($p);
        }

        $tops = self::topSections(array_values(array_unique(array_filter($sectionIds))));
        foreach ($products as $id => &$p) {
            $p['SECTION_TOP'] = $tops[$p['SECTION_ID']] ?? '';
            if ($p['BUY'] === null) {
                // без цены купить нельзя — показываем как недоступный
                $p['AVAILABLE'] = false;
                $p['BUY'] = ['ID' => $id, 'PRICE' => null, 'UNIT' => ''];
            }
        }
        unset($p);
        return $products;
    }

    /** @return array<int,int> ID позиции => ID основной единицы (описание BASE_KOEF; у предложения пусто — у товара) */
    private static function baseUnits(array $parentOf): array
    {
        $ids = array_values(array_unique(array_merge(array_keys($parentOf), array_values($parentOf))));
        $own = [];
        foreach ([LatitudoVideoSync::OFFERS_IBLOCK, LatitudoVideoSync::CATALOG_IBLOCK] as $ib) {
            $values = [];
            CIBlockElement::GetPropertyValuesArray($values, $ib, ['ID' => $ids], ['CODE' => ['BASE_KOEF']]);
            foreach ($values as $id => $props) {
                $d = $props['BASE_KOEF']['DESCRIPTION'] ?? '';
                $own[(int)$id] = (int)trim((string)(is_array($d) ? reset($d) : $d));
            }
        }
        $out = [];
        foreach ($parentOf as $pos => $pid) {
            $out[$pos] = !empty($own[$pos]) ? $own[$pos] : (int)($own[$pid] ?? 0);
        }
        return $out;
    }

    /** @return array<int,string> ID единицы каталога => обозначение (м², шт, пог. м) */
    private static function measureSymbols(): array
    {
        static $out = null;
        if ($out === null) {
            $out = [];
            $rs = CCatalogMeasure::getList([], [], false, false, ['ID', 'SYMBOL_RUS', 'SYMBOL']);
            while ($m = $rs->Fetch()) {
                $out[(int)$m['ID']] = (string)($m['SYMBOL_RUS'] ?: $m['SYMBOL']);
            }
        }
        return $out;
    }

    /** @return array<int,string> ID раздела => короткая подпись его раздела верхнего уровня */
    private static function topSections(array $sectionIds): array
    {
        if (!$sectionIds) {
            return [];
        }
        $tops = [];
        $rs = CIBlockSection::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::CATALOG_IBLOCK, 'DEPTH_LEVEL' => 1, 'CHECK_PERMISSIONS' => 'N'], false,
            ['ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN']);
        while ($s = $rs->Fetch()) {
            $tops[] = $s;
        }
        $out = [];
        $rs = CIBlockSection::GetList([], ['IBLOCK_ID' => LatitudoVideoSync::CATALOG_IBLOCK, 'ID' => $sectionIds, 'CHECK_PERMISSIONS' => 'N'], false,
            ['ID', 'LEFT_MARGIN']);
        while ($s = $rs->Fetch()) {
            foreach ($tops as $t) {
                if ($s['LEFT_MARGIN'] >= $t['LEFT_MARGIN'] && $s['LEFT_MARGIN'] <= $t['RIGHT_MARGIN']) {
                    $name = trim($t['NAME']);
                    $out[(int)$s['ID']] = self::CATEGORY_NAMES[$name] ?? $name;
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Подписи вариантов в переключателе: название без общего начала
     * («… 146х23х3010 Венге» → «Венге»). Один товар — его название целиком.
     *
     * @param string[] $names
     * @return string[]
     */
    private static function variantLabels(array $names): array
    {
        if (count($names) < 2) {
            return $names;
        }
        $words = array_map(function ($n) {
            return preg_split('~\s+~u', trim($n));
        }, $names);
        $common = 0;
        while (true) {
            $w = $words[0][$common] ?? null;
            if ($w === null) {
                break;
            }
            foreach ($words as $list) {
                if (($list[$common] ?? null) !== $w || count($list) <= $common + 1) {
                    break 2;
                }
            }
            $common++;
        }
        return array_map(function ($list) {
            return implode(' ', $list);
        }, array_map(function ($list) use ($common) {
            return array_slice($list, $common);
        }, $words));
    }

    private static function isoFromBitrix(string $date): string
    {
        $t = $date !== '' ? MakeTimeStamp($date) : 0;
        return $t ? date('c', $t) : '';
    }
}
