<?/** @var $block array */?><?
$image = Sprint\Editor\Blocks\Image::getImage($block, array(
    'width' => 1200,
    'height' => 768,
    'exact' => 0,
    //'jpg_quality' => 75
));
?><?if ($image):?>
    <div class="sp-image"><img itemprop="image" alt="<?=$image['DESCRIPTION']?>" class="img-responsive" src="<?=$image['SRC']?>"></div>
<?endif;?>
