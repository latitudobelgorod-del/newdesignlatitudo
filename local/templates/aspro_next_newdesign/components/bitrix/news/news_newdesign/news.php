<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
} ?>
<?php
/**
 * Страница «Акции и скидки» нового дизайна.
 *
 * Копия асprovского шаблона `news`: оригинал не трогаем, он остаётся старому
 * дизайну. Страница /sale/index.php выбирает имя шаблона по SITE_TEMPLATE_ID.
 *
 * Порядок по макету: заголовок, вводный текст, разделитель, плитки акций,
 * разделитель, переключатель «Активные / Завершенные».
 *
 * Отбор по активности («активные» — ещё идут, «завершенные» — дата окончания
 * прошла) взят из старого шаблона один в один: он кладёт условие в глобальный
 * фильтр с именем из FILTER_NAME, и сделать это надо ДО вывода списка.
 * Фильтр по региону (arRegionLink) ставится вне шаблона — его не трогаем.
 */
$this->setFrameMode(true);

$arItemFilter = CNext::GetIBlockAllElementsFilter($arParams);

if ($arParams['CACHE_GROUPS'] == 'Y') {
    $arItemFilter['CHECK_PERMISSIONS'] = 'Y';
    $arItemFilter['GROUPS'] = $GLOBALS['USER']->GetGroups();
}

$itemsCnt = CNextCache::CIblockElement_GetList(
    ['CACHE' => ['TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]],
    $arItemFilter,
    []
);

if ($arParams['USE_RSS'] !== 'N') {
    CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
}

/* Переключатель активности нужен только на самой странице акций — так было и в
   старом шаблоне (проверка $arResult['FOLDER'] === '/sale/'). */
$ndHasActivity = ($arResult['FOLDER'] === '/sale/' && $arParams['USE_FILTER'] !== 'N');
$ndActivity = isset($_GET['activity']) ? (int) $_GET['activity'] : 1;

if ($ndHasActivity) {
    if ($ndActivity === 1) {
        $GLOBALS[$arParams['FILTER_NAME']][] = [
            '>=DATE_ACTIVE_TO' => ConvertDateTime(date('d.m.Y'), 'DD.MM.YYYY'),
            '<=DATE_ACTIVE_FROM' => [false, ConvertTimeStamp(false, 'FULL')],
        ];
    } else {
        $GLOBALS[$arParams['FILTER_NAME']][] = [
            '<DATE_ACTIVE_TO' => ConvertDateTime(date('d.m.Y'), 'DD.MM.YYYY'),
        ];
    }
}

/* Скрипт кнопки «Показать ещё» — общий с портфолио и отзывами. Подключаем
   тегом здесь: <head> уже отдан, и AddHeadScript туда не попадёт. */
if (!defined('ND_UI_JS')) {
    define('ND_UI_JS', true);
    $ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
    $ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
    ?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?php
}
?>
<h1 id="pagetitle" class="nd-sale__h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
<div class="nd-sale__lead">
    <?php
    /* Своя включаемая область: у старого дизайна на этой странице свой текст
       (index_inc.php), и терять его нельзя. */
    $APPLICATION->IncludeComponent(
        'bitrix:main.include',
        '',
        [
            'AREA_FILE_SHOW' => 'page',
            'AREA_FILE_SUFFIX' => 'inc_newdesign',
            'EDIT_TEMPLATE' => '',
        ]
    );
    ?>
</div>

<?php if (!$itemsCnt): ?>
    <p class="nd-sale__empty"><?= GetMessage('SECTION_EMPTY') ?></p>
<?php else: ?>
    <?php
    /* Аякс-подгрузка списка: старый шаблон обрезал буфер, оставляя только
       разметку списка. Поведение сохраняем. */
    $ndIsAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || strtolower($_REQUEST['ajax'] ?? '') === 'y';

    if ($ndIsAjax) {
        $APPLICATION->RestartBuffer();
    }

    include __DIR__.'/page_blocks/list_elements_newdesign.php';

    if ($ndIsAjax) {
        die();
    }
    ?>

    <?php if ($ndHasActivity): ?>
        <div class="nd-sale__tabs">
            <?php foreach ([1 => 'Активные', 0 => 'Завершенные'] as $ndIndex => $ndLabel): ?>
                <?php if ($ndIndex === $ndActivity): ?>
                    <span class="nd-sale__tab nd-sale__tab--active"><?= $ndLabel ?></span>
                <?php else: ?>
                    <a class="nd-sale__tab" href="<?= $APPLICATION->GetCurPageParam('activity='.$ndIndex, ['activity']) ?>"><?= $ndLabel ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
