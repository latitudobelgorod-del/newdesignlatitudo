<?php
global $arRegion,$arTheme, $APPLICATION, $utmSource;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$REGION_TAG_NAME = "#REGION_TAG_NAME#";
$REGION_TAG_ADDRESSMY = "#REGION_TAG_ADDRESSMY#";
$REGION_TAG_PHONE = "#REGION_TAG_PHONE#";
$REGION_TAG_PHONE_PODMENA = "#REGION_TAG_PHONE_PODMENA#";
$REGION_TAG_TIME = "#REGION_TAG_TIME#";
?>



<?=bitrix_sessid_post();?>
<?
foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_source')
	$utm_source =$v;
}
/* 22.09.2026: подмена — как в шапке: только пока идёт рекламный визит (ndShouldReplaceContacts, сутки с перехода);
   метка в сессии живёт 30 дней. 'ya_visit' лишь включает проверки str_contains(..., 'ya') ниже. */
$utm_source = (function_exists('ndShouldReplaceContacts') && ndShouldReplaceContacts()) ? 'ya_visit' : 'empty';
?>




<div class="editor right_block">
<div class="detail_text">
<div class="photo_ter_block"> 
<div class="row"> 
<div class="col-lg-6 col-md-6 col-sm-12 "> 	
<div class="img"><img class="lazy img-responsive" alt="Купить доску ДПК в #REGION_NAME_DECLINE_PP#" src="/assets/lazyload/loading.gif" data-original="/include/mainpage/img_front_main/company.jpg" /> </div>
</div>
<div class="col-lg-6 col-md-6 col-sm-12 "> 	
<div class="row">
<div class="col-lg-12 col-md-12 col-sm-12 "> 
<div class="item">	
<div>ЛАТИТУДО - профессиональная террасная компания, с офисами в Москве, Воронеже, Белгороде, Краснодаре и Ростове-на-Дону. С 2014 года 
мы специализируемся на материалах из древесно-полимерного композита (ДПК) для объектов различной сложности: общественных пространств, HoReCa и частных домов.
</div>
<div class="tiz_ter_block">
	<div class="row"> 
				<div class="col-lg-4 col-md-4 col-sm-4 col"> 	
					<div class="item">  
						<div class="img bg-design"></div>			 
						<div class="title">Разработка технических решений</div>
						
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col"> 	
					<div class="item">    
						<div class="img bg-shipment"></div>
						<div class="title">Производство и поставка материалов</div>
						
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col"> 	
					<div class="item">    
						<div class="img bg-repairer"></div>
						<div class="title">Профессиональный монтаж</div>
						
					</div>
				</div>
		
	</div>
</div>
<div><span class="bold">Обратитесь в отдел продаж</span> любым удобным способом.</div>
<div>Для вашего удобства в шоу-румах компании представлены образцы, стенды и каталоги. Опытные менеджеры готовы помочь с расчетами, комплектацией, доставкой в любой регион и услугами монтажа.</div>
<div>Работаем с юридическими лицами по любой форме оплаты.</div>
<div class="architect" >Архитекторам - индивидуальные условия!</div>

<? if($arRegion):?> 
<?if (($regionID == 9277) || ($regionID == 9278) || ($regionID == 10039) || ($regionID == 9568) || ($regionID == 22018) ): ?> 
		<?if($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
			<div style="margin: 0px 0 20px 40px;text-indent: -20px;"><p><span class="fa fa-map-marker" style="margin-right: 20px;"></span><?=$REGION_TAG_ADDRESSMY?></p></div>
		<?endif;?>	
		<?
		   $dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']); 
		   $href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
		   $href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
		   ?>
		  


		
		  	<?if (ndShouldReplaceContacts()) :?>
				<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
					<div style="margin: 0px 0 20px 40px;text-indent: -20px;">
					<p><span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href1?>"><?=$REGION_TAG_PHONE_PODMENA?></a></p>
					</div>
				<?else:?>
					<div style="margin: 0px 0 20px 40px;text-indent: -20px;">
					<p><span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></p>
					</div>
				<?endif;?>
			<?else:?>
					<div style="margin: 0px 0 20px 40px;text-indent: -20px;">
					<p><span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></p>
					</div>
			<?endif;?>
		  
		
<?else:?>
<a href="/contacts/">Контакты</a>
<?endif;?>
<?endif;?>

</div></div></div></div></div></div></div></div>