<? /** @var $block array */ ?><?
$images = Sprint\Editor\Blocks\Gallery::getImages($block, array(
    'width' => 800,
    'height' => 600,
    'exact' => 0
));
?>
<div class="row galley" itemscope itemtype="https://schema.org/ImageGallery">
    <ul class="module-gallery-list" >
        <? foreach ($images as $image): ?>
            <li class="item_block" itemprop="hasPart" itemscope itemtype="https://schema.org/ImageObject">
                <a itemprop="contentUrl" href="<?= $image['ORIGIN_SRC'] ?>" class="fancy" data-fancybox-group="gallery">
                    <meta itemprop="name" content="<?=$image['DESCRIPTION']?>" />
                    <img itemprop="image" src="<?= $image['SRC'] ?>" alt="<?= $image['DESCRIPTION'] ?>" title="<?= $image['DESCRIPTION'] ?>">
                </a>
            </li>
        <? endforeach; ?>
    </ul>
    <div class="clear"></div>
</div>