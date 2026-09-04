<?php

/**
 * Микроразметка страниц-сущностей: статья «Материалов», проект портфолио, марка.
 *
 * Зачем. Разметка на сайте была только у товара, хлебных крошек и главной
 * (проверка микроразметки, Ирина, сентябрь 2026). Страницы марок, портфолио и
 * «Материалов» поисковик читал как обычный текст: ни автора, ни даты, ни
 * привязки фотографий к странице. Здесь собираем для них графы JSON-LD.
 *
 * Почему JSON-LD, а не микроданные в вёрстке. Разметка темы Аспро уже
 * расставлена по шаблонам кусками (itemprop без itemscope, дубли одного
 * атрибута), править её по месту — значит трогать десяток файлов темы и
 * получать по два описания одной сущности на страницу. Граф отдельным
 * скриптом собирается в одном месте и виден целиком.
 *
 * Организация во всех графах — одна и та же запись с идентификатором
 * <адрес сайта>/#organization; её полное описание печатает главная
 * (page_blocks/schema_org_mainpage_newdesign.php).
 *
 * Фотографии. Каждой картинке даём ImageObject с полным адресом файла,
 * названием, подписью и размерами — по ним Яндекс.Картинки понимают, что
 * снимок относится к странице. Снимки из редактора блоков (sprint.editor)
 * достаём из самого свойства, а не собираем при отрисовке: у компонента
 * редактора свой кеш, и на кешированном хите шаблоны блоков не выполняются.
 */
class LatitudoSchema
{
    /** Постоянный идентификатор организации — тот же, что на главной. */
    const ORG_ANCHOR = '/#organization';

    /** Логотип для publisher: растровый, у svg поисковик не знает размеров. */
    const ORG_LOGO = '/images/company/logo.png';
    const ORG_LOGO_WIDTH = 400;
    const ORG_LOGO_HEIGHT = 92;

    /** Крупнее этого фотографии в разметку не отдаём — хватает для картинок. */
    const IMAGE_MAX_SIDE = 1600;

    /** https://текущий-хост (у регионов свой поддомен). */
    public static function host()
    {
        $scheme = (CMain::IsHTTPS() ? 'https' : 'http');

        return $scheme.'://'.$_SERVER['HTTP_HOST'];
    }

