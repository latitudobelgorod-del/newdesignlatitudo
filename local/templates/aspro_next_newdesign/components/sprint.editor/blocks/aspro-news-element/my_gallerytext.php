<?/** @var $block array */?><?
$images = Sprint\Editor\Blocks\Gallery::getImages($block, array(
    'width' => 450,
    'height' => 638,
    'exact' => 1,
), array(
    'width' => 1024,
    'height' => 1451,
    'exact' => 0,
));
?>

<?if (!empty($images)):?>
<div class="sp-gallerytext">
		<?foreach(array_chunk($images ,4) as $imagessp):?>
	 <div class="sp-gallery-items row flexbox " >
        <?foreach ($imagessp as $image):?>
		<div class="col-md-3 ">
<div class="sp-gallerytext-item">
		<div class="image">
				<a data-fancybox="gallery" data-caption="<?=$image['DESCRIPTION']?>"  class="sp-gallery-item-img-wrapper fancy fancybox" rel="media-gallery" href="<?=$image['DETAIL_SRC']?>">
				  <img  class="img-responsive" alt="<?=$image['DESCRIPTION']?>"  src="<?=$image['SRC']?>" title="<?=$image['DESCRIPTION']?>" >
				</a>
		</div>
		
 <div class="text"><?=$image['DESCRIPTION']?></div>                                                    
		</div></div>
        <?endforeach;?> 
		</div>
		<?endforeach;?>
    
	</div>
<?endif;?>

