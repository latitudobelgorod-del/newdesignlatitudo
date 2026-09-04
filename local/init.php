<?php
/**
 * Общие серверные правки нового дизайна.
 *
 * Файл лежит в /local, поэтому он в Git и приезжает на обе машины. Основной
 * init.php сайта (bitrix/php_interface/init.php) этим не заменяется: ядро
 * подключает оба и по разным путям — getLocalPath("init.php") и
 * getLocalPath("php_interface/init.php", BX_PERSONAL_ROOT), см.
 * bitrix/modules/main/include.php.
 *
 * Заводить вместо этого /local/php_interface/init.php НЕЛЬЗЯ: getLocalPath
 * нашёл бы его первым, и bitrix/php_interface/init.php перестал бы
 * подключаться совсем.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Не даём модулю капчи ломать JSON-ответы.
 *
 * ceteralabs.smartcaptcha на OnEndBufferContent дописывает в ответ свои <style>
 * и <script> — двумя разными способами (lib/eventhandlers/main.php):
 *
 *   1. Запрос ajax'ный (есть bxajaxid или заголовок X-Requested-With) — блок
 *      идёт В НАЧАЛО ответа: $content = self::ajaxInlineInit(...).$content;
 *   2. Иначе — тег captcha.js дописывается перед </head>, а если </head> в
 *      ответе нет, то В КОНЕЦ: $content .= $script.$style;
 *
 * В html оба безобидны, в JSON — нет: ответ перестаёт разбираться.
 *
 * Сначала здесь закрыли только второй способ. Из-за него в корзине не
 * пересчитывались количество и сумма: компонент sale.basket.basket получал
 * ответ, JSON.parse падал на «Unexpected non-whitespace character after JSON»,
 * и до перерисовки итогов дело не доходило. Тем же ломалась цепочка после
 * «В корзину»: jQuery не мог разобрать ответ /ajax/item.php, и счётчик корзины
 * не обновлялся (Ирина, 19 августа 2026).
 *
 * Первый способ остался незакрытым, и это не теория — на боевом latitudo.ru
 * он воспроизводится одним запросом:
 *
 *   curl -d 'PRODUCT_ID=1' https://latitudo.ru/ajax/goals.php
 *   → {"ID":"1",…}                                                 320 байт
 *
 *   он же с -H 'X-Requested-With: XMLHttpRequest'
 *   → <style>.smart-captcha{…</script>{"ID":"1",…}                1018 байт
 *
 * jQuery шлёт X-Requested-With в любом $.ajax, так что под ударом любой наш
 * JSON-эндпоинт. На vrn.easydecking.ru по этой самой причине не работало
 * «В корзину» из списка товаров: разбор падал на нулевом символе, success не
 * вызывался, кнопка крутилась вечно. Здесь чиним до того, как выстрелит.
 *
 * Чиним снаружи, не трогая модуль: свой обработчик с большим весом сортировки
 * идёт после его собственного (модуль регистрируется весом по умолчанию) и
 * срезает дописанное с обоих концов. Режем только если после обрезки остаётся
 * разбираемый JSON — не разобралось, оставляем байт в байт.
 *
 * Из html-ответов не режем ничего: в ajax-формах (попапы приезжают разметкой,
 * а не JSON) этот же блок и рисует виджет капчи.
 *
 * Правка общая с easydecking (local/init.php, easydeckingKeepJsonResponsesValid).
 * Держать одинаковыми.
 */
AddEventHandler('main', 'OnEndBufferContent', 'ndKeepJsonResponsesValid', 9999);

function ndKeepJsonResponsesValid(&$content)
{
    if (defined('ADMIN_SECTION') || !is_string($content) || $content === '') {
        return;
    }

    $head    = '<style>.smart-captcha{';
    $headEnd = '})();</script>';
    $tail    = '<script src="https://smartcaptcha.yandexcloud.net/captcha.js"';

    /* Дешёвый отсев: смотрим только начало ответа, чтобы не копировать и не
       перебирать целиком каждую html-страницу. Тег captcha.js есть и в обычной
       странице (модуль вставляет его перед </head>), так что искать по нему
       нельзя — гейт только по началу. */
    $probe = ltrim(substr($content, 0, 64));

    if ($probe === '') {
        return;
    }

    $injectedHead = (strncmp($probe, $head, strlen($head)) === 0);

    if (!$injectedHead && $probe[0] !== '{' && $probe[0] !== '[') {
        return;
    }

    $body = $content;

    if ($injectedHead) {
        $end = strpos($body, $headEnd);

        if ($end === false) {
            return;
        }

        $body = substr($body, $end + strlen($headEnd));
    }

    /* Именно strrpos: дописанное всегда в самом конце, а такая же строка может
       встретиться и внутри JSON. */
    $tailPos = strrpos($body, $tail);

    if ($tailPos !== false) {
        $body = substr($body, 0, $tailPos);
    }

    $body = trim($body);

    if ($body === '' || $body === $content || ($body[0] !== '{' && $body[0] !== '[')) {
        return;
    }

    json_decode($body);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return;
    }

    $content = $body;
}

