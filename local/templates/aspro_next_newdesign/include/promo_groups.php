<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Группы распродажи считаем только среди товаров текущей акции.
$ndPromoGroups = [];
$ndPromoCounts = [];
$ndPromoIblock = (int)\Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19);
$ndPromoEnums = CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], [
    'IBLOCK_ID' => $ndPromoIblock, 'CODE' => 'ND_SALE_GROUP',
]);
while ($row = $ndPromoEnums->Fetch()) {
    $ndPromoGroups[(int)$row['ID']] = $row['VALUE'];
}
if ($ndPromoGroups) {
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
?>
<?if($ndPromoGroups):?>
<div class="nd-promo-tabs nd-promo-groups" data-nd-promo-groups="<?=(int)$arResult['ID']?>" role="group" aria-label="Группы товаров по акции" hidden>
    <button type="button" class="nd-promo-tabs__tab is-active" data-nd-promo-group="0" aria-pressed="true">Все товары <span class="nd-promo-tabs__cnt"><?=count($ndSaleGoods)?></span></button>
    <?foreach($ndPromoGroups as $id => $name):?>
    <button type="button" class="nd-promo-tabs__tab" data-nd-promo-group="<?=$id?>" aria-pressed="false"><?=htmlspecialcharsbx($name)?> <span class="nd-promo-tabs__cnt"><?=$ndPromoCounts[$id]?></span></button>
    <?endforeach;?>
</div>
<?endif;?>
