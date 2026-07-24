<?CNext::checkRestartBuffer();?>
						<?IncludeTemplateLangFile(__FILE__);?>
							<?if(!$isIndex):?>
									<?if (($isBlog) || ($isProject)) :?>
											
								</div> <?// class=col-md-9 col-sm-12 col-xs-8 content-md?>
									
									
									<?if (substr_count($_SERVER['REQUEST_URI'], '/') >2 ):?>

									<div class="col-md-3 col-sm-3 hidden-xs hidden-sm right-menu-md"  >
									
										<div class="sidearea" >
											<?if (!$isProject) :?>
														<div class="infochat ">
														<?$APPLICATION->IncludeComponent(
	"bitrix:main.include", 
	".default", 
	[
		"COMPONENT_TEMPLATE" => ".default",
		"PATH" => SITE_DIR."include/infochat_materials.php",
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
							
								<?CNext::ShowPageType('indexblocks');?>
							<?endif;?>
							<?CNext::get_banners_position('CONTENT_BOTTOM');?>
						</div> <?// .middle?>
					<?//if(!$isHideLeftBlock && !$isBlog):?>
	<?if( ($isIndex && $isShowIndexLeftBlock) || (!$isIndex && !$isHideLeftBlock) && !$isBlog):?>
				
						</div>



						<?// .right_block?>				
						<?if($APPLICATION->GetProperty("HIDE_LEFT_BLOCK") != "Y" && !(defined('ERROR_404') && !defined('ERROR_PAGEN'))):?>
							<div class="left_block">
								<?CNext::ShowPageType('left_block');?>
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
			<?CNext::ShowPageType('footer');?>
		</footer>
	
		
		
	
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
		CNext::showFooterBasket();?>
		
		
		
<script src="/local/templates/aspro_next/js/cookie-banner.js"></script>
<link rel="stylesheet" href="https://cdn.envybox.io/widget/cbk.css">
<script type="text/javascript" src="https://cdn.envybox.io/widget/cbk.js?wcb_code=e4de92bacc448ee6b674c4cb61afd66e" charset="UTF-8" async></script>
</body>
</html>