/**
 * Приоритет своих марок в поиске: у каждого товара каталога держим числовой
 * вес марки (EasyDecking — 1, LATITUDO — 2, остальные — 3). По нему сортирует
 * выдачу bitrix:catalog.section: марка у товара — привязка к справочнику, и
 * сортировать по ней база умеет только по ID бренда, а нужный порядок из
 * номеров не выходит (Ирина, 2 сентября 2026).
 *
 * Пересчитываем при каждом сохранении товара — тогда смена марки в админке и
 * обмен с 1С не рассинхронизируют вес. Разовая заливка по каталогу —
 * tools/nd_brand_weight_fill.php.
 *
 * Логика в local/php_interface/include/latitudo_brand_weight.php.
 */
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', 'ndSyncBrandWeight');
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'ndSyncBrandWeight');

function ndSyncBrandWeight(&$arFields)
{
    /* Инфоблок сверяем числом, а не константой класса: файл подключаем уже
       после проверки, чтобы на сохранении любого другого инфоблока (а их на
       сайте десяток) не тянуть его впустую. */
    if ((int)$arFields['IBLOCK_ID'] !== 19) {
        return;
    }

    require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_brand_weight.php';
    LatitudoBrandWeight::onAfterSave($arFields);
}

/**
 * Сквозной баннер: сервис и защита от второй записи.
 *
 * На сайте всегда ровно один текущий баннер — новый запускается заменой данных
 * в существующей записи, а не созданием второй. Штатного способа запретить
 * «Добавить элемент» в админке нет, поэтому ставим обработчик: он рубит
 * добавление второго элемента в инфоблок баннера с внятным текстом ошибки.
 *
 * Сам класс подключаем лениво, уже после сверки инфоблока: на сохранении любого
 * другого инфоблока (а их на сайте десяток) тянуть его незачем.
 *
 * Логика в local/php_interface/include/latitudo_banner.php.
 */
/* Класс подключаем сразу, а не лениво в обработчике: его зовут шаблоны —
   блок главной и список товаров, — а там require делать неоткуда. Файл
   маленький и без побочных эффектов, на каждый хит это ничего не стоит. */
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_banner.php';

/**
 * Микроразметка страниц марок, портфолио и «Материалов».
 *
 * Класс зовут шаблоны компонентов — там require делать неоткуда, поэтому
 * подключаем сразу. Файл без побочных эффектов: одни статические методы.
 *
 * Логика в local/php_interface/include/latitudo_schema.php.
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_schema.php';

AddEventHandler('iblock', 'OnBeforeIBlockElementAdd', 'ndBannerSingleRecordGuard');

function ndBannerSingleRecordGuard(&$arFields)
{
    $iblockId = LatitudoBanner::iblockId();
    if (!$iblockId || (int)$arFields['IBLOCK_ID'] !== $iblockId) {
        return true;
    }

    $exists = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    )->Fetch();

    if ($exists) {
        global $APPLICATION;
        $APPLICATION->throwException(
            'Сквозной баннер на сайте один. Новый баннер запускается заменой данных '
            . 'в существующей записи (элемент #'.$exists['ID'].'), а не созданием второй.'
        );

        return false;
    }

    return true;
}

/**
 * Ложные страницы пагинации отдают 404.
 *
 * Проблема: к ЛЮБОМУ адресу можно дописать ?PAGEN_1=17, ?PAGEN_5=17,
 * ?PAGEN_55555=5648586858 — и страница отвечает 200. Битрикс при неверном
 * номере молча показывает первую страницу (CDBResult::calculatePageNumber:
 * если PAGEN вне диапазона, NavPageNomer сбрасывается в 1), поэтому дубли
 * попадают в индекс. Яндекс.Вебмастер их и нашёл (Ирина, 3 сентября 2026).
 *
 * Проверять приходится ПОСЛЕ отрисовки: сколько на странице навигаций и
 * сколько у каждой страниц, до вывода не знает никто. Поэтому висим на
 * OnEndBufferContent — буфер ещё не отдан, заголовок поставить можно.
 *
 * Страница считается настоящей, если выполняется хотя бы одно:
 *   - запрошена первая страница (PAGEN_i=1) — это сам раздел;
 *   - в разметке есть пейджер, и его ТЕКУЩАЯ страница равна запрошенной
 *     (у нового дизайна это .nd-pager__page--current, у темы — .nav-current-page);
 *   - запрошенный номер не больше самого большого номера в ссылках пейджера
 *     (страховка на случай шаблона с незнакомой разметкой — лучше пропустить
 *     лишнее, чем отдать 404 на живой странице).
 * Плюс сама навигация должна существовать: номер после PAGEN_ не может быть
 * больше числа навигаций на странице ($GLOBALS['NavNum'] считает их сам
 * Битрикс в CDBResult::NavStart).
 */
