<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?if (empty($arResult["CATEGORIES"])) return;?>
<?
/**
 * Подсказки поиска в шапке нового дизайна.
 *
 * Вёрстка по макету: Figma «Чистовик», фрейм «Поиск» 20744:47165 (десктоп) и
 * 20744:46533 (мобильный), внутри — «Результаты поиска» 20744:47530. Строка:
 * картинка 54×54, название в две строки (совпавшая часть жирная) и цена под
 * ним; в подвале — ссылка «Все товары» со стрелкой. Размеры и цвета заданы в
 * css/newdesign-header.css, раздел «Подсказки поиска».
 *
 * Классы темы .bx_item_block и .maxwidth-theme убраны: они несут старую сетку
 * на float'ах и мешают собрать строку по макету. Обработчик blur в script.js
 * этого же шаблона был на них завязан — переписан на поиск родителя
 * .title-search-result.
 *
 * А вот .bx_searche на обёртке оставлен намеренно, хотя оформления не даёт:
 * тема вешает на html/body обработчик mousedown (js/main.js, «close search
 * block»), который прячет .title-search-result, если нажали мимо .bx_searche.
 * Без этого класса список исчезал по нажатию клавиши мыши — до отпускания, —
 * и клик по строке или по «Все товары» не срабатывал вовсе.
 *
 * Цену печатаем через «₽»: currency-формат сайта отдаёт «руб», а по макету
 * везде знак рубля — так же поступают newdesign-catalog.js и newdesign-element.js.
 */

$ndRub = function ($price) {
	return preg_replace('/\s*руб\.?/iu', ' ₽', (string)$price);
};

// Ссылку «все результаты» макет ставит в подвал списка, поэтому собираем
// категории заранее, а не печатаем в порядке выдачи компонента.
$ndAllItem = null;
$ndItems = array();
foreach ($arResult["CATEGORIES"] as $category_id => $arCategory) {
	foreach ($arCategory["ITEMS"] as $arItem) {
		if ($category_id === "all") {
			$ndAllItem = $arItem;
		} else {
			$ndItems[] = $arItem;
		}
	}
}

// Режем до TOP_COUNT здесь, а не в подборе: result_modifier ищет с запасом,
// потому что часть найденного он же и выбрасывает — товары чужого региона и
// (по настройке темы) отсутствующие на складе.
$ndTop = (int)$arParams['TOP_COUNT'] > 0 ? (int)$arParams['TOP_COUNT'] : 5;
if (count($ndItems) > $ndTop) {
	$ndItems = array_slice($ndItems, 0, $ndTop);
}

// Если товары нашлись, но их все выбросили те же фильтры, оставалась висеть
// одна строка «Все товары» — пустая рамка вместо подсказок.
if (!$ndItems) {
	return;
}
?>
<div class="nd-sug bx_searche">
	<?foreach ($ndItems as $arItem):?>
		<?$arElement = isset($arResult["ELEMENTS"][$arItem["ITEM_ID"]]) && is_array($arResult["ELEMENTS"][$arItem["ITEM_ID"]])
			? $arResult["ELEMENTS"][$arItem["ITEM_ID"]]
			: null;?>
		<?if ($arElement):?>
			<?
			// Цена строки: у товара с предложениями — минимальная по ним
			// (MIN_PRICE), у товара без предложений — первая доступная из
			// PRICE_CODE (порядок — BASE, OPT).
			$ndRow = null;
			if (isset($arElement["MIN_PRICE"]) && $arElement["MIN_PRICE"]) {
				$ndRow = $arElement["MIN_PRICE"];
			} elseif (!empty($arElement["PRICES"])) {
				foreach ($arElement["PRICES"] as $arPrice) {
					if ($arPrice["CAN_ACCESS"]) {
						$ndRow = $arPrice;
						break;
					}
				}
			}

			// Цена по акции: показываем её, а под ней — старую перечёркнутую и
			// размер скидки, как в карточке товара в разделе каталога
			// (.nd-old-row, newdesign-catalog.js). До 25 сентября 2026 в
			// подсказках стояла одна цена со скидкой — по ней не было видно,
			// что товар подешевел, и в разделе те же кресла выглядели иначе.
			$ndPrice = $ndOldPrice = $ndDiscount = '';
			if ($ndRow) {
				if ($ndRow["DISCOUNT_VALUE"] < $ndRow["VALUE"]) {
					$ndPrice = $ndRow["PRINT_DISCOUNT_VALUE"];
					$ndOldPrice = $ndRow["PRINT_VALUE"];
					// PRINT_DISCOUNT_DIFF кладёт сам Битрикс (GetItemPrices), но
					// у минимальной цены из предложений он местами не доезжает —
					// тогда считаем сами и печатаем в валюте цены.
					if (isset($ndRow["PRINT_DISCOUNT_DIFF"]) && $ndRow["DISCOUNT_DIFF"] > 0) {
						$ndDiscount = $ndRow["PRINT_DISCOUNT_DIFF"];
					} elseif ($ndRow["CURRENCY"] && class_exists('CCurrencyLang')) {
						$ndDiscount = CCurrencyLang::CurrencyFormat($ndRow["VALUE"] - $ndRow["DISCOUNT_VALUE"], $ndRow["CURRENCY"], true);
					}
				} else {
					$ndPrice = $ndRow["PRINT_VALUE"];
				}
			}
			?>
			<a class="nd-sug__item" href="<?=$arItem["URL"]?>">
				<span class="nd-sug__pic">
					<?if (isset($arElement["PICTURE"]) && is_array($arElement["PICTURE"])):?>
						<img src="<?=$arElement["PICTURE"]["src"]?>" alt="" loading="lazy">
					<?else:?>
						<img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_small.png" alt="" width="38" height="38">
					<?endif;?>
				</span>
				<span class="nd-sug__body">
					<span class="nd-sug__name"><?=$arItem["NAME"]?></span>
					<?if ($ndPrice !== ''):?>
						<span class="nd-sug__price"><?=$ndRub($ndPrice)?></span>
					<?endif;?>
					<?if ($ndOldPrice !== ''):?>
						<span class="nd-sug__old-row">
							<span class="nd-sug__old"><?=$ndRub($ndOldPrice)?></span>
							<?if ($ndDiscount !== ''):?>
								<span class="nd-sug__diff">скидка <?=$ndRub($ndDiscount)?></span>
							<?endif;?>
						</span>
					<?endif;?>
				</span>
			</a>
		<?elseif ($arItem["MODULE_ID"]):?>
			<?// Разделы и прочие модули: картинки и цены у них нет.?>
			<a class="nd-sug__item nd-sug__item--plain" href="<?=$arItem["URL"]?>">
				<span class="nd-sug__body">
					<span class="nd-sug__name"><?=$arItem["NAME"]?></span>
				</span>
			</a>
		<?endif;?>
	<?endforeach;?>
	<?if ($ndAllItem):?>
		<?// Подпись в макете — «Все товары», а компонент отдаёт «Все результаты».?>
		<div class="nd-sug__foot">
			<a class="nd-sug__all" href="<?=$ndAllItem["URL"]?>">
				<span>Все товары</span>
				<i class="nd-ico nd-sug__arrow" style="-webkit-mask-image:url('<?=SITE_TEMPLATE_PATH?>/images/newdesign/mobile/chevron.svg');mask-image:url('<?=SITE_TEMPLATE_PATH?>/images/newdesign/mobile/chevron.svg')"></i>
			</a>
		</div>
	<?endif;?>
</div>
