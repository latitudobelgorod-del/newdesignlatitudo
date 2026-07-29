<?/** @var $block array */?><?

/*
$preview = Sprint\Editor\Blocks\Image::getImage($block['preview'], array(
    'width' => 1024,
    'height' => 768,
    'exact' => 0,
    //'jpg_quality' => 75
));
*/

$text = Sprint\Editor\Blocks\Text::getValue($block['text']);

$video = Sprint\Editor\Blocks\Video::getHtml($block['video'], array(
    'width' => 640,
    'height' => 480
));
$videoedit = CFile::ResizeImageGet($video, false);
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video, $match)) {
		$video_id = $match[1];
	}

?>

   <div class="size_video">
	   <div class="c-video" >  
		   <div class="youtube" id="<?=$video_id?>"> </div>
	   </div></div>

<div class="text-video"> <?=$text?>   </div>