AddEventHandler('main', 'OnEndBufferContent', 'ndFakePagination404');

function ndFakePagination404(&$content)
{
    static $done = false;
    if ($done) {
        return;
    }

    // Только обычные GET-страницы: ajax-ответы и файлы не трогаем.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || defined('PUBLIC_AJAX_MODE')) {
        return;
    }
    if (!is_string($content) || stripos($content, '<html') === false) {
        return;
    }

    $pagen = [];
    foreach ($_GET as $key => $value) {
        if (preg_match('/^PAGEN_(.+)$/', $key, $m)) {
            $pagen[$m[1]] = is_array($value) ? '' : (string)$value;
        }
    }
    if (!$pagen) {
        return;
    }

    $navCount = (int)($GLOBALS['NavNum'] ?? 0);
    $fake = false;

    foreach ($pagen as $index => $value) {
        // Номер навигации и номер страницы — только целые положительные.
        if (!preg_match('/^\d+$/', (string)$index) || !preg_match('/^\d+$/', $value)) {
            $fake = true;
            break;
        }

        $index = (int)$index;
        $page = (int)$value;

        if ($index < 1 || $page < 1) {
            $fake = true;
            break;
        }
        // Такой навигации на странице нет вовсе.
        if ($index > $navCount) {
            $fake = true;
            break;
        }
        // Первая страница — это сам раздел, она есть всегда.
        if ($page === 1) {
            continue;
        }

        // Текущая страница по разметке пейджера.
        $current = 0;
        if (preg_match('/nd-pager__page--current[^>]*>\s*(\d+)/u', $content, $m)) {
            $current = (int)$m[1];
        } elseif (preg_match('/nav-current-page[^>]*>\s*(\d+)/u', $content, $m)) {
            $current = (int)$m[1];
        }
        if ($current === $page) {
            continue;
        }

        /* Страховка: номер в пределах ссылок пейджера считаем настоящим.
           Сам запрошенный номер из подсчёта выбрасываем — он попадает в
           разметку из текущего адреса (canonical, ссылки сортировки, «Показать
           ещё»), и без этого ?PAGEN_1=999 сам себя объявлял бы настоящим.
           Настоящая текущая страница ссылкой всё равно не печатается: пейджер
           помечает её как current, а этот случай проверен выше. */
        $maxLinked = 0;
        if (preg_match_all('/PAGEN_'.$index.'=(\d+)/', $content, $mm)) {
            $linked = array_diff(array_map('intval', $mm[1]), [$page]);
            $maxLinked = $linked ? max($linked) : 0;
        }
        if ($page > $maxLinked) {
            $fake = true;
            break;
        }
    }

    if (!$fake) {
        return;
    }

    $done = true;
    CHTTP::SetStatus('404 Not Found');

    /* Показываем человеку страницу «404 — страница не найдена», а не первую
       страницу списка.

       Разметку берём из page_blocks/error404_body.php — это тело корневого
       404.php, чтобы ложные адреса выглядели ровно как любая другая
       ненайденная страница. Собрать 404.php целиком отсюда нельзя: проверка
       срабатывает уже после отрисовки, а повторный заход в пролог из
       обработчика буфера кладёт ответ в ноль (проверено, откатывал).

       Ничего НЕ вырезаем: первая попытка резала разметку от .middle до
       <footer> и уносила закрывающие теги — подвал разъезжался (Ирина,
       3 сентября 2026). Поэтому блок вставляем сразу после открывающего
       .middle, а прежнее содержимое просто прячем стилем: теги остаются
       сбалансированными, шапка и подвал не страдают. */
    $bodyFile = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/error404_body.php';
    $open = strpos($content, '<div class="middle ');
    $openEnd = $open === false ? false : strpos($content, '>', $open);

    if ($openEnd !== false && is_file($bodyFile)) {
        ob_start();
        include $bodyFile;
        $body = ob_get_clean();

        if (is_string($body) && $body !== '') {
            /* Прячем прежнее содержимое страницы. Одного правила по .middle
               мало: у страниц с боковым меню (например /info/dostavka/)
               заголовок и левая колонка лежат ВЫШЕ, прямо в .wrapper_inner, и
               на скриншоте Ирины остались видны рядом с блоком ошибки
               (3 сентября 2026). Правая колонку заодно растягиваем во всю
               ширину — так же делает и сам 404.php своим инлайновым стилем. */
            $inject = '<style>'
                .'.middle > *:not(.nd-404):not(style){display:none !important;}'
                .'.nd-page-head-wrap,.left_block{display:none !important;}'
                .'.right_block{float:none !important;width:100% !important;}'
                .'</style>'
                .'<div class="nd-404">'.$body.'</div>';
            $content = substr($content, 0, $openEnd + 1).$inject.substr($content, $openEnd + 1);
        }
    }

    $content = str_ireplace('</head>', '<meta name="robots" content="noindex, follow"></head>', $content);
}


