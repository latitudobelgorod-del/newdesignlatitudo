<? 
/*
$db_list = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>19, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', "ID"=>$arResult["ID"]), true,$arSelect=Array("UF_LINKSEO")); 
 while($ar_result = $db_list->GetNext()):   
?> 
<?foreach($ar_result["UF_LINKSEO"] as $ANKOR):?> 
 <?=$ANKOR ?> 
<?endforeach?> 
<?endwhile*/?>


<? 
/*
$db_list = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>19, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', "ID"=>$arResult["ID"]), true,$arSelect=Array("UF_LINKSEO")); 
 while($ar_result = $db_list->GetNext()):   
?> 
<?foreach($ar_result["UF_LINKSEO"] as $ankor):?> 
 <?= html_entity_decode($ankor) ?>
<?endforeach?> 
<?endwhile*/?>

<style>
.bold_section {font-weight:700;}
</style>



<div class="sidebar-ru">
	<ul class="ul-sidebar-ru">
	
	<?foreach($arResult["SECTIONS"] as $arItems):?>
	
	 <li >
 	<?if($arItems['SECTION_PAGE_URL'] == $APPLICATION->GetCurPage(false)):?>
	
		
	<span class="<?=((($arItems["ID"] == 98)) ? 'bold' : '')?>  "><?=$arItems["NAME"]?></span>
	
		<?else:?>
		<a href="<?=$arItems["SECTION_PAGE_URL"]?>"><?=$arItems["NAME"]?></a> 
			
<?$fSections = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>19, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', "ID"=>$arItems["ID"]), true,$arSelect=Array("UF_LINKSEO"));
 while($ar_result = $fSections->GetNext()):   
?> 
<?foreach($ar_result["UF_LINKSEO"] as $ankor):?> 
<div class="pl-18" ><?= html_entity_decode($ankor) ?></div>
<?endforeach?> 
<?endwhile?>

<? endif; ?>

<?if (($arItems["SECTIONS"]) && ($arItems["ID"] != 104)):?>
		<ul class="ul-sidebar-ru">
			<?foreach($arItems["SECTIONS"] as $arItem):?>
			<li class="<?=(($arItem['SECTION_PAGE_URL'] == $APPLICATION->GetCurPage(false)) ? 'active' : '')?> ">
			<a href="<?=$arItem["SECTION_PAGE_URL"]?>" ><?=$arItem["NAME"]?></a>
			<?$fSections = CIBlockSection::GetList(Array($by=>$order), $arFilter = Array("IBLOCK_ID"=>19, "ID"=>$arItem["ID"]), true,$arSelect=Array("UF_LINKSEO"));
		 while($ar_result = $fSections->GetNext()):   
		?> 
		<?foreach($ar_result["UF_LINKSEO"] as $ankor):?> 
		 <div class="pl-18"><?= html_entity_decode($ankor) ?></div>
		<?endforeach?> 
		<?endwhile?>
			
			</li>
			<?endforeach;?>
		</ul>
<?endif;?>
</li>

<?endforeach;?>
	
</ul>

</div>