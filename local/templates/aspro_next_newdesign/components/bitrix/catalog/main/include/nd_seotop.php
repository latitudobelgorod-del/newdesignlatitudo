<?php
/* Верхний SEO-текст списка (раздел каталога и посадочная), свёрнутый шторкой
   «Показать все». На входе $ndSeoTop — готовый html; пустой печатается как есть.
   Вынесено из page_blocks/list_elements_1.php, чтобы посадочные сворачивались
   так же, как разделы (Ирина, 27.09.2026). */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$ndSeoTop = isset($ndSeoTop) ? trim((string)$ndSeoTop) : '';
?>
<?if ($ndSeoTop !== '' && trim(strip_tags($ndSeoTop, '<img>')) !== ''):?>
	<style>
		.nd-seotop{position:relative;margin:0 0 24px}
		.nd-seotop.is-collapsed{max-height:130px;overflow:hidden}
		.nd-seotop__more{display:flex;align-items:flex-end;gap:4px;margin:16px 0 0;padding:0;background:none;border:0;color:#c60000;font-size:16px;line-height:24px;font-weight:500;cursor:pointer}
		.nd-seotop__more[hidden]{display:none}
		.nd-seotop.is-collapsed .nd-seotop__more{position:absolute;left:0;right:0;bottom:0;height:64px;margin:0;background:linear-gradient(180deg,rgba(255,255,255,0) 0%,#fff 80%)}
		.nd-seotop__more svg{flex:0 0 auto;margin-bottom:3px;transform:rotate(180deg);transition:transform .2s}
		.nd-seotop.is-collapsed .nd-seotop__more svg{transform:none}
		@media (max-width:767px){.nd-seotop.is-collapsed{max-height:104px}}
	</style>
	<div class="nd-seotop">
		<?=$ndSeoTop?>
		<button type="button" class="nd-seotop__more" hidden>
			<span class="nd-seotop__more-text">Показать все</span>
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
	<script>
	(function () {
		var box = document.querySelector('.nd-seotop');
		var btn = box && box.querySelector('.nd-seotop__more');
		if (!btn) return;
		var txt = btn.querySelector('.nd-seotop__more-text');
		/* Сколько показывать свёрнутым: если текст начинается с картинки —
		   её целиком и ещё пару строк (иначе на компьютере в 260px влезал
		   только кусок фото и было непонятно, что ниже текст); без картинки —
		   130 на компьютере и 104 на телефоне (Ирина, 25.09.2026: «сверни
		   побольше, ещё в половину» — было 260/208, как у описания бренда). */
		function limit() {
			var mob = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
			var img = box.querySelector('img');
			/* Картинка сбоку от текста (две колонки, как у Воронежа) — не
			   «начало»: иначе показывали её всю, и высокий текст не сворачивался. */
			if (img && img.offsetHeight > 40 && img.offsetWidth > box.offsetWidth * 0.6) {
				var top = img.getBoundingClientRect().top - box.getBoundingClientRect().top;
				if (top < 80) return Math.round(top + img.offsetHeight + (mob ? 72 : 96));
			}
			return mob ? 104 : 130;
		}
		function fit() {
			if (box.getAttribute('data-nd-open') === 'Y') return;
			var lim = limit();
			var tall = box.scrollHeight > lim + 96;
			box.style.maxHeight = tall ? lim + 'px' : '';
			box.classList.toggle('is-collapsed', tall);
			btn.hidden = !tall;
		}
		btn.addEventListener('click', function () {
			var open = box.classList.contains('is-collapsed');
			box.classList.toggle('is-collapsed', !open);
			box.style.maxHeight = open ? '' : limit() + 'px';
			box.setAttribute('data-nd-open', open ? 'Y' : 'N');
			txt.textContent = open ? 'Свернуть' : 'Показать все';
			if (!open) box.scrollIntoView({block: 'nearest'});
		});
		fit();
		/* картинки в тексте догружаются позже — высота растёт */
		window.addEventListener('load', fit);
		[].forEach.call(box.querySelectorAll('img'), function (i) { if (!i.complete) i.addEventListener('load', fit); });
	})();
	</script>
<?else:?>
	<?=$ndSeoTop?>
<?endif;?>