/**
 * Метки визита живут не только в сессии, но и в куке.
 *
 * Обработчик темы (PageHandler::OnBeforeProlog в bitrix/php_interface/init.php)
 * складывает utm-метки в $_SESSION. Сессия на этом хостинге живёт 24 минуты
 * (session.gc_maxlifetime = 1440) — посетитель отвлёкся, вернулся к вкладке, и
 * подмена уже не работает, хотя пришёл он по рекламе: ровно это Ирина и
 * заметила 4 сентября 2026 («ходишь по сайту — метка как будто слетает»).
 *
 * Поэтому дублируем метки в куку на 30 дней — обычный срок жизни utm в
 * аналитике. Домен ставим общий для поддоменов: региональные сайты живут на
 * *.latitudo.ru, и метка не должна теряться при переходе между ними.
 */
const ND_UTM_COOKIE = 'ND_UTM';
const ND_UTM_COOKIE_DAYS = 30;

AddEventHandler('main', 'OnBeforeProlog', 'ndUtmRemember', 100);

function ndUtmRemember()
{
    $keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_geo'];

    $fresh = [];
    foreach ($keys as $key) {
        if (!empty($_REQUEST[$key]) && is_string($_REQUEST[$key])) {
            /* Длину режем и оставляем безобидные символы: метка попадёт в куку и
               в наши сравнения, лишнего в ней быть не должно. */
            $fresh[$key] = mb_substr(preg_replace('/[^\w\-.:| ]+/u', '', $_REQUEST[$key]), 0, 100);
        }
    }

    if ($fresh) {
        $_SESSION['UTM'] = array_merge((array)($_SESSION['UTM'] ?? []), $fresh);

        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $domain = preg_match('/([a-z0-9-]+\.[a-z]{2,})$/i', $host, $m) ? '.'.$m[1] : '';

        @setcookie(ND_UTM_COOKIE, json_encode($fresh, JSON_UNESCAPED_UNICODE), [
            'expires' => time() + ND_UTM_COOKIE_DAYS * 86400,
            'path' => '/',
            'domain' => $domain,
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return;
    }

    /* Метки в адресе нет — поднимаем их из куки, чтобы сессия, умершая по
       таймауту, не отменяла подмену. */
    if (empty($_SESSION['UTM']) && !empty($_COOKIE[ND_UTM_COOKIE])) {
        $saved = json_decode((string)$_COOKIE[ND_UTM_COOKIE], true);
        if (is_array($saved) && $saved) {
            $_SESSION['UTM'] = array_intersect_key($saved, array_flip($keys));
        }
    }
}

/**
 * Пришёл ли посетитель по РЕКЛАМНОЙ ссылке — то есть с любой utm-меткой.
 *
 * Метки складывает в сессию обработчик PageHandler::OnBeforeProlog из
 * bitrix/php_interface/init.php: utm_source, utm_medium, utm_campaign,
 * utm_content, utm_term, utm_geo. Держится это до конца визита, поэтому
 * тащить метку в адресе по всему сайту не нужно.
 *
 * По этому признаку показываем подменные контакты — и почту, и телефон.
 * Раньше правилом был список источников (ya/tg/vk/maps), но подменять просили
 * при любой метке (Ирина, 4 сентября 2026): рекламные ссылки бывают и из
 * рассылок, и из прайс-агрегаторов, и перечислять их все — заведомо отставать.
 */
function ndIsUtmVisit(): bool
{
    return !empty($_SESSION['UTM']) && is_array($_SESSION['UTM']) && array_filter($_SESSION['UTM']);
}

/**
 * Узкий признак «источник из списка колл-трекинга» — ya / tg / vk / maps.
 *
 * СЕЙЧАС НЕ ИСПОЛЬЗУЕТСЯ: и почта, и телефон подменяются при любой метке
 * (ndIsUtmVisit). Оставлен на случай, если для телефона снова понадобится
 * сузить правило до конкретных каналов — тогда достаточно вернуть его вызов
 * в места подмены телефона.
 */
function ndIsAdSource(): bool
{
    $source = !empty($_SESSION['UTM']['utm_source']) ? (string)$_SESSION['UTM']['utm_source'] : '';

    return strpos($source, 'ya') !== false
        || strpos($source, 'tg') !== false
        || strpos($source, 'vk') !== false
        || strpos($source, 'maps') !== false;
}

/**
 * Подмена почты региона на подменную во ВСЁМ выводе страницы.
 *
 * Правило сайта: пришёл посетитель с рекламной utm-меткой (ya / tg / vk / maps)
 * — показываем подменные контакты, по ним считается эффективность рекламы.
 * В шаблонах это сделано точечно (шапка, контакты, шоу-румы, «О компании»), но
 * настоящие адреса остаются в ТЕКСТАХ, которые набирают в админке: статьи
 * раздела «Материалы», страницы «Правила возврата», «Заявить рекламацию» и
 * прочие (найдено обходом сайта 4 сентября 2026, восемь страниц).
 *
 * Поэтому подменяем на выходе: берём все почты регионов (свойство
 * REGION_TAG_MAIL инфоблока «Регионы») и заменяем их подменным адресом
 * текущего региона. Так закрываются и уже написанные тексты, и те, что
 * напишут завтра, — контент-менеджеру ничего помнить не нужно.
 *
 * Осознанно НЕ трогаем hr@ (резюме) и info@ (юридические тексты): просьба
 * Ирины 4 сентября 2026 — меняются только почты регионов.
 *
 * Осторожности две:
 * - не лезем в админку и в ответы, которые не являются HTML (ajax, картинки):
 *   ровно на этом обжигался модуль Яндекс-капчи, дописывавший свой скрипт в
 *   JPEG капчи (см. память latitudo-captcha-handler-sort);
 * - список почт кешируем на сутки с тегом инфоблока регионов, иначе выборка
 *   уходила бы в базу на каждый хит.
 */
/* SORT=10000 — обработчик должен отработать ПОСЛЕДНИМ.
   Реальные адреса появляются в буфере не сразу: в текстах статей стоят теги
   вида #REGION_TAG_MAIL#, и разворачивает их обработчик из
   bitrix/php_interface/init.php. Наш local/init.php подключается раньше
   (bitrix/modules/main/include.php: сначала local/init.php, потом
   php_interface/init.php), поэтому при одинаковом весе мы бежали первыми и
   видели ещё теги, а не почту — на страницах «Материалов» подмена не срабатывала. */
AddEventHandler('main', 'OnEndBufferContent', 'ndRegionMailPodmena', 10000);

function ndRegionMailPodmena(&$content)
{
    if (defined('ADMIN_SECTION') || !is_string($content) || $content === '') {
        return;
    }

    /* Ответ не HTML — не трогаем. Пустой Content-Type считаем страницей:
       у обычных страниц Битрикса он к этому моменту ещё не отправлен. */
    foreach (headers_list() as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $type = strtolower(trim(substr($header, 13)));
            if ($type !== '' && strpos($type, 'text/html') === false) {
                return;
            }
            break;
        }
    }

    if (!ndIsUtmVisit()) {
        return;
    }

    global $arRegion;
    $podmena = trim((string)($arRegion['PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE'] ?? ''));
    if ($podmena === '') {
        return;                          // у региона подменного адреса нет
    }

    $mails = ndRegionMailList();
    if (!$mails) {
        return;
    }

    /* str_ireplace, а не регулярка: адреса — обычные строки, и замена должна
       достать их и из текста, и из href="mailto:…". Сам подменный адрес из
       списка убираем, иначе заменяли бы его на самого себя. */
    $mails = array_values(array_filter($mails, static function ($mail) use ($podmena) {
        return strcasecmp($mail, $podmena) !== 0;
    }));
    if (!$mails) {
        return;
    }

    $content = str_ireplace($mails, $podmena, $content);
}

/**
 * Телефоны офисов на подменные — во всём выводе страницы.
 *
 * Зачем отдельно от почты. Номера набраны текстом в местах, где шаблон их не
 * строит: список филиалов под акцией, тексты страниц. Заменять их в шаблонах
 * поштучно бессмысленно — проще один раз на выходе, как с почтой
 * (Ирина, 4 сентября 2026: «на детальной акций выводятся контакты по филиалам,
 * сделай в них тоже подмену»).
 *
 * Правило то же, что у почты: любая utm-метка (ndIsUtmVisit). До 4 сентября
 * 2026 телефон реагировал только на ya / tg / vk / maps, но рекламные ссылки
 * бывают и из рассылок, и из агрегаторов — перечислять источники значит
 * заведомо отставать (решение Ирины).
 *
 * Соответствие «номер офиса → его подменный» берём из инфоблока контактов, а не
 * из текущего региона: в списке филиалов на акции рядом стоят номера пяти
 * городов, и каждый должен смениться на свой.
 */
AddEventHandler('main', 'OnEndBufferContent', 'ndOfficePhonePodmena', 10001);

function ndOfficePhonePodmena(&$content)
{
    if (defined('ADMIN_SECTION') || !is_string($content) || $content === '' || !ndIsUtmVisit()) {
        return;
    }

    /* Ответ не HTML — не трогаем (та же осторожность, что у почты: модуль капчи
       когда-то дописывал скрипт в JPEG). */
    foreach (headers_list() as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $type = strtolower(trim(substr($header, 13)));
            if ($type !== '' && strpos($type, 'text/html') === false) {
                return;
            }
            break;
        }
    }

    $map = ndOfficePhoneMap();
    if (!$map) {
        return;
    }

    $content = strtr($content, $map);
}

