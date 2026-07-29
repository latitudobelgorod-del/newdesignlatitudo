
<?/** @var $block array */?><?

$text = Sprint\Editor\Blocks\Text::getValue($block['text']);
$video = Sprint\Editor\Blocks\Text::getValue($block['text']);
$image = Sprint\Editor\Blocks\Image::getImage($block['image'], array(
    'width' => 320,
    'height' => 240,
    'exact' => 0
));
?>

<div class="c-image-text row">
    <?if ($image):?>
    <div class="col-sm-5">
        <img itemprop="image" alt="<?=$image['DESCRIPTION']?>"  src="<?=$image['SRC']?>" class="img-responsive">
    </div>
    <?endif;?>
    <div class="col-sm-7">
         <a href="<?=$video?>"><?=$text?></a>
    </div>
  
</div>
