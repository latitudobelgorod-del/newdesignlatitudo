<?global $IsCatalogFasadPage,$IsCatalogSadMebelPage;?>

<style>
.bold_section {font-weight:700;}
</style>


<div class="sidebar-ru">
<ul class="ul-sidebar-ru">
<?foreach($arResult["SECTIONS"] as $arItems):?>
<?
$SECTION_ID = 83; //ID текущего раздела
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

<?/* отладочный дамп, отключён */ //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xx44000004445x.txt', print_r($arResult["SECTIONS"], 1));?>

