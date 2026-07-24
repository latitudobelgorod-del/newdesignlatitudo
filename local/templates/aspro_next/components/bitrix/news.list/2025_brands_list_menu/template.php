<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>

<?if($arResult["ITEMS"]):?>
    <div class="megamenu-brands-wrapper">
        <div class="megamenu-brands-container">
            <div class="megamenu-brands-grid">
                <?php 
                $displayCount = 16;
                $counter = 0;
                foreach($arResult["ITEMS"] as $arItem):
                    if($counter >= $displayCount) break;
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                    
                    $imageSrc = '';
                    if(is_array($arItem["PREVIEW_PICTURE"])){
                        $imageSrc = $arItem["PREVIEW_PICTURE"]["SRC"];
                    } elseif(is_array($arItem["DETAIL_PICTURE"])){
                        $imageSrc = $arItem["DETAIL_PICTURE"]["SRC"];
                    }
                    ?>
                    <div class="megamenu-brand-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                        <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="megamenu-brand-link">
                            <?php if($imageSrc): ?>
                                <img src="<?=$imageSrc?>" alt="<?=$arItem["NAME"]?>" title="<?=$arItem["NAME"]?>">
                            <?php else: ?>
                                <span class="megamenu-brand-name"><?=$arItem["NAME"]?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php 
                $counter++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
<?endif;?>
<style>
/* Блок брендов */
.megamenu-brands-wrapper {
    background: #fff;
    padding: 8px 24px;
    width: 100%;
}

.megamenu-brands-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Сетка брендов - 16 колонок */
.megamenu-brands-grid {
    display: grid;
    grid-template-columns: repeat(16, 1fr);
    gap: 12px;
}

/* Карточка бренда - фиксированная высота 50px */
.megamenu-brand-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    transition: all 0.2s ease;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.megamenu-brand-item:hover {
    border-color: #b41818;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.megamenu-brand-link {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    text-decoration: none;
    width: 100%;
    height: 100%;
}

.megamenu-brand-link img {
    max-width: 100%;
    max-height: 40px;
    width: auto;
    height: auto;
    object-fit: contain;
    transition: all 0.2s ease;
}

.megamenu-brand-name {
    font-size: 11px;
    font-weight: 500;
    color: #666;
    text-align: center;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-word;
    /* Выравнивание текста по центру вертикально */
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.megamenu-brand-item:hover .megamenu-brand-name {
    color: #b41818;
}

/* Адаптив */
@media (max-width: 1400px) {
    .megamenu-brands-grid {
        grid-template-columns: repeat(12, 1fr);
    }
}

@media (max-width: 1200px) {
    .megamenu-brands-grid {
        grid-template-columns: repeat(10, 1fr);
    }
}

@media (max-width: 992px) {
    .megamenu-brands-grid {
        grid-template-columns: repeat(8, 1fr);
    }
}

@media (max-width: 768px) {
    .megamenu-brands-wrapper {
        padding: 6px 16px;
    }
    
    .megamenu-brands-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 10px;
    }
    
    .megamenu-brand-item {
        height: 45px;
    }
    
    .megamenu-brand-link img {
        max-height: 35px;
    }
}

@media (max-width: 576px) {
    .megamenu-brands-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    
    .megamenu-brand-item {
        height: 40px;
    }
    
    .megamenu-brand-link img {
        max-height: 30px;
    }
}

@media (max-width: 480px) {
    .megamenu-brands-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>