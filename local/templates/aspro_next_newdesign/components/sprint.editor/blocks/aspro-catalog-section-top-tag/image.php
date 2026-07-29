<? /** @var $block array */ ?><?
$image = Sprint\Editor\Blocks\Image::getImage($block, array(
    'width' => 1200,
    'height' => 768,
    'exact' => 0
));
$renderImg = Sprint\Editor\Blocks\Image::getImage($block, array(
    'width' => 120,
    'height' => 100,
    'exact' => 0
));
?>


<? if ($image): ?>
    <div class="sp-image" style="text-align: center">
        <img itemprop="image" alt="<?=$image['DESCRIPTION'] ?>"  src="<?=$image['SRC'] ?>" class="img-responsive" title="<?=$image['DESCRIPTION'] ?>">
    </div>
<? endif; ?>




