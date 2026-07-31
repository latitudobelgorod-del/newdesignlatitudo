<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * Соцсети в подвале нового дизайна — квадратные плашки 2×2.
 * Копия шаблона `vertical_group`: ссылки те же (настройки темы), RUTUBE
 * так же прибит в шаблоне — в настройках темы поля под него нет.
 * Иконки выгружены из макета, цвет в них зашит (белый), поэтому <img>.
 */
$this->setFrameMode(true);
$sIcons = SITE_TEMPLATE_PATH.'/images/newdesign/footer/';

$arSocial = array();
if(!empty($arResult['SOCIAL_VK']))
	$arSocial[] = array('URL' => $arResult['SOCIAL_VK'], 'ICON' => 'soc-vk.svg', 'TITLE' => 'Группа Вконтакте', 'GOAL' => 'VK');
if(!empty($arResult['SOCIAL_TELEGRAM']))
	$arSocial[] = array('URL' => $arResult['SOCIAL_TELEGRAM'], 'ICON' => 'soc-tg.svg', 'TITLE' => 'Канал в Telegram', 'GOAL' => 'TELEGRAM_CANAL');
if(!empty($arResult['SOCIAL_YOUTUBE']))
	$arSocial[] = array('URL' => $arResult['SOCIAL_YOUTUBE'], 'ICON' => 'soc-yt.svg', 'TITLE' => 'Канал в YouTube', 'GOAL' => '');
$arSocial[] = array('URL' => 'https://rutube.ru/channel/41631334/', 'ICON' => 'soc-rt.svg', 'TITLE' => 'Канал в RUTUBE', 'GOAL' => '');

if(!$arSocial) return;
?>
<!-- noindex -->
<div class="nd-fsoc">
	<?foreach($arSocial as $arItem):?>
		<a class="nd-fsoc__item" href="<?=$arItem['URL']?>" target="_blank" rel="nofollow" title="<?=$arItem['TITLE']?>" aria-label="<?=$arItem['TITLE']?>"<?if($arItem['GOAL']):?> onclick="ym(62259859,'reachGoal','<?=$arItem['GOAL']?>'); return true;"<?endif;?>>
			<img src="<?=$sIcons.$arItem['ICON']?>" alt="" width="27" height="27" loading="lazy">
		</a>
	<?endforeach;?>
</div>
<!-- /noindex -->
