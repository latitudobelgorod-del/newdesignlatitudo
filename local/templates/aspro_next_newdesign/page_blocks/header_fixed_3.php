<?
global $arTheme, $arRegion;
if($arRegion)
	$bPhone = ($arRegion['PHONES'] ? true : false);
else
	$bPhone = ((int)$arTheme['HEADER_PHONES'] ? true : false);
$logoClass = ($arTheme['COLORED_LOGO']['VALUE'] !== 'Y' ? '' : ' colored');
?>
<div class="wrapper_inner">
	<div class="logo-row v1 row margin0">
		<div class="pull-left">
			<div class="inner-table-block sep-left nopadding logo-block">
				<div class="logo<?=$logoClass?>">
					<?=CNext::ShowLogo();?>
				</div>
			</div>
		</div>
		<div class="pull-left">
			<div class="inner-table-block menu-block rows sep-left">
				<div class="title"><i class="svg svg-burger"></i><?=GetMessage("S_MOBILE_MENU")?>&nbsp;&nbsp;<i class="fa fa-angle-down"></i></div>
				<div class="navs table-menu js-nav">
					<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
						array(
							"COMPONENT_TEMPLATE" => ".default",
							"PATH" => SITE_DIR."include/menu/menu.top_fixed_field.php",
							"AREA_FILE_SHOW" => "file",
							"AREA_FILE_SUFFIX" => "",
							"AREA_FILE_RECURSIVE" => "Y",
							"EDIT_TEMPLATE" => "include_area.php"
						),
						false, array("HIDE_ICONS" => "Y")
					);?>
				</div>
			</div>
		</div>
		<div class="pull-left col-md-3 nopadding hidden-sm hidden-xs search animation-width">
			<div class="inner-table-block">
				<?$APPLICATION->IncludeComponent(
					"bitrix:main.include",
					"",
					Array(
						"AREA_FILE_SHOW" => "file",
						"PATH" => SITE_DIR."include/top_page/search.title.catalog.php",
						"EDIT_TEMPLATE" => "include_area.php"
					)
				);?>
			</div>
		</div>
		<div class="pull-left" style="
    border-right: rgba(0,0,0,0.05) 1px solid;
    border-left: rgba(0,0,0,0.05) 1px solid;
    padding: 0 15px;
    ">


<div class="wrap_icon inner-table-block videoyt" >
								<a  title="Наши видеообзоры" class="personal-link dark-color" href="/video/" >
<div class="text">Смотрите<br>наши видео</div>
<div class="img"><img src="/images/yt_video.png" width="30"></div>
</a>
							</div>
							
					
		
							
							
							
							


							</div>
		<div class="pull-right">
			<?CNext::ShowBasketWithCompareLink('top-btn inner-table-block', 'lg');?>
		</div>
		<div class="pull-right">
			<div class="inner-table-block small-block">
				<div class="wrap_icon wrap_cabinet">
					<?=CNext::ShowCabinetLink(true, false, 'lg');?>
				</div>
			</div>
		</div>
		<?//if($arTheme['SHOW_CALLBACK']['VALUE'] == 'Y'):?>
			<div class="pull-right">
				<div class="inner-table-block">
					<div class="animate-load btn btn-default" data-event="jqm" data-param-form_id="CALLBACK" data-name="question">
						<span>Заказать звонок</span>
					</div>
					
					
				
					
					<!--a class="btn btn-default white btn-sm" href="#callbackwidget">
						Заказать звонок
					</a-->
				</div>
			</div>
		<?//endif;?>
		<?if($bPhone):?>
			<div class="pull-left logo_and_menu-row">
				<div class="inner-table-block phones">
					<?CNext::ShowHeaderPhones();?>
				</div>
			</div>
		<?endif;?>
	</div>
</div>