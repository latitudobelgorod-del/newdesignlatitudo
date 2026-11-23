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

<h3>Наши преимущества</h3>
<div class="tiz_ter_block">
	<div class="row"> 
				<div class="col-lg-3 col-md-3 col-sm-3 col"> 	
					<div class="item">  
						<div class="bg-garantiya"></div>			 
						<div class="title bold">Гарантия лучшей цены</div>
						<div>на продажу ДПК собственного производства</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-3 col">  
					<div class="item">    
						<div class="bg-komplektuyushchie"></div>
						<div class="title bold">Все нужные материалы</div>
						<div>лаги, опоры и крепеж</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-3 col "> 
					<div class="item">    
						<div class="bg-kachestvo"></div>
						<div class="title bold">Опыт и качество</div>
						<div>проверенное временем</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-3 col"> 
					<div class="item"> 	
						<div class="bg-servis"></div>
						<div class="title bold">Полный сервис</div>
						<div>от проекта до монтажа</div>
					</div>
				</div>
	</div>
</div>

<?/* Новый дизайн: блок «Приезжайте в шоурум» — разметка в шаблоне
   (include/showroom_newdesign.php), тексты списков и фото — здесь (11.09.2026).
   Старому дизайну — прежний блок ниже, без изменений. */?>
<?if (SITE_TEMPLATE_ID === 'aspro_next_newdesign' && is_file($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/showroom_newdesign.php')):
	$ndShowroom = array (
  'image' => '/include/catalog/img/polovina-konteynera.jpg',
  'lists' => 
  array (
    'Специалисты и образцы' => 
    array (
      0 => 'Опытные специалисты',
      1 => 'Все образцы доски в офисе',
      2 => 'Выезд на замер с образцами',
      3 => 'Проект и визуализация',
    ),
    'Расчёт, доставка, монтаж' => 
    array (
      0 => 'Расчет материалов по размерам',
      1 => 'Хранение, самовывоз, доставка',
      2 => 'Любая форма оплаты',
      3 => 'Работы по <a href="/services/montazh-terrasnoy-doski-iz-dpk/">монтажу</a> и демонтажу',
    ),
  ),
);
	include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/showroom_newdesign.php';
else:?>
<div class="photo_ter_block"> 
<div class="row"> 
<div class="col-lg-6 col-md-6 col-sm-12 "> 	
<div class="img"><img src="/include/catalog/img/polovina-konteynera.jpg" alt="Купить террасную доску" class="img-responsive"/> </div>
</div>

<div class="col-lg-6 col-md-6 col-sm-12 "> 	
<div class="row">
<div class="col-lg-12 col-md-12 col-sm-12 "> 
<div class="item" style="margin-left: 40px;">	
<? if($arRegion):?> 
 <?if($arRegion['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
		<div style="margin: 0px 0 20px 0;text-indent: -20px;"><span class="fa fa-map-marker" style="margin-right: 20px;"></span><?=$REGION_TAG_ADDRESSMY?> <a href="/contacts/">Схема проезда</a></div> 
	<?endif;?>
<?
		   $dump = preg_replace("/[^0-9]/", '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']); 
		   $href = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE_VALUE']);
		   $href1 = 'tel:'.str_replace(array(' ', '-', '(', ')'), '', $arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']);
		   ?>
		  


		
		  	<?if (ndShouldReplaceContacts()) :?>
				<?if ($arRegion['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
				
						<div style="margin: 0px 0 20px 0;text-indent: -20px;">
				 <span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href1?>"><?=$REGION_TAG_PHONE_PODMENA?></a>
				</div>
				<?else:?>
				<div style="margin: 0px 0 20px 0;text-indent: -20px;">
				 <span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a>
				</div>


					
				<?endif;?>
			<?else:?>
					<div style="margin: 0px 0 20px 40px;text-indent: -20px;">
					<p><span class="fa fa-phone" style="margin-right: 20px;"></span> <a rel="nofollow" href="<?=$href?>"><?=$REGION_TAG_PHONE?></a></p>
					</div>
			<?endif;?>
  
  
<?endif;?>
<?if ($REGION_TAG_TIME):?>
<div style="margin: 0px 0 20px 0;text-indent: -20px;"><span class="fa fa-clock-o" style="margin-right: 20px;"></span><?=$REGION_TAG_TIME?></div>
<? endif; ?>
</div>
</div>
<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 col"> 	
	<ul>
		<li><i class="fa fa-check "></i>Опытные специалисты</li>
		<li><i class="fa fa-check "></i>Все образцы доски в офисе</li>
		<li><i class="fa fa-check "></i>Выезд на замер с образцами</a></li>
		<li><i class="fa fa-check "></i>Проект и визуализация</li>
	</ul>
</div>
<div class="col-lg-6 col-md-6 col-sm-6  col-xs-12 col"> 
	<ul>
	<li><i class="fa fa-check "></i>Расчет материалов по размерам</li>
	<li><i class="fa fa-check "></i>Хранение, самовывоз, доставка</li>
	<li><i class="fa fa-check "></i>Любая форма оплаты</li>
	<li><i class="fa fa-check "></i>Работы по <a href="/services/montazh-terrasnoy-doski-iz-dpk/">монтажу</a> и демонтажу</li>
	</ul>	
</div>
</div>
<div class="architect" ><a href="/info/sotrudnichestvo-s-arhitektorami/">Архитекторам</a> - индивидуальные условия и помощь в проектировании!</div>
</div>
</div>
</div>
<?endif;?>
<div class="tiz_ter_block_3img center">
<div class="bold">Вызовите мастера с образцами материала</div>
<div style="margin:20px 0;"><span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  btn-default  btn" data-event="jqm" data-param-form_id="MAINFORM" data-name="spbuttonMAINFORMRIHuh65Sl632XDMFy" >Заказать замер и расчет</span></div>
<div>Профессиональный монтаж объекта любой сложности</div>
</div>

<div class="tiz_ter_block_3img">
<div class="list items">
	<div class="row flexbox">
		<div class="col-md-3 col-sm-3 col-xs-12 front-sect col">
			<div class="item ">
			<div class="img shine"><img src="/include/catalog/img/montazh-doski.jpg" alt="Монтаж террасной доски от 750 руб./м2" title="Монтаж террасной доски от 750 руб./м2"></div>
			<div class="name ">
			<div class="blk"> 
			<div class="left col-md-12 col-sm-12 "><a href="/services/montazh-terrasnoy-doski-iz-dpk/">Монтаж террасной доски</a>
			<br><span>цена от 750 руб./м2</span></div>
			</div>
			</div>
			</div>
		</div>
		<div class="col-md-3 col-sm-3 col-xs-12 front-sect col">
			<div class="item ">
			<div class="img shine"><img src="/include/catalog/img/ustanovka-peril.jpg" alt="Монтаж ограждений от 2000 руб./метр" title="Монтаж ограждений от 2000 руб./метр"></div>
			<div class="name ">
			<div class="blk"> 
			<div class="left col-md-12 col-sm-12"><a href="/services/montazh-peril-dlya-ulichnoy-lestnitsy/">Монтаж перил</a> 
			<br><span>цена от 2000 руб./метр</span></div>
			</div>
			</div>
			</div>
		</div>
		<div class="col-md-3 col-sm-3 col-xs-12 front-sect col">
			<div class="item " >
			<div class="img shine"><img src="/include/catalog/img/ustanovka-stupeney.jpg" alt="Установка ступеней от 2500 руб./метр" title="Установка ступеней от 2500 руб./метр"></div>
			<div class="name ">
			<div class="blk"> 
			<div class="left col-md-12 col-sm-12"><span style="color:#fff; text-decoration:none; border-bottom: 1px solid;">Установка ступеней</span>
			<br><span>цена от 2500 руб./метр</span></div>
			</div>
			</div>
			</div>
		</div>
			<div class="col-md-3 col-sm-3 col-xs-12 front-sect col">
			<div class="item " >
			<div class="img shine"><img src="/include/catalog/img/ter_pod_kl.jpg" alt="Строительство террас от 9500 руб./метр" title="Строительство террас от 9500 руб./метр"></div>
			<div class="name ">
			<div class="blk"> 
			<div class="left col-md-12 col-sm-12"><a href="/services/stroitelstvo-terras-pod-kluch/">Строительство террас</a>
			<br><span>цена от 9500 руб./метр</span></div>
			</div>
			</div>
			</div>
		</div>
	</div>
</div>
</div>