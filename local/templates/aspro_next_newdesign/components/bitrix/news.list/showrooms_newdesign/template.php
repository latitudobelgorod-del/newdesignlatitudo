<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * «Шоу-румы компании» на странице «О компании» (макет 21399:66000).
 *
 * Карточка: слева адрес, телефоны, почта, режим работы и адрес склада,
 * справа фото офиса с кнопкой видео. Города листаются стрелками справа
 * от заголовка, счётчик «01/05» — там же. Скрипт карусели в script.js.
 *
 * Данные — инфоблок контактов (ID 10), тот же, что у раздела /contacts/.
 * Порядок городов задаётся списком ID в вызове компонента.
 */
if (!$arResult['ITEMS']) {
	return;
}

$ndIco = SITE_TEMPLATE_PATH.'/images/newdesign/contacts/';
$ndTel = static function ($phone) {
	return 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
};
$ndTotal = count($arResult['ITEMS']);
?>
<section class="nd-co__sec nd-shr" data-nd-shr>
	<div class="nd-shr__head">
		<h2 class="nd-shr__title">Шоу-румы компании</h2>
		<?if($ndTotal > 1):?>
			<div class="nd-shr__nav">
				<span class="nd-shr__counter" data-nd-shr-counter>01/<?=str_pad($ndTotal, 2, '0', STR_PAD_LEFT)?></span>
				<button type="button" class="nd-shr__arrow" data-nd-shr-prev aria-label="Предыдущий шоу-рум">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="nd-shr__arrow" data-nd-shr-next aria-label="Следующий шоу-рум">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			</div>
		<?endif;?>
	</div>

	<p class="nd-shr__lead">Есть в Москве, Воронеже, Белгороде, Ростове-на-Дону и Краснодаре. В этих регионах мы предлагаем расчет по вашим размерам, отгрузку со склада, доставку.</p>

	<div class="nd-shr__viewport">
		<div class="nd-shr__track" data-nd-shr-track>
			<?foreach($arResult['ITEMS'] as $arItem):?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

				$props = $arItem['PROPERTIES'];
				$phones = array_filter((array) ($props['PHONE']['VALUE'] ?? array()));
				$email = $props['EMAIL']['VALUE'] ?? '';
				$address = $props['ADDRESS']['VALUE'] ?? '';
				$schedule = $props['SCHEDULE']['VALUE']['TEXT'] ?? '';
				$store = $props['ADDRESS_SKLAD']['VALUE'] ?? '';
				$video = trim((string) ($props['VIDEO_OFFICE']['VALUE'] ?? ''));
				$city = $arItem['ND_CITY'] ?: $arItem['NAME'];
				?>
				<article class="nd-shr__slide" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
					<div class="nd-shr__info">
						<h3 class="nd-shr__city"><?=$city?></h3>

						<?if($address):?>
							<div class="nd-shr__row nd-shr__row--address">
								<img class="nd-shr__ico" src="<?=$ndIco?>pin.svg" alt="" width="20" height="20">
								<span><?=html_entity_decode($address)?></span>
							</div>
						<?endif;?>

						<?if($phones || $email):?>
							<div class="nd-shr__contacts">
								<?foreach($phones as $phone):?>
									<a class="nd-shr__row" href="<?=$ndTel($phone)?>" rel="nofollow">
										<img class="nd-shr__ico" src="<?=$ndIco?>phone.svg" alt="" width="20" height="20">
										<span><?=$phone?></span>
									</a>
								<?endforeach;?>
								<?if($email):?>
									<a class="nd-shr__row" href="mailto:<?=$email?>">
										<img class="nd-shr__ico" src="<?=$ndIco?>mail.svg" alt="" width="20" height="20">
										<span><?=$email?></span>
									</a>
								<?endif;?>
							</div>
						<?endif;?>

						<?if($schedule):?>
							<div class="nd-shr__row nd-shr__row--schedule">
								<img class="nd-shr__ico" src="<?=$ndIco?>clock.svg" alt="" width="20" height="20">
								<span><?=htmlspecialcharsBack($schedule)?></span>
							</div>
						<?endif;?>

						<?if($store):?>
							<div class="nd-shr__store">
								<div class="nd-shr__row">
									<img class="nd-shr__ico" src="<?=$ndIco?>store.svg" alt="" width="20" height="20">
									<span><b>Адрес склада</b><br><?=html_entity_decode($store)?></span>
								</div>
							</div>
						<?endif;?>
					</div>

					<div class="nd-shr__media">
						<?if($arItem['ND_PHOTO']):?>
							<img class="nd-shr__photo" src="<?=$arItem['ND_PHOTO']?>" alt="Шоу-рум Латитудо <?=$city?>" loading="lazy">
						<?endif;?>
						<?if($video):?>
							<a class="nd-shr__video fancy" href="<?=$video?>" rel="shr-video-<?=$arItem['ID']?>" title="Видео офиса <?=$city?>">
								<img src="<?=$ndIco?>play.svg" alt="" width="20" height="20">
								<span>Видео офиса</span>
							</a>
						<?endif;?>
					</div>
				</article>
			<?endforeach;?>
		</div>
	</div>
</section>
