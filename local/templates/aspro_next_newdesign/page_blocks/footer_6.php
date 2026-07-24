<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<div class="footer_inner <?=($arTheme["SHOW_BG_BLOCK"]["VALUE"] == "Y" ? "fill" : "no_fill");?> compact footer-grey">
	<div class="bottom_wrapper">
		<div class="wrapper_inner">
			<div class="bottom-middle">
			<div class="row">
				<div class="col-md-3 col-sm-3 copy-block">
					<div class="copy blocks">
						<?$APPLICATION->IncludeFile(SITE_DIR."include/footer/copy/copyright.php", Array(), Array(
								"MODE" => "php",
								"NAME" => "Copyright",
								"TEMPLATE" => "include_area.php",
							)
						);?>
					</div>
					<div class="print-block blocks"><?=CNext::ShowPrintLink();?></div>
					<div id="bx-composite-banner" class="blocks"></div>
				</div>
				<div class="col-md-6 col-sm-6">
					<?$APPLICATION->IncludeFile(SITE_DIR."include/footer/contacts-title.php", array(), array(
							"MODE" => "html",
							"NAME" => "Title",
							"TEMPLATE" => "include_area.php",
						)
					);?>
					<div class="row info">
						<div class="col-md-6">
							<?CNext::ShowHeaderPhones('', true);?>
							<div class="email blocks">
								<?$APPLICATION->IncludeFile(SITE_DIR."include/footer/site-email.php", array(), array(
										"MODE" => "html",
										"NAME" => "E-mail",
										"TEMPLATE" => "include_area.php",
									)
								);?>
							</div>	
						</div>
						<div class="col-md-6">
							<div class="address blocks">
								<?$APPLICATION->IncludeFile(SITE_DIR."include/top_page/site-address.php", array(), array(
										"MODE" => "html",
										"NAME" => "Address",
										"TEMPLATE" => "include_area.php",
									)
								);?>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-3 col-sm-3">
					<div class="social-block">
						<?$APPLICATION->IncludeComponent(
							"aspro:social.info.next",
							".default",
							array(
								"CACHE_TYPE" => "A",
								"CACHE_TIME" => "3600000",
								"CACHE_GROUPS" => "N",
								"COMPONENT_TEMPLATE" => ".default",
								"SOCIAL_TITLE" => GetMessage("SOCIAL_TITLE")
							),
							false
						);?>
					</div>
				</div>
			</div>
			</div>
		</div>
	</div>
</div>