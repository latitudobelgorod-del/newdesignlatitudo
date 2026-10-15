<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */
/** @var CMain $APPLICATION */

/* Все вкладки печатаются сразу, переключает их script.js без перезагрузки
   (неактивные панели — за краем экрана, а не display:none: плитки и лента
   меряют ширину при запуске).

   Таблица — как у ДНС (Ирина, 15.10.2026): без колонки названий слева,
   название свойства строкой, под ним значения товаров. Так товарам достаётся
   вся ширина — по четыре колонки на компьютере, по две на телефоне.

   Верхняя строка — обычные плитки списка (catalog_blockcolors_newdesign
   через include/brand_products.php): цена в единицах, количество, «В корзину»,
   наличие — как в разделе. Крестик удаления ставит script.js. */

$tabs = $arResult['TABS'];
$active = $arResult['ACTIVE'];

$ndRelatedNav = '<div class="nd-related__nav">'
	.'<span class="nd-related__counter"></span>'
	.'<button class="nd-related__arrow nd-related__arrow--prev" type="button" aria-label="Предыдущие товары">'
	.'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
	.'<button class="nd-related__arrow nd-related__arrow--next" type="button" aria-label="Следующие товары">'
	.'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
	.'</div>';
?>
<div class="nd-cmp" id="nd-cmp" data-tab="<?=htmlspecialcharsbx($active)?>">

<?if (!$tabs):?>
	<div class="nd-cmp__empty">
		<p>В сравнении пока нет товаров. Нажмите «Сравнить» на карточке товара или в списке.</p>
		<a class="nd-btn nd-btn--red" href="/catalog/">Перейти в каталог</a>
	</div>
<?else:?>

	<nav class="nd-cmp__tabs" role="tablist">
		<?foreach ($tabs as $key => $t):?>
			<a class="nd-cmp__tab<?=($key === $active ? ' is-active' : '')?>" href="?tab=<?=urlencode($key)?>" role="tab"
				data-nd-cmp-tab="<?=htmlspecialcharsbx($key)?>" aria-selected="<?=($key === $active ? 'true' : 'false')?>">
				<?=htmlspecialcharsbx($t['TITLE'])?> <span class="nd-cmp__tab-count">(<?=count($t['IDS'])?>)</span>
			</a>
		<?endforeach;?>
	</nav>

	<?foreach ($tabs as $key => $tab):
		$panel = $arResult['PANELS'][$key];
		$items = $panel['ITEMS'];
		$count = count($items);
		// пустой слот «Добавить товар», пока товаров меньше четырёх
		$slots = max(4, $count + ($count < 4 ? 1 : 0));
		?>
		<section class="nd-cmp__panel<?=($key === $active ? ' is-active' : '')?>" data-nd-cmp-panel="<?=htmlspecialcharsbx($key)?>" role="tabpanel">

		<?if (!$count):?>
			<div class="nd-cmp__empty">
				<p>В этой вкладке нет товаров.</p>
				<a class="nd-btn nd-btn--red" href="<?=htmlspecialcharsbx($tab['URL'])?>">Перейти в раздел</a>
			</div>
		<?else:?>

			<div class="nd-cmp__bar">
				<label class="nd-cmp__diff">
					<input type="checkbox" data-nd-cmp-diff<?=($count >= 3 ? ' checked' : '')?>>
					<span>Показывать только различия</span>
				</label>
				<button type="button" class="nd-cmp__clear" data-nd-cmp-clear="<?=htmlspecialcharsbx($key)?>">Очистить список</button>
			</div>

			<?/* Липкая шапка: название, цена и «В корзину» каждого товара — когда
			     плитки ушли за верх экрана. Кнопка жмёт «В корзину» плитки
			     с выбранным там количеством. */?>
			<div class="nd-cmp__sticky" data-nd-cmp-sticky aria-hidden="true">
				<div class="nd-cmp__sticky-in" style="--nd-cmp-slots: <?=$slots?>">
					<?foreach ($items as $id => $item):?>
						<div class="nd-cmp__sticky-cell">
							<a class="nd-cmp__sticky-name" href="<?=$item['URL']?>"><?=htmlspecialcharsbx($item['NAME'])?></a>
							<div class="nd-cmp__sticky-row">
								<span class="nd-cmp__sticky-price"><?=($item['PRICE'] !== null ? number_format($item['PRICE'], 0, ',', ' ').' ₽/'.$panel['UNIT'] : '')?></span>
								<button type="button" class="nd-cmp__sticky-buy" data-nd-cmp-buy="<?=$id?>">В корзину</button>
							</div>
						</div>
					<?endforeach;?>
				</div>
			</div>

			<div class="nd-cmp__scroll" data-nd-cmp-scroll>
				<div class="nd-cmp__grid" style="--nd-cmp-slots: <?=$slots?>">

					<div class="nd-cmp__row nd-cmp__row--head">
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

					<?foreach ($panel['ROWS'] as $row):?>
						<div class="nd-cmp__row<?=($row['SAME'] && $count > 1 ? ' is-same' : '')?> nd-cmp__row--<?=$row['KIND']?>">
							<div class="nd-cmp__lbl"><span><?=htmlspecialcharsbx($row['LABEL'])?></span></div>
							<?foreach ($row['CELLS'] as $cell):?>
								<div class="nd-cmp__cell<?=($cell['BEST'] ? ' is-best' : '')?>"><?=($cell['HTML'] !== '' ? $cell['HTML'] : '—')?></div>
							<?endforeach;?>
							<?for ($i = $count; $i < $slots; $i++):?>
								<div class="nd-cmp__cell nd-cmp__cell--slot"></div>
							<?endfor;?>
						</div>
					<?endforeach;?>

					<div class="nd-cmp__row nd-cmp__row--links">
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

			<?/* «С этим покупают» — лентой со стрелками, как «Может пригодиться»
			     в корзине: та же разметка .nd-related, листает js/newdesign-catalog.js. */?>
			<?if ($panel['EXPANDABLES']):?>
				<div class="nd-related nd-related--compare">
					<div class="nd-related__head">
						<h2 class="nd-related__title">С этим покупают</h2>
						<?=$ndRelatedNav?>
					</div>
					<div class="nd-related__body">
						<?
						$ldBrand = [
							'MODE' => 'flat',
							'FILTER' => ['ID' => $panel['EXPANDABLES'], 'ACTIVE' => 'Y'],
							'ID_ORDER' => $panel['EXPANDABLES'],
							'PER_SECTION' => count($panel['EXPANDABLES']),
							'TITLE' => '',
						];
						include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';
						?>
					</div>
				</div>
			<?endif;?>

		<?endif;?>
		</section>
	<?endforeach;?>

<?endif;?>
</div>
