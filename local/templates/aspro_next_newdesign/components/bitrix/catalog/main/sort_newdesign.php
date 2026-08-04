<?
/**
 * Панель над списком товаров — новый дизайн.
 * Макет: Figma «Категория» 20464:371289 — слева чипы посадочных страниц,
 * справа выпадающий список сортировки.
 *
 * Копия sort.php: логика сортировки (параметры sort/order, подмена адреса
 * через sotbit.seometa, перевод PRICE → PROPERTY_MINIMUM_PRICE) прежняя,
 * поменялась только разметка. Здесь же выбирается шаблон карточек.
 */
$display = "blockcolors";
$template = "catalog_blockcolors_newdesign";

$sort = "sort";
$sort_order = 'asc';

if($_REQUEST["sort"]){
	$sort = ToUpper($_REQUEST["sort"]);
}
if($_REQUEST["order"]){
	$sort_order = $_REQUEST["order"];
}

$sortArr = array(
	'CHEAPER' => array(
		'title' => 'Сначала дешёвые',
		'key' => 'PRICE',
		'order' => 'asc',
	),
	'EXPENSIVE' => array(
		'title' => 'Сначала дорогие',
		'key' => 'PRICE',
		'order' => 'desc',
	),
);

$bSotbit = \Bitrix\Main\Loader::includeModule('sotbit.seometa');

/**
 * Адрес с параметрами сортировки. Если у страницы есть ЧПУ-двойник из
 * sotbit.seometa, подменяем путь — иначе сортировка уводит с посадочной.
 */
$ndSortUrl = function($params) use ($APPLICATION, $bSotbit){
	$url = $APPLICATION->GetCurPageParam($params, array('sort', 'order'));
	$url = str_replace('+', '%2B', $url);
	$urlPath = parse_url($url, PHP_URL_PATH);

	if($bSotbit && $urlPath){
		$row = \Sotbit\Seometa\Orm\SeometaUrlTable::getRow(array(
			'filter' => array('=REAL_URL' => $urlPath),
			'select' => array('NEW_URL'),
			'cache' => array('ttl' => 300),
		));

		if($row && $row['NEW_URL']){
			$url = str_replace($urlPath, $row['NEW_URL'], $url);
		}
	}

	return $url;
};

// текущий выбор — заголовок выпадашки
$ndCurrentSortTitle = 'По популярности';
foreach($sortArr as $value){
	if($sort == $value['key'] && $sort_order == $value['order']){
		$ndCurrentSortTitle = $value['title'];
	}
}

// чипы посадочных страниц собираются в list_elements_1.php — здесь только печатаем
$ndTagsHtml = isset($GLOBALS['ND_CATALOG_TAGS_HTML']) ? trim($GLOBALS['ND_CATALOG_TAGS_HTML']) : '';
?>
<div class="sort_header nd-catlist-sort view_<?=$display?>">
	<div class="nd-catlist-sort__tags"><?=$ndTagsHtml?></div>

	<?/* Кнопка «Фильтры» из мобильного макета. Своей панели фильтра не заводим —
	   жмём штатный триггер темы (.filter_opener), он открывает ту же шторку. */?>
	<span class="nd-catlist-sort__filter" data-nd-filter-opener>Фильтры</span>

	<div class="nd-catlist-sort__select">
		<span class="nd-catlist-sort__current"><?=$ndCurrentSortTitle?></span>
		<div class="nd-catlist-sort__list">
			<a href="<?=$ndSortUrl("")?>" class="<?=($sort !== 'PRICE' ? 'current' : '')?>" rel="nofollow">По популярности</a>
			<?foreach($sortArr as $value):?>
				<a href="<?=$ndSortUrl('sort='.$value["key"].'&order='.$value['order'])?>"
				   class="<?=(($sort == $value['key'] && $sort_order == $value['order']) ? 'current' : '')?>" rel="nofollow"><?=$value['title']?></a>
			<?endforeach;?>
		</div>
	</div>
</div>
<?
if($sort == "PRICE"){
	$sort = 'PROPERTY_MINIMUM_PRICE';
}
?>
