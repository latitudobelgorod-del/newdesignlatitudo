<?
$display = "blockcolors";
$template = "catalog_blockcolors";
?>
<? /* if (!$section['UF_SORT']):*/?>
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
				<?php
				$currentUrl = $APPLICATION->GetCurPageParam('sort='.$value["key"].'&order='.$value['order'], 	array('sort', 'order'));
				$url = str_replace('+', '%2B', $currentUrl);
				$classSort = ($sort == $value['key'] && $sort_order == $value['order']) ? 'current' : '';

                $urlPath = parse_url($url, PHP_URL_PATH);

                if (\Bitrix\Main\Loader::includeModule('sotbit.seometa')) {
                    $newUrl = \Sotbit\Seometa\Orm\SeometaUrlTable::getRow([
                        'filter' => ['=REAL_URL' => $urlPath],
                        'select' => ['NEW_URL'],
                        'cache' => ['ttl' => 300],
                    ])['NEW_URL'];

                    if ($newUrl) {
                        $newUrlPath = $newUrl;
                    }
                }

                $url = str_replace($urlPath, $newUrlPath, $url);
				?>
				<a href="<?php echo $url; ?>" class="sort_btn <?php echo $classSort; ?>" rel="nofollow">
					<span><?php echo $value['title']; ?></span>
				</a>
			<?endforeach; ?>
		<a href="<?=str_replace($urlPath, $newUrlPath, $APPLICATION->GetCurPageParam("", array('sort', 'order')));?>" class="<?=(($sort == "PRICE") ? 'visible' : 'hidden')?>" rel="nofollow">Сбросить</a>
			
		
<?
			if($sort == "PRICE"){
				$sort = 'PROPERTY_MINIMUM_PRICE';
			}
			?>
		
</div>

		<div class="clearfix"></div>
	
</div>

<?/*else:?>

<?endif;*/?>