<?
global $arTheme, $arRegion, $APPLICATION;
$arRegions = CNextRegionality::getRegions();
$regionID = ($arRegion ? $arRegion['ID'] : '');

?>

<div class="top_inner_block_wrapper maxwidth-theme">
	<div class="page-top-wrapper grey v3">
		<section class="page-top maxwidth-theme <?CNext::ShowPageProps('TITLE_CLASS');?>">
			
			<div id="navigation">
				<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "next1", array(
					"START_FROM" => "0",
					"PATH" => "",
					"SITE_ID" => SITE_ID,
					"SHOW_SUBSECTIONS" => "N"
					),
					false,
					array("HIDE_ICONS"=>"Y")
				);?>
			</div>
		</section>
	</div>
</div>






<?/* Баннер «Комплект Пергола + мебель» — один на все регионы. Раньше Воронежу
   (region 9278) показывалась своя пара половинных баннеров: переезд офиса и
   рассрочка. Теперь регион ничем не отличается от остальных.

   На самой странице перголы баннер не печатаем: он туда и ведёт. */?>
<?if(strpos($APPLICATION->GetCurPage(false), '/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/') !== 0):?>
<div class="top_inner_block_wrapper">
<div class="maxwidth-theme">
<div class="banner_gradient banner_gradient--pergola"><a href="/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/"><img  alt="Комплект Пергола + мебель" title="Комплект Пергола + мебель" src="/images/banners/banner_pergola.jpg" loading="lazy" ></a></div>
</div>
</div>
<?endif;?>





