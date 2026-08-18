<?CNext::checkRestartBuffer();?>
						<?IncludeTemplateLangFile(__FILE__);?>
							<?if(!$isIndex):?>
									<?if (($isBlog) || ($isProject)) :?>
											
								</div> <?// class=col-md-9 col-sm-12 col-xs-8 content-md?>
									
									
									<?if (substr_count($_SERVER['REQUEST_URI'], '/') >2 ):?>

									<div class="col-md-3 col-sm-3 hidden-xs hidden-sm right-menu-md"  >
									
										<div class="sidearea" >
											<?if (!$isProject) :?>
														<div class="infochat nd-infochat-wrap">
														<?$APPLICATION->IncludeComponent(
	"bitrix:main.include", 
	".default", 
	[
		"COMPONENT_TEMPLATE" => ".default",
		"PATH" => SITE_DIR."include/infochat_materials_newdesign.php",
		"AREA_FILE_SHOW" => "file",
		"AREA_FILE_SUFFIX" => "",
		"AREA_FILE_RECURSIVE" => "Y",
		"EDIT_TEMPLATE" => "standard.php"
	],
	false
);?>
														</div>
											<?endif;?>
											<?CNext::get_banners_position('SIDE');?>
										</div>
						
									</div>	
									<?endif;?>
									
									
								</div>
								
								
										<?endif;?>
								
								<?if($isHideLeftBlock && !$isWidePage):?>
								
								</div> <?// .maxwidth-theme?>
								<?endif;?>
								</div> 
								
								
								<?// .container?>
							<?else:?>
							
								<?// Блоки главной для нового дизайна лежат в /indexblocks_newdesign.php —
								// все правки главной делаем там. Настройка темы INDEX_TYPE общая на сайт,
								// поэтому переключить её только для этого шаблона нельзя.
								// Если своего файла нет, работает штатный выбор по INDEX_TYPE.
								$ndIndexBlocks = $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'indexblocks_newdesign.php';
								if (file_exists($ndIndexBlocks)) {
									include $ndIndexBlocks;
								} else {
									CNext::ShowPageType('indexblocks');
								}?>
							<?endif;?>
							<?CNext::get_banners_position('CONTENT_BOTTOM');?>
						</div> <?// .middle?>
					<?//if(!$isHideLeftBlock && !$isBlog):?>
	<?if( ($isIndex && $isShowIndexLeftBlock) || (!$isIndex && !$isHideLeftBlock) && !$isBlog):?>
				
						</div>



						<?// .right_block?>				
						<?if($APPLICATION->GetProperty("HIDE_LEFT_BLOCK") != "Y" && !(defined('ERROR_404') && !defined('ERROR_PAGEN'))):?>
							<div class="left_block">
								<?// Левая колонка нового дизайна подключается напрямую:
								// штатный ShowPageType выбирает файл настройкой темы,
								// а она одна на сайт (та же беда, что с HEADER_TYPE).
								include __DIR__.'/page_blocks/left_block_newdesign.php';?>
		                    </div>
						<?endif;?>							
						
					
						
					<?endif;?>
				<?if($isIndex):?>
					</div>
				<?elseif(!$isWidePage):?>
					
					</div> <?// .wrapper_inner?>				
				
				
				<?endif;?>
			
			
			</div> <?// #content?>

		</div><?// .wrapper?>
		

	
		
		<footer id="footer">
			<?// Подвал нового дизайна подключаем напрямую, а не через
			// CNext::ShowPageType('footer'): тот выбирает файл по настройке темы
			// FOOTER_TYPE, а она одна на весь сайт и к шаблону не привязана
			// (та же беда, что с HEADER_TYPE и INDEX_TYPE).
			include __DIR__.'/page_blocks/footer_newdesign.php';?>
		</footer>

		<?// Прибитая нижняя панель мобильного нового дизайна и её шторки
		// («Меню» и «Связаться с нами»). Выводим после подвала, чтобы шторки
		// лежали поверх контента; на десктопе блок скрыт классами темы.?>
		<div class="visible-xs visible-sm">
			<?include __DIR__.'/page_blocks/nav_bottom_newdesign.php';?>
		</div>
	
		
		
	
<script>
$("[data-fancybox]").fancybox({
speed : 330,
image : {
protect : true
}
});
</script>


<script>  
$(document).ready(function() {
    $("a.gallery").fancybox();
});

</script>


<script src="/bitrix/templates/aspro_next/js/jquery.airStickyBlock.min.js"></script>


<?CNext::setFooterTitle();
		CNext::bottomActions();

		// Вылетающая корзина (красная плашка у правого края) в новом дизайне не нужна:
		// в макете её нет, корзина живёт только в шапке. Сам вызов убирать нельзя —
		// showFooterBasket() кроме плашки печатает arBasketAspro и setBasketStatusBtn(),
		// на них держится состояние кнопок «В корзину / В корзине» во всём каталоге.
		// Поэтому подменяем вид корзины на NORMAL: тогда метод печатает только скрипты,
		// а разметку .basket_wrapp.fly не выводит совсем (CNext.php, showFooterBasket).
		// Значение правим в самом конце страницы — дальше $arTheme никто не читает.
		// CNext.php лежит в корне сайта, вне Git, его не трогаем.
		global $arTheme;
		if (isset($arTheme['ORDER_BASKET_VIEW'])) {
			if (is_array($arTheme['ORDER_BASKET_VIEW'])) {
				$arTheme['ORDER_BASKET_VIEW']['VALUE'] = 'NORMAL';
			} else {
				$arTheme['ORDER_BASKET_VIEW'] = 'NORMAL';
			}
		}
		CNext::showFooterBasket();?>
		
		
		
<script src="/local/templates/aspro_next/js/cookie-banner.js"></script>
<link rel="stylesheet" href="https://cdn.envybox.io/widget/cbk.css">
<script type="text/javascript" src="https://cdn.envybox.io/widget/cbk.js?wcb_code=e4de92bacc448ee6b674c4cb61afd66e" charset="UTF-8" async></script>
</body>
</html>