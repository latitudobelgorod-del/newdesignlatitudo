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
								}
								// Окно «Оставить заявку» с формой в HTML главной — для проверки 152-ФЗ.
								include __DIR__.'/include/home_form_newdesign.php';?>
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
		
		
		
<!-- Top.Mail.Ru counter -->
<?/* Счётчик Top.Mail.Ru — обычным кодом в странице, как на vrn.easydecking.ru (07.10.2026);
   прежде его загружал js/cookie-banner.js. Считает все посещения, как и раньше. */?>
<script>
var _tmr = window._tmr || (window._tmr = []);
_tmr.push({id: "3477275", type: "pageView", start: (new Date()).getTime()});
(function (d, w, id) {
  if (d.getElementById(id)) return;
  var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
  ts.src = "https://top-fwz1.mail.ru/js/code.js";
  var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
  if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
})(document, window, "tmr-code");
</script>
<noscript><div><img src="https://top-fwz1.mail.ru/counter?id=3477275;js=na" style="position:absolute;left:-9999px;" alt="Top.Mail.Ru" /></div></noscript>
<!-- /Top.Mail.Ru counter -->
<?/* Разметка cookie-баннера — в HTML, скрытой; показывает её js/cookie-banner.js (см. там, 07.10.2026). */?>
<div id="cb-wrap" role="dialog" aria-label="Уведомление об использовании файлов cookie" hidden><p id="cb-text">Сайт использует cookie и аналитику, согласно <a href="/info/licenses_detail/" target="_blank" rel="noopener">Политике конфиденциальности</a>.</p><button id="cb-accept">Согласен</button></div>
<script src="/local/templates/aspro_next_newdesign/js/cookie-banner.js?v=20261007b"></script>
<?/* Виджет обратного звонка envybox — по первому действию посетителя, из отдельного
   файла js/nd-lazy-widgets.js (там же значок рейтинга на главной; почему — см. шапку файла). */?>
<script src="<?=SITE_TEMPLATE_PATH?>/js/nd-lazy-widgets.js?v=20261007" defer></script>
<?/* Кнопка звонка на телефоне (26.10.2026): круг 90px закрывал «В корзину» в плитках
   и поля калькуляторов. Уменьшаем до ~56px и прячем, пока человек вводит текст.
   Положение над нижним меню и панелями корзины/карточки задают
   css/newdesign-mobile.css и css/newdesign-element.css — его здесь не трогаем:
   transform-origin в правом нижнем углу оставляет кнопку на том же месте.
   left гасим: виджет пишет положение себе инлайном, и с left значение right не работает.
   zoom гасим: на телефоне (по user-agent) виджет ставит себе инлайном zoom ~0.57, а zoom
   уменьшает и отступы — bottom 68px превращался в ~39 и кнопка садилась на нижнее меню.
   Без zoom кнопка мобильного вида 110px, scale 0.5 даёт ~55px. */?>
<style>
@media (max-width: 767px) {
  a.callbackkiller.cbk-phone {
    left: auto !important;
    zoom: 1 !important;
    transform: scale(0.5) !important;
    transform-origin: right bottom !important;
  }
  body.cbk-hidden a.callbackkiller.cbk-phone {
    display: none !important;
  }
}
</style>
<script>
(function () {
  var mq = window.matchMedia('(max-width: 767px)');
  var FIELDS = 'input:not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]), select, textarea';

  document.addEventListener('focusin', function (e) {
    if (mq.matches && e.target.matches && e.target.matches(FIELDS)) {
      document.body.classList.add('cbk-hidden');
    }
  });
  document.addEventListener('focusout', function () {
    // задержка: при переходе между полями кнопка не мигает
    setTimeout(function () {
      var a = document.activeElement;
      if (!(a && a.matches && a.matches(FIELDS))) {
        document.body.classList.remove('cbk-hidden');
      }
    }, 150);
  });
})();
</script>
</body>
</html>