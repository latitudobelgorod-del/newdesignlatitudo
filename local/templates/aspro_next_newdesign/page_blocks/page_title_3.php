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






<?if ($regionID == '9278') : ?>
<div class="top_inner_block_wrapper">
<div class="maxwidth-theme">
	<div class="row">
		<div class="col-md-6"><div class="banner_gradient"><a href="/contacts/"><img  alt="Контакты Латитудо в Воронеже" title="Контакты Латитудо в Воронеже" src="/images/banners/banner_small_office_vrn.jpg" loading="lazy" ></a></div></div>
		<div class="col-md-6"><div class="banner_gradient"><a href="/info/rassrochka/"><img  alt="Рассрочка" title="Рассрочка" src="/images/banners/banner_small_rassrochka.jpg" loading="lazy" ></a></div></div>
	</div>
</div>
</div>
<?else:?>
<?/* Баннер «Комплект Пергола + мебель». На самой странице перголы его не
   показываем: он туда и ведёт, и звать со страницы на неё же незачем. */?>
<?if(strpos($APPLICATION->GetCurPage(false), '/projects/pergoly/') !== 0):?>
<div class="top_inner_block_wrapper">
<div class="maxwidth-theme">
<div class="banner_gradient"><a href="/projects/pergoly/"><img  alt="Комплект Пергола + мебель" title="Комплект Пергола + мебель" src="/images/banners/banner_pergola.jpg" loading="lazy" ></a></div>
</div>
</div>
<?endif;?>
<?endif;?>





