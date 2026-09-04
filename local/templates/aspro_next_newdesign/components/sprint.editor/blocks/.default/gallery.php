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
$address_image_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST'] : 'http://'. $_SERVER['HTTP_HOST'];
$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'] : 'http://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
?>

<?// Подпись снимка. Своя подпись у картинки есть далеко не всегда, и тогда
   // на всю галерею уходит один и тот же alt — название страницы. Для
   // Яндекс.Картинок это слабый признак: пятнадцать разных фотографий
   // подписаны одинаково. Поэтому повторы нумеруем.
   //
   // Нумеруем только alt: title виден пользователю подсказкой, и «— фото 7»
   // в ней ни к чему.?>
<?$ndUsedAlts = array();?>
<?if (!empty($images)):?>
<div class="sp-gallery j">
    <ul class="sp-gallery-items">
        <?foreach ($images as $i => $image):?>
        <?
        $ndImgTitle = $image['DESCRIPTION'] ? : $this->arParams['NEWS_NAME'];
        $ndImgAlt = isset($ndUsedAlts[$ndImgTitle]) ? $ndImgTitle.' — фото '.($i + 1) : $ndImgTitle;
        $ndUsedAlts[$ndImgTitle] = true;
        ?>
        <li class="sp-gallery-item" itemscope itemtype="https://schema.org/ImageObject">
            <a itemprop="contentUrl" data-fancybox="gallery" data-caption="<?=$ndImgTitle?>"  class="sp-gallery-item-img-wrapper fancy fancybox" rel="media-gallery" 
			href="<?=$address_image_itemprop?><?=$image['DETAIL_SRC']?>">
  <meta itemprop="name" content="<?=$ndImgAlt?>" />
   <link itemprop="url" href="<?=$page_url_itemprop?>" />
  <img src="<?=$image['SRC']?>" title="<?=$ndImgTitle?>" alt="<?=$ndImgAlt?>" >
                <div class="sp-gallery-item-text">
                    <div class="sp-gallery-item-text-content"><?=$image['DESCRIPTION']?></div>
                </div>
            </a>
        </li>
        <?endforeach;?>
    </ul>
</div>
<?endif;?>

