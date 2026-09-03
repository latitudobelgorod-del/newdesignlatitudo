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
