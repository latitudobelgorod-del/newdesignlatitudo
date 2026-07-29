<?php /** @var $block array */ ?><?php

use Sprint\Editor\Tools\Image as ImageTools;

?>
<?php if (!empty($block['images'])) { ?>
    <div class="top_slider_wrapp editslide maxwidth-banner">
        <div class="flexslider">
            <ul class="slides">
                <?php foreach ($block['images'] as $blockImage) {
                    $image = ImageTools::resizeImage2($blockImage['file']['ID'], [
                        'width'  => 1024,
                        'height' => 768,
                        'exact'  => 0,
                    ]);
                    $image['LINK'] = htmlspecialchars($blockImage['link']);
                    $image['DESCRIPTION'] = htmlspecialchars($blockImage['desc']);

                    ?>
                    <li class="box" style="background-image:url('<?= $image['SRC'] ?>') !important;">
                        <div class="wrapper_inner">
                            <?php if ($image['LINK']) { ?>
                                <p class="shadow" style="background:rgba(0,0,0,.6);padding: 10px 0;"><a style="color:#fff;font-size: 16px;text-decoration: none;font-weight: normal;" href="<?= $image['LINK'] ?>"><?= $image['DESCRIPTION'] ?></a></p>
                            <?php } else { ?>
                                <p><?= $image['DESCRIPTION'] ?></p>
                            <?php } ?>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
<?php } ?>

