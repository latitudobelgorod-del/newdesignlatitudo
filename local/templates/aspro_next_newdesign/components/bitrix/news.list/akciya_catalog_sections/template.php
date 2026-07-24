<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die()?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
 global $arFilterActiya;
?>

<?$this->setFrameMode(true);?>
<?if($arResult['ITEMS']):?>
	<?$isAjax = (isset($_GET["AJAX_REQUEST"]) && $_GET["AJAX_REQUEST"] == "Y");?>
	<?$isWideImg = (isset($arParams['IMAGE_WIDE']) && $arParams['IMAGE_WIDE'] == 'Y');?>
	<?if(!$isAjax):?>

<style>
/* ========== MOBILE SLIDER ========== */
@media (max-width: 767px) {

    .news-list-akciya.items.row {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
    }

    /* Обёртка — нейтральная, без отступов */
    .akciya-slider-wrap {
        position: relative;
    }

    /* Клипер — режет слайды */
    .akciya-slider-clip {
        overflow: hidden;
        margin: 0;
    }
    .akciya-slider-track {
        display: flex;
        transition: transform 0.35s ease;
        will-change: transform;
    }

    /* Каждый слайд — 100% враппера */
    .akciya-slider-track .col-md-3 {
        flex: 0 0 100%;
        width: 100%;
        max-width: 100%;
        padding: 0 !important;
        margin: 0 !important;
        box-sizing: border-box;
    }

    /* Картинка */
    .akciya-slider-track .col-md-3 picture,
    .akciya-slider-track .col-md-3 img {
        width: 100% !important;
        height: auto !important;
        display: block;
    }

    /* Точки — поверх картинки, от .akciya-slider-wrap */
    .akciya-slider-dots {
        position: absolute;
        bottom: 66px;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        pointer-events: none;
        z-index: 10;
    }

    .akciya-slider-dots button {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        border: none;
        background: rgba(0, 0, 0, 0.4);
        padding: 0;
        cursor: pointer;
        pointer-events: all;
        transition: background 0.2s, width 0.25s, border-radius 0.25s;
        outline: none;
        -webkit-appearance: none;
        flex-shrink: 0;
    }

    .akciya-slider-dots button.active {
        width: 20px;
        height: 7px;
        border-radius: 3.5px;
        background: #fff;
    }
}

@media (min-width: 768px) {
    .akciya-slider-dots {
        display: none !important;
    }
}
</style>

	<div class="section__wrap item-views list list-type-block wide_img image_left news item-views <?=($arParams['IMAGE_POSITION'] ? 'image_'.$arParams['IMAGE_POSITION'] : '')?> <?=($templateName = $component->{'__parent'}->{'__template'}->{'__name'})?>">

		<?
		$bHasSection = false;
		if($arParams['PARENT_SECTION'] && (isset($arResult['SECTIONS']) && $arResult['SECTIONS']))
		{
			if(isset($arResult['SECTIONS'][$arParams['PARENT_SECTION']]) && $arResult['SECTIONS'][$arParams['PARENT_SECTION']])
				$bHasSection = true;
		}
		if($bHasSection)
		{
			$arSectionButtons = CIBlock::GetPanelButtons($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 0, $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], array('SESSID' => false, 'CATALOG' => true));
			?>
			<div class="section" id="<?=$this->GetEditAreaId($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'])?>">
			<?
		}?>

		<div class="news-list-akciya items row">
			<div class="akciya-slider-wrap">
				<div class="akciya-slider-clip">
				<div class="akciya-slider-track">
	<?endif;?>
			<?
				$count=count($arResult['ITEMS']);
				$current=0;
			?>
			<?foreach($arResult['ITEMS'] as $i => $arItem):?>
				<?
				$current++;
				$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				$bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
				$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);
				$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
				$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']));
				$date_active_to = $arItem["DISPLAY_ACTIVE_TO"];
				?>

<div class="col-md-3">
	<div class="news-list__item-wrap" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
		<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="news-list__item">
			<div>
				<?if($imageSrc):?>
				<picture class="loaded">
					<img width="280" height="183" src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" style="display:block;width:100%;height:100%;object-fit:cover;" />
				</picture>
				<?else:?>
				<div style="width:280px;height:183px;background:url(/images/no_photo_medium.png);background-size:contain;background-repeat:no-repeat;background-position:center;"></div>
				<?endif;?>
			</div>
			<div class="news-list__item-date">
				<?$convers1 = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
				$date_activeto = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
				?>
				<?if($bActiveDate):?>
					<?if($arItem["DATE_ACTIVE_TO"]):?>
						<?if(($date_activeto) > ($time_now)):?>
						<div class="akciya_metka"><div>Акция до <?=$convers1?></div></div>
						<?else:?>
						<div class="akciya_metka"><div>Акция завершена</div></div>
						<?endif;?>
					<?endif;?>
				<?endif;?>
			</div>
		</a>
	</div>
</div>

			<?endforeach;?>
		<?if(!$isAjax):?>
				</div><?/* /akciya-slider-track */?>
				</div><?/* /akciya-slider-clip */?>
				<div class="akciya-slider-dots" id="akciyaDots"></div>
			</div><?/* /akciya-slider-wrap */?>
		</div><?/* /news-list-akciya */?>

		<?if($bHasSection):?>
			</div>
		<?endif;?>
		<?endif;?>

	<?if(!$isAjax):?>
	</div>
	<?endif;?>

<script>
(function() {
    function initSlider() {
        if (window.innerWidth >= 768) return;

        var wrap = document.querySelector('.akciya-slider-wrap');
        var track = document.querySelector('.akciya-slider-track');
        var dotsContainer = document.getElementById('akciyaDots');
        if (!wrap || !track || !dotsContainer) return;

        var slides = track.querySelectorAll('.col-md-3');
        var total = slides.length;
        if (total <= 1) {
            dotsContainer.style.display = 'none';
            return;
        }

        var current = 0;
        var PADDING = 15;

        // Создаём точки
        dotsContainer.innerHTML = '';
        for (var i = 0; i < total; i++) {
            var btn = document.createElement('button');
            btn.setAttribute('aria-label', 'Слайд ' + (i + 1));
            if (i === 0) btn.classList.add('active');
            (function(idx) {
                btn.addEventListener('click', function() { goTo(idx); });
            })(i);
            dotsContainer.appendChild(btn);
        }

        function goTo(idx) {
            current = idx;
            var slideW = slides[0].offsetWidth;
            track.style.transform = 'translateX(-' + (slideW * current) + 'px)';
            var dots = dotsContainer.querySelectorAll('button');
            dots.forEach(function(d, i) {
                d.classList.toggle('active', i === current);
            });
        }

        // Touch-свайп
        var startX = 0;
        var isDragging = false;

        wrap.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });

        wrap.addEventListener('touchend', function(e) {
            if (!isDragging) return;
            isDragging = false;
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) {
                if (diff > 0 && current < total - 1) goTo(current + 1);
                else if (diff < 0 && current > 0) goTo(current - 1);
            }
        }, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSlider);
    } else {
        initSlider();
    }

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            var track = document.querySelector('.akciya-slider-track');
            if (window.innerWidth >= 768 && track) {
                track.style.transform = '';
            } else {
                initSlider();
            }
        }, 150);
    });
})();
</script>

<?endif;?>