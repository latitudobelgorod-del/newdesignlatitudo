<? /** @var $block array */ ?><?
$image = Sprint\Editor\Blocks\Image::getImage($block, array(
    'width' => 825,
    'height' => 600,
    'exact' => 0
));
?><? if ($image): ?>
    <div  style="text-align: center">
        <img itemprop="image" src="<?= $image['SRC'] ?>"  style="max-width:825px;" title="<?= $image['DESCRIPTION'] ?> alt="<?= $image['DESCRIPTION'] ?>"  ">
    </div>
<? endif; ?>