/**
 * Карта замен телефонов: «как написано» => «подменный», плюс варианты для
 * ссылок tel:.
 *
 * Один и тот же номер на странице встречается в двух видах: человеческом
 * («+7 (495) 135-93-05») и цифрами в href («+74951359305», иногда с 8 вместо
 * +7). Готовим оба, иначе текст сменится, а ссылка поведёт на старый номер.
 *
 * @return array<string,string>
 */
function ndOfficePhoneMap(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    if (!\CModule::IncludeModule('iblock')) {
        return $map;
    }

    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'nd_office_phones', '/nd/office_phones')) {
        $map = (array)$cache->getVars();

        return $map;
    }

    $cache->startDataCache();
    $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
    $taggedCache->startTagCache('/nd/office_phones');
    $taggedCache->registerTag('iblock_id_10');

    /* Цифровой вид номера: +7XXXXXXXXXX. Восьмёрку в начале приводим к семёрке,
       чтобы «8 (495)…» и «+7 (495)…» считались одним номером. */
    $digits = static function ($phone) {
        $only = preg_replace('/\D+/', '', (string)$phone);
        if (strlen($only) === 11 && $only[0] === '8') {
            $only = '7'.substr($only, 1);
        }

        return strlen($only) === 11 ? $only : '';
    };

    $rs = \CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => 10, 'ACTIVE' => 'Y', '!PROPERTY_PHONE_PODMENA' => false, 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_PHONE', 'PROPERTY_PHONE_PODMENA']
    );

    $pairs = [];
    while ($row = $rs->GetNext()) {
        $target = trim((string)$row['~PROPERTY_PHONE_PODMENA_VALUE']);
        $targetDigits = $digits($target);
        if ($target === '' || $targetDigits === '') {
            continue;
        }

        foreach ((array)$row['~PROPERTY_PHONE_VALUE'] as $phone) {
            $phone = trim((string)$phone);
            $phoneDigits = $digits($phone);
            if ($phone === '' || $phoneDigits === '' || $phoneDigits === $targetDigits) {
                continue;
            }

            /* Человеческое написание — как в базе, оно же стоит в текстах. */
            $pairs[$phone] = $target;
            /* Ссылки tel: и любые цифровые формы. */
            $pairs['+'.$phoneDigits] = '+'.$targetDigits;
            $pairs['8'.substr($phoneDigits, 1)] = '8'.substr($targetDigits, 1);
        }
    }

    /* Длинные строки заменяем первыми: strtr() и так берёт самое длинное
       совпадение, но порядок делает карту понятной при отладке. */
    uksort($pairs, static function ($a, $b) {
        return strlen($b) <=> strlen($a);
    });

    $map = $pairs;

    $taggedCache->endTagCache();
    $cache->endDataCache($map);

    return $map;
}

