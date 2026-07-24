<?$arDisplays = array("block", "list", "table", "block1604","blockcolors");
if(array_key_exists("display", $_REQUEST) || (array_key_exists("display", $_SESSION)) || $arParams["DEFAULT_LIST_TEMPLATE"]){
	if($_REQUEST["display"] && (in_array(trim($_REQUEST["display"]), $arDisplays))){
		$display = trim($_REQUEST["display"]);
		$_SESSION["display"]=trim($_REQUEST["display"]);
	}
	elseif($_SESSION["display"] && (in_array(trim($_SESSION["display"]), $arDisplays))){
		$display = $_SESSION["display"];
	}
	elseif($arSection["DISPLAY"]){
		$display = $arSection["DISPLAY"];
	}
	else{
		$display = $arParams["DEFAULT_LIST_TEMPLATE"];
	}
}
else{
	$display = "block";
}
$template = "catalog_".$display;
?>
<?  if (!$section['UF_SORT']):?>
<div class="sort_header view_<?=$display?>">
<div class="sort_filter">
		<?	$sort = "sort";
		$sort_order = 'asc';

		// установка сортировки из параметров запроса
		if($_REQUEST["sort"]) {
			$sort = ToUpper($_REQUEST["sort"]);
		}
		if($_REQUEST["order"]){
			$sort_order = $_REQUEST["order"];
		}

		// массив с ссылками для сортировки
		$sortArr = [
			'CHEAPER' => [
				'title' => 'По возрастанию цены',
				'key' => 'PRICE',
				'order' => 'asc'
			],
			'EXPENSIVE' => [
				'title' => 'По убыванию цены',
				'key' => 'PRICE',
				'order' => 'desc'
			]
		];
?>
	<div style="display:inline-block;">Сортировать:</div>
			<?foreach ($sortArr as $key => $value): ?>
				<?$currentUrl = $APPLICATION->GetCurPageParam('sort='.$value["key"].'&order='.$value['order'], 	array('sort', 'order'));
				$url = str_replace('+', '%2B', $currentUrl);
				$classSort = ($sort == $value['key'] && $sort_order == $value['order']) ? 'current' : '';
				?>
				<a href="<?php echo $url; ?>" class="sort_btn <?php echo $classSort; ?>" rel="nofollow">
					<span><?php echo $value['title']; ?></span>
				</a>
			<?endforeach; ?>
			<a href="<?=$APPLICATION->GetCurPageParam("", array('sort', 'order'));?>" class="<?=(($sort == "PRICE") ? 'visible' : 'hidden')?>" rel="nofollow">Сбросить</a>	
			
			<?if($sort == "PRICE"){
						$sort = 'PROPERTY_MINIMUM_PRICE';
			}
			?>
		
</div>
<div class="clearfix"></div>
</div>
<?else:?>
<?endif;?>