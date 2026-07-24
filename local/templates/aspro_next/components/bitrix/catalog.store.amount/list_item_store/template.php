<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); ?>

<style>
.store-list-dropdown {
    display: none;
    margin-top: 8px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 8px;
    font-size: 13px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.store-list-dropdown.show {
    display: block;
}
.store-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
}
.store-list-item:last-child {
    border-bottom: none;
}
.store-name {
    font-weight: 500;
}
.store-name.region {
    font-weight: bold;
}
.store-amount {
    font-weight: 600;
}
.amount-green { color: #2ca94c; }
.amount-orange { color: #ff9500; }
.order-wait { color: #ff9500; font-size: 12px; }
</style>

<div class="store-list-dropdown" data-product-id="<?=$arParams['ELEMENT_ID']?>">
    <? if(!empty($arResult["STORES"])): ?>
        <? foreach($arResult["STORES"] as $store):
            $amount = (isset($store['REAL_AMOUNT']) ? $store['REAL_AMOUNT'] : $store['AMOUNT']);
            $storeName = trim($store['NAME'] ?: $store['TITLE'] ?: 'Склад');
            if (mb_strlen($storeName) > 35) $storeName = mb_substr($storeName, 0, 32).'...';
            $amountClass = '';
            if ($amount > 0) {
                $amountClass = ($amount >= 30) ? 'amount-green' : 'amount-orange';
                $amountText = (int)$amount.' шт.';
            } else {
                $amountText = '<span class="order-wait">Под заказ</span>';
            }
            $nameClass = ($store['IS_REGION_STORE'] ?? false) ? 'store-name region' : 'store-name';
        ?>
            <div class="store-list-item">
                <div class="<?=$nameClass?>"><?=htmlspecialcharsbx($storeName)?></div>
                <div class="store-amount <?=$amountClass?>"><?=$amountText?></div>
            </div>
        <? endforeach; ?>
    <? else: ?>
        <div class="store-list-item">Нет доступных складов</div>
    <? endif; ?>
</div>