/**
 * Почты всех регионов: свойство REGION_TAG_MAIL инфоблока «Регионы».
 *
 * @return string[]
 */
function ndRegionMailList(): array
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }

    $list = [];
    if (!\CModule::IncludeModule('iblock')) {
        return $list;
    }

    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'nd_region_mails', '/nd/region_mails')) {
        $list = (array)$cache->getVars();

        return $list;
    }

    $iblock = \CIBlock::GetList([], ['CODE' => 'aspro_next_regions', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if (!$iblock) {
        return $list;
    }

    $cache->startDataCache();
    $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
    $taggedCache->startTagCache('/nd/region_mails');
    $taggedCache->registerTag('iblock_id_'.$iblock['ID']);

    $rs = \CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblock['ID'], 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'PROPERTY_REGION_TAG_MAIL']
    );
    while ($row = $rs->Fetch()) {
        $mail = trim((string)$row['PROPERTY_REGION_TAG_MAIL_VALUE']);
        /* Длину проверяем не из вредности: пустое или мусорное значение в
           str_ireplace превратилось бы в замену половины страницы. */
        if ($mail !== '' && strpos($mail, '@') !== false && strlen($mail) > 5) {
            $list[$mail] = $mail;
        }
    }

    $list = array_values($list);

    $taggedCache->endTagCache();
    $cache->endDataCache($list);

    return $list;
}

