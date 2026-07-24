<?global $IsCatalogSadMebelPage;?>

<style>
.bold_section {font-weight:700;}
</style>



	<?$SECTION_ID = $arResult['SECTION_ID']; //ID текущего раздела
$sectionParent = CIBlockSection::GetNavChain(false, $SECTION_ID);
    while($arItem = $sectionParent->Fetch()){
        $parentSectionId = $arItem['ID']; // ID родителя (там массив, если больше одного родителя)
    }

?>
<div class="sidebar-ru">
<ul class="ul-sidebar-ru">
<?foreach($arResult["SECTIONS"] as $arItems):?>
<?
$SECTION_ID = 503; //ID текущего раздела
$res = CIBlockSection::GetByID($SECTION_ID);
if($ar_res = $res->GetNext()) {
}
?>

<li>

		
<?if (($arItems["SECTIONS"]) ):?>
		<ul class="ul-sidebar-ru">
		
			
		
		<?if ($_SERVER['REQUEST_URI'] == $ar_res['SECTION_PAGE_URL']):?>
		<span class="bold"><?=$ar_res['NAME'];?></span>
		<?else:?>
		<a href="<?=$ar_res['SECTION_PAGE_URL']?>"><?=$ar_res["NAME"]?></a>
		<? endif; ?>
		
		
	

			<?foreach($arItems["SECTIONS"] as $arItem):?>
			<li class="<?=(($arItem['SECTION_PAGE_URL'] == $APPLICATION->GetCurPage(false)) ? 'active' : '')?> ">
			<a href="<?=$arItem["SECTION_PAGE_URL"]?>" ><?=$arItem["NAME"]?></a>	
			</li>
			<?endforeach;?>
		</ul>
<?endif;?>
</li>

<?endforeach;?>

</ul>
</div>





	 
	 
	


<?file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xx440001x.txt', print_r($ar_res["SECTION_PAGE_URL"], 1));?>

