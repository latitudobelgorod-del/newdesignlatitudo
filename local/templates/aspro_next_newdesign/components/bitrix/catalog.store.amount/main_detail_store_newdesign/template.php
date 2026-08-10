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
/* Блок наличия в новом дизайне — по макету Figma «Карточка товара» 20475:75842
   (заголовок Frame 2087326762, ряд чипов Frame 2087326771).
   Чипы шириной по содержимому и в одну строку с зазором 4, а НЕ сеткой из
   четырёх равных колонок: в макете «Москва» 98, «Краснодар» 102,
   «Ростов-на-Дону» 144 — ширина считается от названия города. */
.stores-simple-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin: 8px 0 0;
}

.store-item {
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    min-height: 47px;
    padding: 4px 8px;
    border-radius: 6px;
}

/* Заливки — тот же цвет с прозрачностью 10%, как в макете, а не подобранный
   светлый оттенок: на белой карточке совпадает, но не «плывёт» на другом фоне. */
.store-item-green {
    background: rgba(52, 199, 135, .1);
}

.store-item-orange {
    background: rgba(255, 149, 0, .1);
}

.store-item-gray {
    background: rgba(82, 82, 100, .1);
}

/* Название города — обычный вес, количество — 500 и цветом по остатку. */
.store-name {
    font-size: 14px;
    line-height: 19.6px;
    font-weight: 400;
    color: #101014;
}

.store-amount {
    font-size: 14px;
    line-height: 19.6px;
    font-weight: 500;
}

.store-item-green .store-amount {
    color: #2CA973;
}
.store-item-orange .store-amount {
    color: #D97400;
}
.store-item-gray .store-amount,
.store-item-gray .order-wait {
    color: #101014;
    font-weight: 500;
}

.title-stock {
    margin-top: 20px;
    font-size: 18px;
    line-height: 21.6px;
    color: #2CA973;
    font-weight: 700;
}

hr {
    margin: 20px 0;
    border: none;
    border-top: 1px solid #e0e0e0;
}

/* Планшетного правила про 2 колонки больше нет: раскладка на flex-wrap,
   чипы переносятся сами по ширине контейнера. */

/* Мобильные устройства (ширина до 768px) - вертикальный список */
@media (max-width: 768px) {
    .stores-simple-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 12px 0;
    }
    
    /* Мобильного макета для этого блока нет — оставлено прежнее поведение
       (город и количество строкой во всю ширину), радиус приведён к 6. */
    .store-item {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-radius: 6px;
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