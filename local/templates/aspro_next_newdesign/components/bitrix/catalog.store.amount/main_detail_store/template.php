<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// Проверяем, есть ли хотя бы один склад с остатком > 0
$hasPositiveAmount = false;
if (!empty($arResult["STORES"]) && is_array($arResult["STORES"])) {
    foreach($arResult["STORES"] as $arStore){
        $amount = isset($arStore['REAL_AMOUNT']) ? $arStore['REAL_AMOUNT'] : $arStore['AMOUNT'];
        if($amount > 0){
            $hasPositiveAmount = true;
            break;
        }
    }
}
if(!$hasPositiveAmount) return; // нет положительных остатков — ничего не выводим
?>

<style>
/* Базовые стили - для десктопа (4 колонки) */
.stores-simple-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin: 5px 0;
}

.store-item {
    border-radius: 4px;
    padding: 6px 12px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: auto;
}

.store-item-green {
    background: #ebfaef;
}

.store-item-orange {
    background: #fff5e6;
}

.store-item-gray {
    background: #eeeef0;
}

.store-item-green .store-amount {
    color: #2ca94c;
}
.store-item-orange .store-amount {
    color: #ff9500;
}
.store-item-gray .store-amount {
    color: #999;
}

.store-item-gray .order-wait {
    color: #999;
    font-weight: normal;
}

.title-stock {
    margin-top: 20px;
    font-size: 18px;
    line-height: 1.2;
    color: #2ca94c;
    font-weight: bold;
}

hr {
    margin: 20px 0;
    border: none;
    border-top: 1px solid #e0e0e0;
}

/* Планшеты (ширина до 992px) - 2 колонки */
@media (max-width: 992px) {
    .stores-simple-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Мобильные устройства (ширина до 768px) - вертикальный список */
@media (max-width: 768px) {
    .stores-simple-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 12px 0;
    }
    
    .store-item {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-radius: 8px;
    }
    
    .store-name {
        margin-bottom: 0;
        font-size: 14px;
        flex: 1;
        margin-right: 12px;
    }
    
    .store-amount {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .title-stock {
        font-size: 16px;
        margin-bottom: 12px;
    }
}

/* Очень маленькие телефоны (ширина до 480px) */
@media (max-width: 480px) {
    .store-item {
        padding: 10px 12px;
    }
    
    .store-name {
        font-size: 13px;
    }
    
    .store-amount {
        font-size: 13px;
    }
    
    .title-stock {
        font-size: 15px;
        margin-bottom: 10px;
    }
}
</style>

<? if(!empty($arResult["STORES"]) && is_array($arResult["STORES"])): ?>
    <div class="title-stock">
        <?=GetMessage("IN_STOCK_TITLE")?>
    </div>
    <div class="stores-simple-list">
        <? foreach($arResult["STORES"] as $arStore):
            $amount = isset($arStore['REAL_AMOUNT']) ? $arStore['REAL_AMOUNT'] : $arStore['AMOUNT'];
            
            // Формируем чистое название склада (без скобок и запятых)
            $storeName = '';
            if (!empty($arStore["NAME"])) {
                $storeName = trim($arStore["NAME"]);
            } elseif (!empty($arStore["TITLE"])) {
                $storeName = trim($arStore["TITLE"]);
            }
            // Удаляем всё после '('
            $pos = strpos($storeName, '(');
            if ($pos !== false) {
                $storeName = trim(substr($storeName, 0, $pos));
            }
            // Удаляем всё после ','
            $pos = strpos($storeName, ',');
            if ($pos !== false) {
                $storeName = trim(substr($storeName, 0, $pos));
            }
            if (empty($storeName)) {
                $storeName = GetMessage("STORE_NAME");
            }
            
            // Определяем класс по количеству
            if ($amount >= 30) {
                $itemClass = 'store-item-green';
            } elseif ($amount > 0 && $amount < 30) {
                $itemClass = 'store-item-orange';
            } else {
                $itemClass = 'store-item-gray';
            }
        ?>
            <div class="store-item <?=$itemClass?>">
                <div class="store-name">
                    <?=htmlspecialcharsbx($storeName)?>
                </div>
                <div class="store-amount">
                    <? if($amount > 0): ?>
                        <?=(int)$amount?> <?=GetMessage("PIECES")?>
                    <? else: ?>
                        <span class="order-wait"><?=GetMessage("ON_ORDER")?></span>
                    <? endif; ?>
                </div>
            </div>
        <? endforeach; ?>
    </div>
<? else: ?>
    <div class="stores-empty-message"><?=GetMessage("NO_STORES")?></div>
<? endif; ?>