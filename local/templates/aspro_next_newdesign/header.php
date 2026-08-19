<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?if($_GET["debug"] == "y")
	error_reporting(E_ERROR | E_PARSE);
IncludeTemplateLangFile(__FILE__);
global $APPLICATION, $arRegion, $arSite, $arTheme, $perem_metrika, $imya_sayta;
$arSite = CSite::GetByID(SITE_ID)->Fetch();
$htmlClass = ($_REQUEST && isset($_REQUEST['print']) ? 'print' : false);
$bIncludedModule = (\Bitrix\Main\Loader::includeModule("aspro.next"));
$imya_sayta = $_SERVER['SERVER_NAME'];

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns#" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>" <?=($htmlClass ? 'class="'.$htmlClass.'"' : '')?>>
<head>

    <?if($APPLICATION->getCurPage() != '/'):?> 
	<title><?$APPLICATION->ShowTitle()?></title>
	<?else:?>
	<title><?$APPLICATION->ShowTitle()?></title>
	<?endif;?>
	<?$APPLICATION->ShowMeta("viewport");?>
	<?$APPLICATION->ShowMeta("HandheldFriendly");?>
<?$APPLICATION->ShowMeta("apple-mobile-web-app-capable", "yes");?>
	<?$APPLICATION->ShowMeta("apple-mobile-web-app-status-bar-style");?>
	<?$APPLICATION->ShowMeta("SKYPE_TOOLBAR");?>
	<?$APPLICATION->ShowHead();
	?>
	

<?$APPLICATION->AddHeadString('<script>BX.message('.CUtil::PhpToJSObject( $MESS, false ).')</script>', true);?>
<?
$url_noindex = $APPLICATION->GetCurUri();
$findstr   = "SEF_APPLICATION_CUR_PAGE_URL";
?>
<?if (strripos($url_noindex, $findstr) !== false) :?>   
 <meta name="robots" content="noindex" />

 <?endif;?>

<script src="/assets/lazyload/jquery.lazyload.min.js"></script>


	<meta name="referrer" content="origin">
	<?if($bIncludedModule)
		CNext::Start(SITE_ID);?>

<?if($imya_sayta == 'latitudo.ru') :?>
	<meta name="google-site-verification" content="-TTAPUpXWmnT6ZV_N16B9seJTaWZbyzkVcJ2FozF7gA" />
 <?endif;?>


</head>

<body class="<?=($bIncludedModule ? "fill_bg_".strtolower(CNext::GetFrontParametrValue("SHOW_BG_BLOCK")) : "");?>" id="main" >


	<div id="panel"><?$APPLICATION->ShowPanel();?></div>

	<?if(!$bIncludedModule):?>
		<?$APPLICATION->SetTitle(GetMessage("ERROR_INCLUDE_MODULE_ASPRO_NEXT_TITLE"));?>
		<center><?$APPLICATION->IncludeFile(SITE_DIR."include/error_include_module.php");?></center></body></html><?die();?>
	<?endif;?>

	<?$arTheme = $APPLICATION->IncludeComponent("aspro:theme.next", ".default", array("COMPONENT_TEMPLATE" => ".default"), false, array("HIDE_ICONS" => "Y"));?>
	<?// Стили нового дизайна. Подключаем после theme.next (он подключает custom.css),
	// чтобы наши правила шли в сборке позже и перебивали старые.
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/newdesign.css', true);
	// Шапка нового дизайна — отдельным файлом, идёт после newdesign.css.
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/newdesign-header.css', true);
	// Мобильные шапка и нижняя панель — последними, они перебивают отступы,
	// которые newdesign-header.css задаёт под десктопную шапку.
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/newdesign-mobile.css', true);
	// Модальные формы: окно приходит ajax'ом, стили нужны на любой странице.
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/newdesign-forms.css', true);
	$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/newdesign-forms.js?'.@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-forms.js'));
	// Капча: не пускаем отправку формы с нерешённым виджетом.
	$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/smartcaptcha-guard.js?'.@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/smartcaptcha-guard.js'));
	// Цифра у корзины после добавления товара: штатная цепочка темы её не обновляет.
	$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/newdesign-basket-count.js?'.@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-basket-count.js'));
	// Кружки цветов на плитках — одной пачкой вместо запроса на каждую плитку.
	$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/newdesign-sku-colors.js?'.@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-sku-colors.js'));?>
	<?include_once('defines.php');?>
	<?CNext::SetJSOptions();?>
