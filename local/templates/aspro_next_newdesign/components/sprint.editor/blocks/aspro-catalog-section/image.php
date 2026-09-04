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
$address_image_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST'] : 'http://'. $_SERVER['HTTP_HOST'];
$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'] : 'http://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
?>


<? if ($image): ?>
    <div class="sp-image" style="text-align: center"  itemscope itemtype="https://schema.org/ImageObject">
        <meta itemprop="name" content="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" />
		<link itemprop="url" href="<?=$page_url_itemprop?>" />
	<img itemprop="contentUrl"  src="<?=$image['SRC'] ?>" class="img-responsive" alt="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" title="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>">
    </div>
<? endif; ?>




