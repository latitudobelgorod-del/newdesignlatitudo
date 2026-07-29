<?/** @var $block array */?><?

$text = Sprint\Editor\Blocks\Text::getValue($block['text']);
$image = Sprint\Editor\Blocks\Image::getImage($block['image'], array(
    'width' => 320,
    'height' => 240,
    'exact' => 0
));
$address_image_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST'] : 'http://'. $_SERVER['HTTP_HOST'];
$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'] : 'http://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
?>

<div class="c-image-text row">
    <?if ($image):?>
    <div class="col-sm-5" itemprop="hasPart" itemscope itemtype="https://schema.org/ImageObject">
	 <meta itemprop="name" content="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" />
		<meta itemprop="url" content="<?=$page_url_itemprop?>" />
        <img itemprop="contentUrl"  alt="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" title="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>"
		src="<?=$address_image_itemprop?><?=$image['SRC']?>" class="img-responsive">
    </div>
    <?endif;?>
    <div class="col-sm-7">
         <?=$text?>
    </div>
</div>