<?CNext::ShowPageType('search_title_component');?>
	<div class="wrapper1 <?=($isIndex && $isShowIndexLeftBlock ? "with_left_block" : "");?>
	<?=CNext::getCurrentPageClass();?> <?=CNext::getCurrentThemeClasses();?>">
		<?CNext::get_banners_position('TOP_HEADER');?>

		<div class="header_wrap visible-lg visible-md title-v<?=$arTheme["PAGE_TITLE"]["VALUE"];?><?=($isIndex ? ' index' : '')?>">
			<header id="header">
				<?// Шапка нового дизайна подключается напрямую, а не через
				// CNext::ShowPageType('header'): тот выбирает файл по настройке темы
				// HEADER_TYPE, она одна на весь сайт и к шаблону не привязана —
				// боевой aspro_next тогда тоже переехал бы на новую шапку.
				include(__DIR__.'/page_blocks/header_newdesign.php');?>
			</header>
		</div>

		<?// Штатная «прилипающая» шапка (#headerfixed) в новом дизайне не нужна:
		// header_newdesign.php сам зафиксирован сверху, иначе на скролле было бы
		// две шапки сразу.?>

		<?// Мобильная шапка нового дизайна. Штатные ShowPageType('header_mobile')
		// и выпадающее #mobilemenu не выводим: меню переехало в прибитую нижнюю
		// панель (page_blocks/nav_bottom_newdesign.php, подключается из footer.php).
		// Скрипты темы, завязанные на #mobileheader/#mobilemenu, проверяют
		// наличие этих элементов, поэтому без них ничего не ломается.?>
		<?/* nd-mheader-wrap: у этой обёртки свой контекст наложения (тема даёт
		   ей z-index 2), а контент страницы лежит в .wraps с z-index 3 — без
		   поднятия карточки проезжали поверх прибитой шапки. */?>
		<div class="visible-xs visible-sm nd-mheader-wrap">
			<?include(__DIR__.'/page_blocks/header_mobile_newdesign.php');?>
		</div>

		<?CNext::get_banners_position('TOP_UNDERHEADER');?>

		<?if($arTheme['MOBILE_FILTER_COMPACT']['VALUE'] === 'Y'):?>
				<div id="mobilefilter" class="visible-xs visible-sm scrollbar-filter"></div>
			<?endif;?>



		<?/*filter for contacts*/
		if($arRegion && !in_array('component', $arRegion['LIST_STORES']))
		{
			if($arTheme['STORES_SOURCE']['VALUE'] != 'IBLOCK')
				$GLOBALS['arRegionality'] = array('ID' => $arRegion['LIST_STORES']);
			else
				$GLOBALS['arRegionality'] = array('PROPERTY_STORE_ID' => $arRegion['LIST_STORES']);
		}
		if($isIndex)
		{
			$GLOBALS['arrPopularSections'] = array('UF_POPULAR' => 1);
			$GLOBALS['arrFrontElements'] = array('PROPERTY_SHOW_ON_INDEX_PAGE_VALUE' => 'Y');
		}
		
			$GLOBALS['arrFrontElementsvnutr'] = array('PROPERTY_SHOW_ON_INDEX_PAGE_VALUE' => 'Y');
		
		?>

		<div class="wraps hover_<?=$arTheme["HOVER_TYPE_IMG"]["VALUE"];?>" id="content"> 
			<?if(!$is404 && !$isForm && !$isIndex):?>
				<?$APPLICATION->ShowViewContent('section_bnr_content');?>
				<?if($APPLICATION->GetProperty("HIDETITLE") !== 'Y'):?>
					<!--title_content-->
					<?// Свой верх страницы: хлебные крошки по макету и без баннеров.
					   // Штатный ShowPageType('page_title') выбирает файл настройкой темы,
					   // а она одна на сайт — поэтому подключаем напрямую.?>
					<?include __DIR__.'/page_blocks/page_title_newdesign.php';?>
					<!--end-title_content-->
				<?endif;?>
				<?$APPLICATION->ShowViewContent('top_section_filter_content');?>
				


			<?endif;?>
	
			<?if(($isIndex)|| ($isProject)):?>
				<div class="wrapper_inner front <?=($isShowIndexLeftBlock ? "" : "wide_page");?>">
				
				<?elseif(!$isWidePage):?>
				<div class="wrapper_inner <?=($isHideLeftBlock ? "wide_page" : "");?>">
		
			<?endif;?>
					
		<?if( ($isIndex && $isShowIndexLeftBlock) || (!$isIndex && !$isHideLeftBlock) && !$isBlog):?>
				
				
					<div class="right_block <?=(defined("ERROR_404") ? "error_page" : "");?> wide_<?=CNext::ShowPageProps("HIDE_LEFT_BLOCK");?>">

							
				<?endif;?>
				
								
				
					<div class="middle <?=($is404 ? 'error-page' : '');?>">
					
						<?// Баннеры над контентом в новом дизайне пока не выводим (Ирина, 2026-07-31).
						   // Было: CNext::get_banners_position('CONTENT_TOP');?>
						<?if(!$isIndex):?>
							<div class="container airSticky_stop-block">
				
		<?//h1?>
								<?if($isHideLeftBlock && !$isWidePage):?>
									<div class="maxwidth-theme">

								<?endif;?>
								<?if ($isBlog):?>
									<?// В новом дизайне у блоговых страниц колонки идут наоборот:
									   // боковая слева, контент справа (макет «Материалы»).
									   // Порядок меняет CSS, разметку не трогаем — на неё завязаны
									   // и тема, и сам комплексный компонент.?>
									<?// H1 по макету идёт над обеими колонками, во всю ширину, и отбит чертой.
										   // Тема печатает его внутри правой колонки — выводим свой выше .row,
										   // а тот, что ниже, гасим стилями. Берём именно ShowTitle():
										   // GetTitle() здесь пуст — заголовок ставит компонент ниже
										   // по странице, а ShowTitle отложенный и подставится в конце.?>
										<div class="nd-blog-head">
											<h1 class="nd-blog-head__title"><?$APPLICATION->ShowTitle(false)?></h1>
										</div>
										<div class="row nd-blog-row" data-target="#myScrollspy">							
									<?$APPLICATION->ShowViewContent('under_sidebar_content');?>
									<div class="<? if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) != '/materials/') : ?>col-md-9 col-sm-9<?endif;?> col-xs-12 content-md <?=CNext::ShowPageProps("ERROR_404");?>">
									<?// H1 вынесен выше .row (nd-blog-head) — второй заголовок не выводим,
										   // иначе на странице два h1. Пустой #pagetitle оставляем:
										   // на него завязаны скрипты темы.?>
										<div id="pagetitle"></div>
									<?endif;?>
								<?endif;?>
				
					
								
								
								
						<?CNext::checkRestartBuffer();?>