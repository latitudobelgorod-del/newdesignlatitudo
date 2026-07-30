<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Выпадающий каталог под кнопкой «Каталог» в шапке нового дизайна.
 *
 * Разделы берёт не из меню, а прямо из инфоблока каталога — это делает
 * result_modifier.php (перенесён с easydecking.ru без изменений).
 * Стили — в css/newdesign-header.css, префикс .nd-cat.
 */
?>
<?if(!empty($arResult)):?>
	<?// Разделы приходят сырыми из инфоблока (result_modifier.php), а не из
	// arResult меню, — компонент их не экранирует, делаем это сами.?>
	<div class="nd-cat">
		<div class="nd-cat__cols">
			<?foreach($arResult as $arItem):?>
				<div class="nd-cat__group">
					<a class="nd-cat__parent" href="<?=htmlspecialcharsbx($arItem['SECTION_PAGE_URL'])?>"><?=htmlspecialcharsbx($arItem['NAME'])?></a>
					<?if($arItem['CHILD']):?>
						<ul class="nd-cat__children">
							<?foreach($arItem['CHILD'] as $arChild):?>
								<li>
									<a class="nd-cat__child<?=($arChild['SELECTED'] ? ' is-active' : '')?>" href="<?=htmlspecialcharsbx($arChild['SECTION_PAGE_URL'])?>"><?=htmlspecialcharsbx($arChild['NAME'])?></a>
									<?if($arChild['CHILD']):?>
										<ul class="nd-cat__sub">
											<?foreach($arChild['CHILD'] as $arSub):?>
												<li>
													<a class="nd-cat__sub-link<?=($arSub['SELECTED'] ? ' is-active' : '')?>" href="<?=htmlspecialcharsbx($arSub['SECTION_PAGE_URL'])?>"><?=htmlspecialcharsbx($arSub['NAME'])?></a>
												</li>
											<?endforeach;?>
										</ul>
									<?endif;?>
								</li>
							<?endforeach;?>
						</ul>
					<?endif;?>
				</div>
			<?endforeach;?>
		</div>
	</div>
<?endif;?>