/**
 * Режим работы из свойства офиса — в формате schema.org (openingHours).
 *
 * В инфоблоке 10 расписание лежит свободным текстом («С понедельника по
 * пятницу: с 9:00 до 18:00, суббота и воскресенье: выходные дни»), а поисковики
 * ждут машинный вид «Mo-Fr 09:00-18:00». Разбираем только этот, самый частый,
 * шаблон: дни недели по-русски плюс «с ЧЧ:ММ до ЧЧ:ММ». Не разобрали —
 * возвращаем пустую строку, и разметки просто не будет: лучше ничего, чем
 * неверные часы (Ирина, 4 сентября 2026, проверка микроразметки).
 */
function ndSchemaOpeningHours($text)
{
    $text = mb_strtolower(strip_tags((string) $text));
    if (!$text) {
        return '';
    }

    if (!preg_match('/с\s*(\d{1,2})[:.](\d{2})\s*до\s*(\d{1,2})[:.](\d{2})/u', $text, $time)) {
        return '';
    }
    $hours = sprintf('%02d:%s-%02d:%s', $time[1], $time[2], $time[3], $time[4]);

    $days = array(
        'понедельник' => 'Mo', 'вторник' => 'Tu', 'сред' => 'We', 'четверг' => 'Th',
        'пятниц' => 'Fr', 'суббот' => 'Sa', 'воскресень' => 'Su',
    );

    /* «с понедельника по пятницу» — диапазон; иначе берём первый названный день. */
    if (preg_match('/(?:с|со)\s+(\S+?)\s+по\s+(\S+?)[\s:,]/u', $text, $range)) {
        $from = $to = '';
        foreach ($days as $ru => $en) {
            if (mb_strpos($range[1], $ru) === 0) {
                $from = $en;
            }
            if (mb_strpos($range[2], $ru) === 0) {
                $to = $en;
            }
        }
        if ($from && $to) {
            return $from.'-'.$to.' '.$hours;
        }
    }

    return '';
}

/**
 * Переехавший товар или раздел каталога — 301 на новый адрес.
 *
 * 4 сентября 2026 у «Уличных диванов» появились подразделы по цветам, и 161
 * товар переехал из раздела в подраздел: адрес карточки строится по основному
 * разделу, поэтому прежние ссылки стали отдавать 404 — а они в индексе
 * поисковиков, в рекламе и в чужих статьях.
 *
 * Списком такие переезды вести бессмысленно: он устареет на следующем
 * перемещении. Вместо этого разбираем сам адрес: последний кусок пути — это
 * символьный код товара (или раздела). Если элемент с таким кодом жив, а
 * настоящий его адрес другой — отправляем 301 туда. Работает и для будущих
 * переносов, и для тех, что уже случились раньше.
 *
 * Вызывается ТОЛЬКО из /404.php, то есть когда страница и так не найдена:
 * на обычных запросах ни одного лишнего обращения к базе не делается.
 */
