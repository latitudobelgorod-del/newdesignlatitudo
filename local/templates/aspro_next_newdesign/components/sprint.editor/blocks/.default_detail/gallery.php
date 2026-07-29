<?/** @var $block array */?><?
$images = Sprint\Editor\Blocks\Gallery::getImages($block, array(
    'width' => 400,
    'height' => 300,
    'exact' => 1,
), array(
    'width' => 1024,
    'height' => 768,
    'exact' => 0,
));
?>


<?if (!empty($images)):?>
<div class="sp-gallery j" itemscope itemtype="https://schema.org/ImageGallery">
    <ul class="sp-gallery-items">
        <?foreach ($images as $image):?>
        <li class="sp-gallery-item" itemprop="hasPart" itemscope itemtype="https://schema.org/ImageObject">
            <a itemprop="contentUrl" data-fancybox="gallery" data-caption="<?=$image['DESCRIPTION']?>"  class="sp-gallery-item-img-wrapper fancy fancybox" rel="media-gallery" href="<?=$image['DETAIL_SRC']?>">
                <img itemprop="image"  alt="<?=$image['DESCRIPTION']?>" src="<?=$image['SRC']?>" title="<?=$image['DESCRIPTION']?>" >
                 <meta itemprop="name" content="<?=$image['DESCRIPTION']?>" />
				 <div class="sp-gallery-item-text">
                    <div class="sp-gallery-item-text-content"><?=$image['DESCRIPTION']?></div>
                </div>
            </a>
        </li>
        <?endforeach;?>
    </ul>
</div>
<?endif;?>

