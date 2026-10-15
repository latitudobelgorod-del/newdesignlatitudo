<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */
/** @var CMain $APPLICATION */

/* Верхняя строка таблицы — обычные плитки списка (catalog_blockcolors_newdesign
   через include/brand_products.php, как бренд и акции): цена в единицах,
   количество, «В корзину», наличие по городам — ровно как в разделе. Плитки
   стоят в первой строке таблицы; крестик удаления к каждой добавляет script.js. */

$tabs = $arResult['TABS'];
$active = $arResult['ACTIVE'];
$items = $arResult['ITEMS'];
$count = count($items);
$tab = $tabs[$active] ?? null;
// пустой слот «Добавить товар», пока товаров меньше четырёх
$slots = max(4, $count + ($count < 4 ? 1 : 0));
$diffOnly = $count >= 3;
?>
<div class="nd-cmp" id="nd-cmp" data-tab="<?=htmlspecialcharsbx($active)?>" data-count="<?=$count?>">

<?if (!$tabs):?>
	<div class="nd-cmp__empty">
		<p>В сравнении пока нет товаров. Нажмите «Сравнить» на карточке товара или в списке.</p>
		<a class="nd-btn nd-btn--red" href="/catalog/">Перейти в каталог</a>
	</div>
<?else:?>

	<?if (count($tabs) > 1):?>
		<nav class="nd-cmp__tabs">
			<?foreach ($tabs as $key => $t):?>
				<a class="nd-cmp__tab<?=($key === $active ? ' is-active' : '')?>" href="?tab=<?=urlencode($key)?>">
					<?=htmlspecialcharsbx($t['TITLE'])?> <span class="nd-cmp__tab-count">(<?=count($t['IDS'])?>)</span>
				</a>
			<?endforeach;?>
		</nav>
	<?endif;?>

	<?if (!$count):?>
		<div class="nd-cmp__empty">
			<p>В этой вкладке нет товаров.</p>
			<a class="nd-btn nd-btn--red" href="<?=htmlspecialcharsbx($tab['URL'] ?? '/catalog/')?>">Перейти в раздел</a>
		</div>
	<?else:?>

	<div class="nd-cmp__bar">
		<label class="nd-cmp__diff">
			<input type="checkbox" data-nd-cmp-diff<?=($diffOnly ? ' checked' : '')?>>
			<span>Показывать только различия</span>
		</label>
		<button type="button" class="nd-cmp__clear" data-nd-cmp-clear="<?=htmlspecialcharsbx($active)?>">Очистить список</button>
	</div>

	<?/* Липкая шапка: фото, название, цена и «В корзину» каждого товара —
	     появляется, когда плитки ушли за верх экрана. Кнопка жмёт «В корзину»
	     плитки с выбранным там количеством. */?>
	<div class="nd-cmp__sticky" data-nd-cmp-sticky aria-hidden="true">
		<div class="nd-cmp__sticky-in" style="--nd-cmp-slots: <?=$slots?>">
			<div class="nd-cmp__lbl"></div>
			<?foreach ($items as $id => $item):?>
				<div class="nd-cmp__sticky-cell" data-id="<?=$id?>">
					<a class="nd-cmp__sticky-name" href="<?=$item['URL']?>"><?=htmlspecialcharsbx($item['NAME'])?></a>
					<div class="nd-cmp__sticky-row">
						<span class="nd-cmp__sticky-price"><?=($item['PRICE'] !== null ? number_format($item['PRICE'], 0, ',', ' ').' ₽/'.$arResult['UNIT'] : '')?></span>
						<button type="button" class="nd-cmp__sticky-buy" data-nd-cmp-buy="<?=$id?>">В корзину</button>
					</div>
				</div>
			<?endforeach;?>
		</div>
	</div>

	<div class="nd-cmp__scroll" data-nd-cmp-scroll>
		<div class="nd-cmp__grid" style="--nd-cmp-slots: <?=$slots?>">

			<?/* Плитки печатаются прямо здесь, в своём контейнере списка: перенос
			     по колонкам ломал их стили и галерею (размеры считались, пока блок
			     был скрыт). Контейнер растянут на колонки товаров, а внутри него
			     стили делают ту же сетку, что у строк таблицы. */?>
			<div class="nd-cmp__row nd-cmp__row--head">
				<div class="nd-cmp__lbl nd-cmp__lbl--head">
					<?=$count?> <?=($count === 1 ? 'товар' : ($count < 5 ? 'товара' : 'товаров'))?>
				</div>
				<div class="nd-cmp__tiles" data-nd-cmp-tiles style="grid-column: span <?=$count?>; --nd-cmp-n: <?=$count?>">
					<?
					$ldBrand = [
						'MODE' => 'flat',
						'FILTER' => ['ID' => array_keys($items)],
						'ID_ORDER' => array_keys($items),
						'PER_SECTION' => $count,
						'TITLE' => '',
					];
					include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';
					?>
				</div>
				<?for ($i = $count; $i < $slots; $i++):?>
					<div class="nd-cmp__cell nd-cmp__cell--slot">
						<?if ($i === $count):?>
							<a class="nd-cmp__add" href="<?=htmlspecialcharsbx($tab['URL'])?>">
								<span class="nd-cmp__add-plus">+</span>
								<span>Добавить товар</span>
							</a>
						<?endif;?>
					</div>
				<?endfor;?>
			</div>

			<?foreach ($arResult['ROWS'] as $row):?>
				<div class="nd-cmp__row<?=($row['SAME'] && $count > 1 ? ' is-same' : '')?> nd-cmp__row--<?=$row['KIND']?>">
					<div class="nd-cmp__lbl"><?=htmlspecialcharsbx($row['LABEL'])?></div>
					<?foreach ($row['CELLS'] as $cell):?>
						<div class="nd-cmp__cell<?=($cell['BEST'] ? ' is-best' : '')?>"><?=($cell['HTML'] !== '' ? $cell['HTML'] : '—')?></div>
					<?endforeach;?>
					<?for ($i = $count; $i < $slots; $i++):?>
						<div class="nd-cmp__cell nd-cmp__cell--slot"></div>
					<?endfor;?>
				</div>
			<?endforeach;?>

			<div class="nd-cmp__row nd-cmp__row--links">
				<div class="nd-cmp__lbl"></div>
				<?foreach ($items as $id => $item):?>
					<div class="nd-cmp__cell"><a class="nd-cmp__more" href="<?=$item['URL']?>">Подробнее о товаре</a></div>
				<?endforeach;?>
				<?for ($i = $count; $i < $slots; $i++):?>
					<div class="nd-cmp__cell nd-cmp__cell--slot"></div>
				<?endfor;?>
			</div>
		</div>
	</div>

	<?/* Заявка менеджеру — штатная «Общая форма» темы (MAINFORM), как кнопка
	     в карточке. Список товаров script.js подставляет в поле вопроса —
	     покупатель может его дописать. */
	$ndList = [];
	foreach ($items as $item) {
		$ndList[] = $item['NAME'];
	}
	?>
	<div class="nd-cmp__manager">
		<span class="btn btn-default btn-lg type_block transition_bg one_click nd-cmp__calc" data-event="jqm" data-param-form_id="MAINFORM" data-name="question"
			data-nd-form-title="Рассчитать вместе с менеджером"
			data-nd-cmp-list="<?=htmlspecialcharsbx('Сравниваю: '.implode('; ', $ndList))?>">
			<span>Рассчитать вместе с менеджером</span>
		</span>
		<p class="nd-cmp__manager-note">Менеджер получит список сравниваемых товаров и поможет посчитать количество и доставку.</p>
	</div>

	<?if ($arResult['EXPANDABLES']):?>
		<section class="nd-cmp__also">
			<h2 class="nd-cmp__h2">С этим покупают</h2>
			<?
			$ldBrand = [
				'MODE' => 'flat',
				'FILTER' => ['ID' => $arResult['EXPANDABLES'], 'ACTIVE' => 'Y'],
				'ID_ORDER' => $arResult['EXPANDABLES'],
				'PER_SECTION' => count($arResult['EXPANDABLES']),
				'TITLE' => '',
			];
			include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';
			?>
		</section>
	<?endif;?>

	<?endif;?>
<?endif;?>
</div>
