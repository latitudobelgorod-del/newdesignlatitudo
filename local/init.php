<?php
/**
 * Общие серверные правки нового дизайна.
 *
 * Файл лежит в /local, поэтому он в Git и приезжает на обе машины. Основной
 * init.php сайта (bitrix/php_interface/init.php) этим не заменяется: ядро
 * подключает оба, /local/init.php раньше (bitrix/modules/main/include.php).
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Не даём модулю капчи ломать JSON-ответы.
 *
 * ceteralabs.smartcaptcha на OnEndBufferContent дописывает свой <script> и
 * <style> в ответ, у которого не нашлось </head> (см. lib/eventhandlers/main.php,
 * ветка else). В html это безобидно, а в JSON — нет: ответ перестаёт
 * разбираться, и обработчик молча выходит.
 *
 * Из-за этого в корзине не пересчитывались количество и сумма: компонент
 * sale.basket.basket получал ответ, JSON.parse падал на «Unexpected
 * non-whitespace character after JSON», и до перерисовки итогов дело не
 * доходило. Тем же ломалась цепочка после «В корзину»: jQuery не мог разобрать
 * ответ /ajax/item.php, и счётчик корзины не обновлялся (Ирина, 19 августа 2026).
 *
 * Чиним снаружи, не трогая модуль: свой обработчик с большим весом сортировки
 * идёт после его собственного и срезает дописанное — но только если ответ и
 * правда JSON и только если после обрезки он разбирается. Ошибиться такой
 * проверкой нельзя: не разобралось — оставляем как было.
 */
AddEventHandler('main', 'OnEndBufferContent', 'ndKeepJsonResponsesValid', 9999);

function ndKeepJsonResponsesValid(&$content)
{
    if (defined('ADMIN_SECTION')) {
        return;
    }

    $probe = ltrim($content);
    if ($probe === '' || ($probe[0] !== '{' && $probe[0] !== '[')) {
        return;
    }

    // Ответ и так в порядке — модуль до него не добрался.
    if (json_decode($content) !== null) {
        return;
    }

    $marker = '<script src="https://smartcaptcha.yandexcloud.net/captcha.js"';
    $pos = strpos($content, $marker);
    if ($pos === false) {
        return;
    }

    $cut = rtrim(substr($content, 0, $pos));
    if (json_decode($cut) !== null) {
        $content = $cut;
    }
}