function ndMovedElementRedirect()
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }
    if (!class_exists('CIBlockElement') || !CModule::IncludeModule('iblock')) {
        return;
    }

    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $path = '/'.trim($path, '/').'/';

    /* Только каталог: в остальных разделах сайта коды не уникальны и можно
       увести человека не туда. */
    if (strpos($path, '/catalog/') !== 0) {
        return;
    }

    $parts = array_values(array_filter(explode('/', $path), 'strlen'));
    $code = end($parts);

    /* Служебные куски пути (фильтр, пагинация, сравнение) кодами не бывают. */
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{4,}$/', (string) $code)) {
        return;
    }

    $iblockId = 19;   // «Каталог» — тот же инфоблок, что и у карточек товара
    $target = '';

    $rs = CIBlockElement::GetList(
        array(),
        array('IBLOCK_ID' => $iblockId, '=CODE' => $code, 'ACTIVE' => 'Y'),
        false,
        array('nTopCount' => 1),
        array('ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL')
    );
    if ($row = $rs->GetNext()) {
        $target = (string) $row['DETAIL_PAGE_URL'];
    }

    /* Не товар — вдруг переехал раздел. */
    if (!$target) {
        $section = CIBlockSection::GetList(
            array(),
            array('IBLOCK_ID' => $iblockId, '=CODE' => $code, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
            false,
            array('ID', 'IBLOCK_ID', 'SECTION_PAGE_URL')
        )->GetNext();
        if ($section) {
            $target = (string) $section['SECTION_PAGE_URL'];
        }
    }

    if (!$target) {
        return;
    }

    $target = '/'.trim((string) parse_url($target, PHP_URL_PATH), '/').'/';
    if ($target === $path || $target === '//') {
        return;   // адрес тот же — значит 404 по другой причине, не зацикливаемся
    }

    /* Метки и прочие параметры переносим на новый адрес: по ним считают рекламу. */
    $query = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    if ($query !== '') {
        $target .= '?'.$query;
    }

    LocalRedirect($target, false, '301 Moved Permanently');
}

/**
 * Open Graph: дописываем og:title, og:type и og:url там, где их нет.
 *
 * Тема печатает только og:image и og:description, а og:title/type/url ставит
 * лишь карточка товара (main6_newdesign). На разделах каталога, статьях и
 * прочих страницах их не было вовсе — валидатор Яндекса ругается «поле
 * отсутствует или пусто», а соцсети и мессенджеры показывают в превью адрес
 * вместо заголовка (Ирина, 4 сентября 2026).
 *
 * Делаем в буфере, а не в шапке: заголовок страницы часто ставит компонент,
 * то есть уже ПОСЛЕ вывода <head>. Здесь готовая страница, и <title> можно
 * просто прочитать.
 *
 * Порядок 10050 — после подмен почты и телефона: пусть в og:title попадёт
 * тот же текст, что и в заголовке.
 */
AddEventHandler('main', 'OnEndBufferContent', 'ndOpenGraphFallback', 10050);

function ndOpenGraphFallback(&$content)
{
    if (!is_string($content) || $content === '' || stripos($content, '</head>') === false) {
        return;   // не html-страница (ajax, xml, картинка) — уходим
    }

    $add = array();

    if (stripos($content, 'property="og:title"') === false) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m)) {
            $title = trim(strip_tags($m[1]));
            if ($title !== '') {
                $add[] = '<meta property="og:title" content="'.htmlspecialcharsbx(html_entity_decode($title, ENT_QUOTES, 'UTF-8')).'" />';
            }
        }
    }

    if (stripos($content, 'property="og:type"') === false) {
        $add[] = '<meta property="og:type" content="website" />';
    }

    if (stripos($content, 'property="og:url"') === false) {
        /* Канонический адрес, если он на странице есть, — иначе текущий путь без
           параметров: с метками og:url плодил бы дубли в шарах. */
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)/i', $content, $m)) {
            $url = $m[1];
        } else {
            $url = (CMain::IsHTTPS() ? 'https://' : 'http://').$_SERVER['HTTP_HOST']
                .(string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        }
        $add[] = '<meta property="og:url" content="'.htmlspecialcharsbx($url).'" />';
    }

    if (!$add) {
        return;
    }

    $pos = stripos($content, '</head>');
    $content = substr($content, 0, $pos)."
".implode("
", $add)."
".substr($content, $pos);
}
