<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>

<?if(!empty($arResult['MAIN_SECTIONS'])):?>
<div class="top_wrapper row margin0 <?=($arParams["SHOW_UNABLE_SKU_PROPS"] != "N" ? "show_un_props" : "unshow_un_props");?>">
    <div id="portfolio_loader">			
        <div class="items ankor_sect">
            <div class="wrap">
                <div class="clearfix"> 	
                    <?foreach($arResult['MAIN_SECTIONS'] as $section): ?>
                        <div class="item">
                            <a class="some_link" href="#<?=$section['CODE']?>">
                                <?=$section['NAME']?>
                            </a>
                        </div>
                    <?endforeach;?>	
                </div>
            </div>
        </div>
    </div>
</div>
<?endif;?>