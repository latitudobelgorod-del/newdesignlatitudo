<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die()?>
<?$this->setFrameMode(true);?>

<?/* Акции раздела по новому макету: узкая колонка справа от плиток
	подразделов, баннеры листаются точками в левом нижнем углу.
	Разметка одна и та же на всех разрешениях — на мобильном колонка
	просто встаёт под сетку (см. .nd-subsec-row в newdesign.css). */?>

<?if($arResult['ITEMS']):?>
	<?$sliderID = 'nd-akc-'.$this->randString();?>
	<div class="nd-akc" id="<?=$sliderID?>">
		<div class="nd-akc__viewport">
			<div class="nd-akc__track">
				<?foreach($arResult['ITEMS'] as $arItem):?>
					<?
					$imageSrc = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
					$alt = ($arItem['FIELDS']['PREVIEW_PICTURE']['ALT'] ? $arItem['FIELDS']['PREVIEW_PICTURE']['ALT'] : $arItem['NAME']);
					$title = ($arItem['FIELDS']['PREVIEW_PICTURE']['TITLE'] ? $arItem['FIELDS']['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME']);
					?>
					<a class="nd-akc__slide" href="<?=$arItem['DETAIL_PAGE_URL']?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<?if($imageSrc):?>
							<img src="<?=$imageSrc?>" alt="<?=$alt?>" title="<?=$title?>" loading="lazy" />
						<?else:?>
							<span class="nd-akc__name"><?=$arItem['NAME']?></span>
						<?endif;?>
					</a>
				<?endforeach;?>
			</div>
		</div>
		<div class="nd-akc__dots"></div>
	</div>

	<script>
	(function(){
		var root = document.getElementById('<?=$sliderID?>');
		if(!root || root.getAttribute('data-nd-akc-init')) return;
		root.setAttribute('data-nd-akc-init', 'Y');

		var track = root.querySelector('.nd-akc__track'),
			dotsBox = root.querySelector('.nd-akc__dots'),
			slides = track ? track.querySelectorAll('.nd-akc__slide') : [],
			total = slides.length,
			current = 0;

		if(total < 2){
			if(dotsBox) dotsBox.style.display = 'none';
			return;
		}

		for(var i = 0; i < total; i++){
			(function(idx){
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.setAttribute('aria-label', 'Слайд ' + (idx + 1));
				if(!idx) dot.className = 'active';
				dot.addEventListener('click', function(e){ e.preventDefault(); goTo(idx); });
				dotsBox.appendChild(dot);
			})(i);
		}

		function goTo(idx){
			current = Math.max(0, Math.min(idx, total - 1));
			track.style.transform = 'translateX(-' + (current * 100) + '%)';
			var dots = dotsBox.querySelectorAll('button');
			for(var i = 0; i < dots.length; i++){
				if(i === current) dots[i].classList.add('active');
				else dots[i].classList.remove('active');
			}
		}

		/* Свайп: если палец сдвинулся заметно — листаем и гасим переход
		   по ссылке, иначе тап по баннеру перестанет открывать акцию. */
		var startX = 0, moved = false;
		root.addEventListener('touchstart', function(e){
			startX = e.touches[0].clientX;
			moved = false;
		}, {passive: true});
		root.addEventListener('touchmove', function(e){
			if(Math.abs(e.touches[0].clientX - startX) > 10) moved = true;
		}, {passive: true});
		root.addEventListener('touchend', function(e){
			if(!moved) return;
			var diff = startX - e.changedTouches[0].clientX;
			if(Math.abs(diff) > 40) goTo(current + (diff > 0 ? 1 : -1));
		}, {passive: true});
		root.addEventListener('click', function(e){
			if(moved){ e.preventDefault(); moved = false; }
		});
	})();
	</script>
<?endif;?>
