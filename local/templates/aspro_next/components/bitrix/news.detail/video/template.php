<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<?$this->setFrameMode(true);?>	
<?use \Bitrix\Main\Localization\Loc;?>

<?$GLOBALS['ID'] = $arResult['ID'];?>

<div class="item video-blocks projects-blocks">
	<h1 id="pagetitle"><?$APPLICATION->ShowTitle(false)?></h1>
	
	<div class="head-block<?=($arResult['GALLERY'] ? '' : ' wti')?>">

		
		
<?$url = $arResult['PROPERTIES']['LINK_VIDEO']['VALUE'];
$photo = CFile::GetPath($arResult["PREVIEW_PICTURE"]["SRC"]);  
$parsed_url = parse_url($url);
parse_str($parsed_url['query'], $parsed_query);
global $sswlka;
$sswlka = $parsed_query['v'];

?>


			<? if ($arResult['PROPERTIES']['LINK_VIDEO']['VALUE']): ?>
			<?
if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
    $video_id = $match[1];

}
?>



<div style="overflow:hidden;">

<div class="c-video"><iframe  src="https://www.youtube.com/embed/<?=$video_id?>" width="1280" height="720" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>
<div class="playvideo">  <a  title="Подпишитесь на наш канал" target="_blank" class="personal-link dark-color"href="https://www.youtube.com/channel/UCRgn9WlVgrp3W2hRxEw6AwQ?sub_confirmation=1" >
<div class="text">Подпишитесь на наш канал</div>
<img src="/images/yt_video.png">
</a>
				</div>
 <? endif; ?>
 
	
		
	</div>
</div>
















<?if($arResult['PROPERTIES']['LINK_VIDEO']['VALUE']):
		$arSelect = Array("ID", "NAME", "PROPERTIES_LINK_VIDEO","PREVIEW_PICTURE");
		$res = CIBlockElement::GetList(Array("ID"=>"ASC"), Array("ID"=>$arResult['PROPERTIES']['LINK_VIDEO']['VALUE'], "IBLOCK_ID" => 23, ), false, false, Array("NAME", "PROPERTY_LINK_VIDEO", "PREVIEW_PICTURE") );
		$home = array();
		while($ob = $res->GetNextElement()) 
		{
			$arr = $ob->GetFields();
			$home[$arr['ID']] = $arr;
		}

?>

	<? foreach ($home as $vid): ?>
		<?
		$url = $vid["PROPERTY_LINK_VIDEO_VALUE"];
		$photo = CFile::GetPath($vid["PREVIEW_PICTURE"]);    
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
		$video_id = $match[1];
		}
		?>
		<div class="size_video" >
		<a href='https://www.youtube.com/embed/<?=$video_id?>'  class="gallery" rel="group">
       <div class="preview_pic"><img src="<?=$photo?>"  class="img-responsive" /></div>
        </a>
   	</div>
	<? endforeach; ?>
	
		<? endif; ?>












	