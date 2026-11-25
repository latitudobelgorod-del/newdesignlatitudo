<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * Группы «Распродажи» на странице акции — те же чипы, что в разделе каталога
 * (components/bitrix/catalog/main/include/sale_groups_newdesign.php):
 * .section_tag_top .tag_ank, лишние прячет collapseTags() с кнопкой «Ещё N».
 *
 * Ирина, 25.11.2026: «полосу прокрутки делать не нужно, сделай как в разделе
 * каталога — показать ещё». Поэтому никакой горизонтальной прокрутки: на
 * компьютере чипы идут в одну строку с переносом, на телефоне две строки и «Ещё».
 *
 * Ссылки «?group=…» работают и без скрипта: фильтр применяется на сервере
 * (news.detail/news_newdesign/template.php), скрипт лишь подменяет список без
 * перезагрузки. Группа — свойство ND_SALE_GROUP (список), значения считаем
 * только среди товаров текущей акции.
 */
$ndPromoGroups = [];
$ndPromoCounts = [];
$ndPromoIblock = (int)\Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19);
$ndPromoEnums = CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], [
    'IBLOCK_ID' => $ndPromoIblock, 'CODE' => 'ND_SALE_GROUP',
]);
while ($row = $ndPromoEnums->Fetch()) {
    $ndPromoGroups[(int)$row['ID']] = $row['VALUE'];
}
if ($ndPromoGroups && !empty($ndSaleGoods)) {
    $rows = CIBlockElement::GetList([], [
        'IBLOCK_ID' => $ndPromoIblock, 'ID' => $ndSaleGoods, 'ACTIVE' => 'Y',
    ], ['PROPERTY_ND_SALE_GROUP']);
    while ($row = $rows->Fetch()) {
        $ndPromoCounts[(int)$row['PROPERTY_ND_SALE_GROUP_ENUM_ID']] = (int)$row['CNT'];
    }
    $ndPromoGroups = array_filter($ndPromoGroups, function ($id) use ($ndPromoCounts) {
        return !empty($ndPromoCounts[$id]);
    }, ARRAY_FILTER_USE_KEY);
}
$ndPromoTotal = (int)($ndPromoTotal ?? count((array)$ndSaleGoods));
$ndPromoCur = (int)($_GET['group'] ?? 0);
if (!isset($ndPromoGroups[$ndPromoCur])) {
    $ndPromoCur = 0;
}
$ndPromoUrl = $APPLICATION->GetCurPage(false);
?>
<?if($ndPromoGroups):?>
<div class="nd-catlist-sort__tags"><div class="section_tag_top nd-promo-groups" data-nd-promo-groups="<?=(int)$arResult['ID']?>" role="group" aria-label="Группы товаров по акции" hidden>
	<?/* Порядок как на остальных акциях: категории сначала, «Все товары» последней
	   (Ирина, 25.11.2026). Внешний вид — крупные вкладки с красной активной,
	   стили .nd-promo-groups в newdesign-catalog.css. */?>
	<?foreach($ndPromoGroups as $ndPromoId => $ndPromoName):?>
	<span class="tag_ank"><a href="<?=htmlspecialcharsbx($ndPromoUrl)?>" data-nd-promo-group="<?=(int)$ndPromoId?>" rel="nofollow"<?=($ndPromoCur === $ndPromoId ? ' class="active" aria-current="true"' : '')?>><?=htmlspecialcharsbx($ndPromoName)?> <span class="nd-sale-groups__cnt"><?=(int)$ndPromoCounts[$ndPromoId]?></span></a></span>
	<?endforeach;?>
	<span class="tag_ank"><a href="<?=htmlspecialcharsbx($ndPromoUrl)?>" data-nd-promo-group="0"<?=($ndPromoCur === 0 ? ' class="active" aria-current="true"' : '')?>>Все товары <span class="nd-sale-groups__cnt"><?=$ndPromoTotal?></span></a></span>
</div></div>
<?endif;?>