    /** Относительный адрес → абсолютный. Пустой и внешний отдаём как есть. */
    public static function abs($url)
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return self::host().'/'.ltrim($url, '/');
    }

    /** Организация: полный узел для графа страницы. */
    public static function organization()
    {
        return array(
            '@type' => 'Organization',
            '@id' => self::host().self::ORG_ANCHOR,
            'name' => 'Латитудо',
            'url' => self::host().'/',
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => self::host().self::ORG_LOGO,
                'width' => self::ORG_LOGO_WIDTH,
                'height' => self::ORG_LOGO_HEIGHT,
            ),
        );
    }

    /** Сайт: тот же идентификатор, что печатает главная. */
    public static function website()
    {
        return array(
            '@type' => 'WebSite',
            '@id' => self::host().'/#website',
            'name' => 'Латитудо',
            'url' => self::host().'/',
            'inLanguage' => 'ru-RU',
            'publisher' => self::organizationRef(),
        );
    }

    /** Ссылка на организацию — когда полное описание уже есть в том же графе. */
    public static function organizationRef()
    {
        return array('@id' => self::host().self::ORG_ANCHOR);
    }

    /**
     * Дата Битрикса → ISO 8601.
     *
     * На вход приходит и «01.09.2026 14:20:00», и «01.09.2026», и уже готовый
     * timestamp. Не разобрали — свойства просто не будет.
     */
    public static function date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $stamp = MakeTimeStamp($value);
        if (!$stamp) {
            $stamp = strtotime($value);
        }

        return $stamp ? date('c', $stamp) : '';
    }

    /**
     * ImageObject по картинке.
     *
     * $file — массив CFile (ID, SRC, WIDTH, HEIGHT) либо ID файла. Большие
     * снимки ужимаем до IMAGE_MAX_SIDE: в разметку нужен адрес существующего
     * файла, а исходники из фотоаппарата весят по несколько мегабайт.
     */
    public static function image($file, $name, $pageUrl = '', $caption = '')
    {
        if (!is_array($file)) {
            $file = CFile::GetFileArray($file);
        }
        /* ID обязателен: у галереи товара без снимков вместо картинки лежит
           заглушка «нет фото» из шаблона — у неё есть SRC, но записи в базе
           файлов нет, и в разметке ей делать нечего. */
        if (empty($file['SRC']) || empty($file['ID'])) {
            return array();
        }

        $src = $file['SRC'];
        $width = (int) $file['WIDTH'];
        $height = (int) $file['HEIGHT'];

        if (max($width, $height) > self::IMAGE_MAX_SIDE) {
            $resized = CFile::ResizeImageGet(
                $file,
                array('width' => self::IMAGE_MAX_SIDE, 'height' => self::IMAGE_MAX_SIDE),
                BX_RESIZE_IMAGE_PROPORTIONAL,
                true
            );
            if (!empty($resized['src'])) {
                $src = $resized['src'];
                $width = (int) $resized['width'];
                $height = (int) $resized['height'];
            }
        }

        $node = array(
            '@type' => 'ImageObject',
            'contentUrl' => self::abs($src),
            'url' => self::abs($src),
        );

        if ($width && $height) {
            $node['width'] = $width;
            $node['height'] = $height;
        }

        $name = trim(strip_tags(html_entity_decode((string) $name, ENT_QUOTES, 'UTF-8')));
        if ($name !== '') {
            $node['name'] = $name;
        }

        $caption = trim(strip_tags(html_entity_decode((string) $caption, ENT_QUOTES, 'UTF-8')));
        if ($caption !== '' && $caption !== $name) {
            $node['caption'] = $caption;
        }

        if ($pageUrl) {
            /* Страница, на которой снимок опубликован: по ней Яндекс.Картинки
               ведут из выдачи обратно на сайт. */
            $node['isPartOf'] = array('@id' => self::abs($pageUrl).'#webpage');
        }

        return $node;
    }

    /**
     * Картинки из свойства редактора блоков (sprint.editor).
     *
     * Значение свойства — JSON с деревом блоков; расположение снимков внутри
     * зависит от вёрстки блока (колонки, вложенные раскладки), поэтому дерево
     * обходим целиком и собираем всё, у чего есть file.ID.
     *
     * @return array список array('ID' => int, 'DESC' => string)
     */
    public static function editorImages($iblockId, $elementId, $propertyCodes)
    {
        $iblockId = (int) $iblockId;
        $elementId = (int) $elementId;
        if (!$iblockId || !$elementId || !$propertyCodes) {
            return array();
        }
        if (!is_array($propertyCodes)) {
            $propertyCodes = array($propertyCodes);
        }
        if (!CModule::IncludeModule('iblock')) {
            return array();
        }

        $found = array();
        /* По одному коду за запрос: фильтр GetProperty понимает CODE строкой,
           списком — нет. Свойств редактора у элемента одно-два. */
        foreach ($propertyCodes as $code) {
            $rs = CIBlockElement::GetProperty(
                $iblockId,
                $elementId,
                array(),
                array('CODE' => $code)
            );
            while ($prop = $rs->Fetch()) {
                if (empty($prop['VALUE'])) {
                    continue;
                }

                foreach (self::editorImagesFromValue($prop['VALUE']) as $image) {
                    if (!isset($found[$image['ID']])) {
                        $found[$image['ID']] = $image;
                    }
                }
            }
        }

        return array_values($found);
    }

    /**
     * Картинки из уже прочитанного значения свойства редактора.
     *
     * Отдельно от editorImages: карта изображений читает свойства всех
     * элементов инфоблока одним проходом и в базу за каждым не ходит.
     *
     * @return array список array('ID' => int, 'DESC' => string)
     */
    public static function editorImagesFromValue($value)
    {
        if (is_array($value)) {
            $value = isset($value['TEXT']) ? $value['TEXT'] : '';
        }

        $data = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return array();
        }

        $found = array();
        self::walkEditorImages($data, $found);

        return array_values($found);
    }

    /** Рекурсивный обход дерева блоков редактора. */
    private static function walkEditorImages($node, array &$found)
    {
        if (!is_array($node)) {
            return;
        }

        if (isset($node['file']['ID']) && (int) $node['file']['ID'] > 0) {
            $id = (int) $node['file']['ID'];
            if (!isset($found[$id])) {
                $found[$id] = array(
                    'ID' => $id,
                    'DESC' => isset($node['desc']) ? (string) $node['desc'] : '',
                );
            }
        }

        foreach ($node as $key => $child) {
            if ($key !== 'file' && is_array($child)) {
                self::walkEditorImages($child, $found);
            }
        }
    }

    /**
     * Узлы ImageObject по галерее шаблона.
     *
     * Ждём список вида array('DETAIL' => массив CFile, 'TITLE' =>, 'ALT' =>) —
     * так галереи собирают result_modifier'ы портфолио и марок.
     */
    public static function imagesFromGallery($gallery, $fallbackName, $pageUrl = '')
    {
        $nodes = array();
        foreach ((array) $gallery as $item) {
            if (empty($item['DETAIL'])) {
                continue;
            }

            $name = '';
            foreach (array('TITLE', 'ALT') as $key) {
                if (!empty($item[$key])) {
                    $name = $item[$key];
                    break;
                }
            }

            $node = self::image($item['DETAIL'], ($name ? $name : $fallbackName), $pageUrl);
            if ($node) {
                $nodes[$node['contentUrl']] = $node;
            }
        }

        return array_values($nodes);
    }

    /** Узлы ImageObject по картинкам редактора блоков. */
    public static function imagesFromEditor($iblockId, $elementId, $propertyCodes, $fallbackName, $pageUrl = '')
    {
        $nodes = array();
        foreach (self::editorImages($iblockId, $elementId, $propertyCodes) as $image) {
            $desc = self::text($image['DESC']);
            $node = self::image($image['ID'], ($desc !== '' ? $desc : $fallbackName), $pageUrl, $desc);
            if ($node) {
                $nodes[$node['contentUrl']] = $node;
            }
        }

        return array_values($nodes);
    }

    /** Склейка списков картинок без повторов: один файл — один узел. */
    public static function mergeImages()
    {
        $nodes = array();
        foreach (func_get_args() as $list) {
            foreach ((array) $list as $node) {
                if (!empty($node['contentUrl'])) {
                    $nodes[$node['contentUrl']] = $node;
                }
            }
        }

        return array_values($nodes);
    }

    /**
     * Даты элемента инфоблока.
     *
     * Компоненты кладут в arResult только те поля, что перечислены в
     * FIELD_CODE, и даты там есть не всегда — а без datePublished статья для
     * поисковика неполная. Спрашиваем базу отдельно и только при нужде.
     *
     * @return array array('PUBLISHED' => строка, 'MODIFIED' => строка)
     */
    public static function elementDates($iblockId, $elementId)
    {
        static $cache = array();

        $iblockId = (int) $iblockId;
        $elementId = (int) $elementId;
        $key = $iblockId.':'.$elementId;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $dates = array('PUBLISHED' => '', 'MODIFIED' => '');
        if ($iblockId && $elementId && CModule::IncludeModule('iblock')) {
            $row = CIBlockElement::GetList(
                array(),
                array('IBLOCK_ID' => $iblockId, 'ID' => $elementId),
                false,
                array('nTopCount' => 1),
                array('ID', 'DATE_CREATE', 'ACTIVE_FROM', 'TIMESTAMP_X')
            )->Fetch();

            if ($row) {
                $dates['PUBLISHED'] = $row['ACTIVE_FROM'] ? $row['ACTIVE_FROM'] : $row['DATE_CREATE'];
                $dates['MODIFIED'] = $row['TIMESTAMP_X'];
            }
        }

        $cache[$key] = $dates;

        return $dates;
    }

    /**
     * Общая часть графа страницы: WebPage со ссылкой на организацию.
     *
     * Идентификатор <адрес страницы>#webpage — к нему привязываем фотографии
     * (isPartOf) и основную сущность страницы (mainEntity).
     */
    public static function webPage($pageUrl, $name, $description = '')
    {
        $node = array(
            '@type' => 'WebPage',
            '@id' => self::abs($pageUrl).'#webpage',
            'url' => self::abs($pageUrl),
            'name' => self::text($name),
            'inLanguage' => 'ru-RU',
            'isPartOf' => array('@id' => self::host().'/#website'),
            'publisher' => self::organizationRef(),
        );

        if ($description = self::text($description)) {
            $node['description'] = $description;
        }

        return $node;
    }

    /** Текст из инфоблока → чистая строка для разметки. */
    public static function text($value, $limit = 0)
    {
        if (is_array($value)) {
            $value = isset($value['TEXT']) ? $value['TEXT'] : '';
        }

        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));

        if ($limit > 0 && mb_strlen($value, 'UTF-8') > $limit) {
            $value = rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')).'…';
        }

        return $value;
    }

    /**
     * Граф страницы-статьи: «Материалы» и проекты портфолио.
     *
     * Оба инфоблока устроены одинаково — заголовок, текст, детальная картинка
     * и галереи, — поэтому сборка общая. Проект отличается только типом
     * (Article против более узкого, который поисковику ничего не даёт) и
     * набором фотографий, а он приходит параметром.
     *
     * $p: URL, NAME, HEADLINE, DESCRIPTION, IMAGES (узлы ImageObject),
     *     DATE_PUBLISHED, DATE_MODIFIED, SECTION (рубрика, необязательно).
     */
    public static function articleGraph(array $p)
    {
        $url = self::abs($p['URL']);
        $name = self::text($p['NAME']);
        $description = self::text(isset($p['DESCRIPTION']) ? $p['DESCRIPTION'] : '', 500);
        $images = isset($p['IMAGES']) ? array_values(array_filter((array) $p['IMAGES'])) : array();

        $article = array(
            '@type' => 'Article',
            '@id' => $url.'#article',
            /* headline у поисковиков ограничен сотней с небольшим символов —
               длинные заголовки они просто отбрасывают. */
            'headline' => self::text(isset($p['HEADLINE']) && $p['HEADLINE'] ? $p['HEADLINE'] : $name, 110),
            'name' => $name,
            'mainEntityOfPage' => array('@id' => $url.'#webpage'),
            'url' => $url,
            'inLanguage' => 'ru-RU',
            'author' => self::organizationRef(),
            'publisher' => self::organizationRef(),
        );

        if ($description !== '') {
            $article['description'] = $description;
        }
        if ($images) {
            $article['image'] = $images;
        }
        $published = self::date(isset($p['DATE_PUBLISHED']) ? $p['DATE_PUBLISHED'] : '');
        $modified = self::date(isset($p['DATE_MODIFIED']) ? $p['DATE_MODIFIED'] : '');
        if ($published === '' && !empty($p['IBLOCK_ID']) && !empty($p['ID'])) {
            /* Компонент кладёт в arResult только поля из FIELD_CODE, и даты
               там есть не всегда — тогда спрашиваем базу. */
            $dates = self::elementDates($p['IBLOCK_ID'], $p['ID']);
            $published = self::date($dates['PUBLISHED']);
            if ($modified === '') {
                $modified = self::date($dates['MODIFIED']);
            }
        }
        if ($published !== '') {
            $article['datePublished'] = $published;
        }
        if ($modified !== '') {
            $article['dateModified'] = $modified;
        }
        if ($section = self::text(isset($p['SECTION']) ? $p['SECTION'] : '')) {
            $article['articleSection'] = $section;
        }

        $webPage = self::webPage($url, $name, $description);
        $webPage['mainEntity'] = array('@id' => $article['@id']);

        return array(self::organization(), self::website(), $webPage, $article);
    }

    /**
     * Граф страницы марки.
     *
     * Марка — не организация: производителей у нас несколько, и объявлять
     * каждого Organization значило бы спорить с записью самой «Латитудо».
     * Brand — ровно то, чем марка и является; страницу описываем как
     * CollectionPage, потому что на ней перечислены товары этой марки.
     *
     * $p: URL, NAME, DESCRIPTION, LOGO (массив CFile или ID), PRODUCTS
     *     (список array('NAME' =>, 'URL' =>) для ItemList, необязательно).
     */
    public static function brandGraph(array $p)
    {
        $url = self::abs($p['URL']);
        $name = self::text($p['NAME']);
        $description = self::text(isset($p['DESCRIPTION']) ? $p['DESCRIPTION'] : '', 500);

        $brand = array(
            '@type' => 'Brand',
            '@id' => $url.'#brand',
            'name' => $name,
            'url' => $url,
        );

        if ($description !== '') {
            $brand['description'] = $description;
        }
        if (!empty($p['LOGO']) && ($logo = self::image($p['LOGO'], $name))) {
            $brand['logo'] = $logo;
        }

        $webPage = self::webPage($url, $name, $description);
        $webPage['@type'] = 'CollectionPage';
        $webPage['mainEntity'] = array('@id' => $brand['@id']);

        $nodes = array(self::organization(), self::website(), $webPage, $brand);

        $products = isset($p['PRODUCTS']) ? array_values(array_filter((array) $p['PRODUCTS'])) : array();
        if ($products) {
            $items = array();
            foreach ($products as $i => $product) {
                if (empty($product['URL'])) {
                    continue;
                }
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => count($items) + 1,
                    'name' => self::text($product['NAME']),
                    'url' => self::abs($product['URL']),
                );
            }
            if ($items) {
                $nodes[] = array(
                    '@type' => 'ItemList',
                    '@id' => $url.'#products',
                    'name' => 'Товары марки '.$name,
                    'numberOfItems' => count($items),
                    'itemListElement' => $items,
                );
            }
        }

        return $nodes;
    }

    /** Печать графа. Пустые узлы отбрасываем, пустой граф не печатаем вовсе. */
    public static function printGraph(array $nodes)
    {
        $nodes = array_values(array_filter($nodes));
        if (!$nodes) {
            return;
        }

        $graph = array(
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        );

        echo '<script type="application/ld+json">'
            .\Bitrix\Main\Web\Json::encode($graph)
            .'</script>';
    }
}
