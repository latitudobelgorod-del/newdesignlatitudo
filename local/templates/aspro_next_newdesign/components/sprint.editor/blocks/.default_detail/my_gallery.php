<?/** @var $block array */?><?
$images = Sprint\Editor\Blocks\Gallery::getImages($block, array(
   'width' => 1024,
    'height' => 768,
    'exact' => 0,
), array(

));
?>

<?if (!empty($images)):?>
<div class="top_slider_wrapp editslide maxwidth-banner">
<div class="flexslider" >
<ul class="slides">
<?foreach ($images as $image):?>
<li class="box" style="background-image:url('<?=$image['SRC']?>') !important;">
	<div class="wrapper_inner"><p><?=$image['DESCRIPTION']?></p></div>
</li>
<?endforeach;?>
</ul>
</div>
</div>
<?endif;?>


