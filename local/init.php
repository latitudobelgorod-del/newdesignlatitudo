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
