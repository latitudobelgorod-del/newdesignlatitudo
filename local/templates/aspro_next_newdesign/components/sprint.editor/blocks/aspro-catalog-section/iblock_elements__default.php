<?/** @var $block array */?><?
$elements = Sprint\Editor\Blocks\IblockElements::getList($block, array(
    'NAME',
    'DETAIL_PAGE_URL'
));
?><div class="sp-iblock-elements">
    <?foreach ($elements as $aItem):?>
        <div class="elements">
            <i class="fa fa-arrow-circle-right link"> </i> <a href="<?=$aItem['DETAIL_PAGE_URL']?>"><?=$aItem['NAME']?></a> <br/>
        </div>
    <?endforeach;?>
</div>