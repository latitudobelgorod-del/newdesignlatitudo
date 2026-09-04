<?/** @var $block array */?><?
$images = Sprint\Editor\Blocks\Gallery::getImages($block, array(
    'width' => 400,
    'height' => 300,
    'exact' => 0,
), array(
    'width' => 1024,
    'height' => 768,
    'exact' => 0,
));
$address_image_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST'] : 'http://'. $_SERVER['HTTP_HOST'];
$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'] : 'http://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];

?>

<?if (!empty($images)):?>
<div class="sp-gallery "itemscope itemtype="https://schema.org/ImageGallery" >
    <ul class="sp-gallery-items">
        <?foreach ($images as $image):?>
        <li class="sp-gallery-item"  itemscope itemtype="https://schema.org/ImageObject">
            <a itemprop="contentUrl" data-fancybox="gallery" data-caption="<?=$image['DESCRIPTION']?>"  
			class="sp-gallery-item-img-wrapper fancy fancybox" rel="media-gallery" href="<?=$address_image_itemprop?><?=$image['DETAIL_SRC']?>">
                 <meta itemprop="name" content="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" />
				<link itemprop="url" href="<?=$page_url_itemprop?>" />

				<img  alt="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>"  src="<?=$image['SRC']?>" title="<?=$image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'] ?>" >
                
                 <div class="sp-gallery-item-text">
                    <div class="sp-gallery-item-text-content"><?=$image['DESCRIPTION']?></div>
                </div>
            </a>
        </li>
        <?endforeach;?>
    </ul>
</div>
<?endif;?>






