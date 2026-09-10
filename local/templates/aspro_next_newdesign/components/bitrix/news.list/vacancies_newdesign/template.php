<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Список вакансий на /info/vakansii/ в новом дизайне: аккордеон,
 * сгруппированный по городам (разделам инфоблока «Вакансии»).
 *
 * Раньше страницу собирали руками в редакторе блоков: заголовок «Филиал
 * Москва» плюс блок «Инфоблоки. Элементы» с выбранными вакансиями, и так на
 * каждый город. Здесь то же самое строится само — появилась активная вакансия
 * в городе, появился и город.
 *
 * Тело пункта — «Редактор блоков 1» самой вакансии, выводим его компонентом
 * целиком: там же лежит и красная кнопка «Отправить резюме» (веб-форма RESUME),
 * поэтому своей кнопки не рисуем — иначе их было бы две.
 *
 * Стили — .nd-vac* в css/newdesign-vacancies.css, раскрытие — data-nd-vac-acc
 * в js/newdesign-vacancies.js (общие со страницей, шаблон их не дублирует).
 *
 * Галочка BUTTON_VIDEO («Добавить кнопку видео-презентация вакансии менеджера
 * по продажам») ставит справа от «Отправить резюме» вторую кнопку — видео
 * с Rutube во fancybox на ширину экрана. Ролик один на все такие вакансии,
 * поэтому адрес задан здесь, а не свойством.
 */
$this->setFrameMode(true);

// Адрес для встраивания, а не страница ролика: rutube.ru/video/<id>/ в iframe
// не открывается. Обычная ссылка — https://rutube.ru/video/c8ffcec1a26203378caaf9763c943fe0/
$sNdVacVideoSrc = 'https://rutube.ru/play/embed/c8ffcec1a26203378caaf9763c943fe0/';

/**
 * Ставит кнопку видео в один ряд с кнопкой «Отправить резюме».
 *
 * Кнопку резюме рисует блок редактора (sprint.editor, button_link.php) — это
 * последний блок в теле вакансии, обёрнутый в <div class="block">. Находим
 * последний такой блок и дописываем видео внутрь него, а блоку даём класс
 * ряда. Если контент-менеджер кнопку резюме убрал — видео встаёт отдельной
 * строкой под текстом.
 */
if (!function_exists('ndVacAddVideoBtn')) {
	function ndVacAddVideoBtn($sHtml, $sBtn)
	{
		$sRe = '~<div class="block">(\s*<span class="[^"]*\bnd-editor-btn\b[^"]*"[^>]*>.*?</span>\s*</span>\s*)</div>~s';
		if (preg_match_all($sRe, $sHtml, $arMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
			$arLast = end($arMatches);
			$sRow = '<div class="block nd-vac__btns">'.$arLast[1][0].$sBtn.'</div>';
			return substr_replace($sHtml, $sRow, $arLast[0][1], strlen($arLast[0][0]));
		}
		return $sHtml.'<div class="block nd-vac__btns">'.$sBtn.'</div>';
	}
}
?>
<?if($arResult['SECTIONS']):?>
	<div class="nd-vac__list">
		<?// Раскрытой в макете стоит только самая первая вакансия — счётчик
		   // сквозной по всем городам, а не по каждому.?>
		<?$iNdVacNum = 0;?>
		<?foreach($arResult['SECTIONS'] as $arSection):?>
			<div class="nd-vac__group">
				<?if(strlen($arSection['NAME'])):?>
					<h3 class="nd-vac__group-title">Филиал <?=$arSection['NAME']?></h3>
				<?endif;?>
				<div class="nd-vac__group-items">
					<?foreach($arSection['ITEMS'] as $arItem):?>
						<?
						// Кнопки правки элемента в режиме редактирования сайта —
						// чтобы вакансию можно было открыть прямо со страницы.
						$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
						$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

						$bNdVacOpen = ($iNdVacNum === 0);
						$iNdVacNum++;
						// Зарплата в макете стоит не в шапке, а первой плашкой в теле
						// вакансии, и всегда со знаком рубля: «руб.» из свойства меняем
						// на «₽», а если валюты в значении нет вовсе — дописываем.
						// Текстовые значения вроде «по договорённости» не трогаем.
						$sNdVacPay = trim($arItem['DISPLAY_PROPERTIES']['PAY']['VALUE']);
						if(strlen($sNdVacPay)){
							$sNdVacPay = trim(preg_replace('/\s*(?:руб(?:лей|ля)?\.?|₽)\s*$/ui', '', $sNdVacPay));
							if(preg_match('/\d/u', $sNdVacPay)){
								$sNdVacPay .= ' ₽';
							}
							$sNdVacPayText = 'Зарплата '.$sNdVacPay;
						}else{
							$sNdVacPayText = 'Зарплата по результатам собеседования';
						}
						?>
						<div class="nd-vac__acc<?=($bNdVacOpen ? ' is-open' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>" data-nd-vac-acc>
							<button class="nd-vac__acc-head" type="button" aria-expanded="<?=($bNdVacOpen ? 'true' : 'false')?>">
								<span class="nd-vac__acc-title">
									<span class="nd-vac__acc-name"><?=$arItem['NAME']?></span>
								</span>
								<svg class="nd-vac__acc-ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
									<path class="nd-vac__acc-ico-v" d="M12 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
								</svg>
							</button>
							<div class="nd-vac__acc-body">
								<div class="nd-vac__acc-inner">
									<div class="nd-vac__pay"><?=$sNdVacPayText?><br><span class="nd-vac__pay-note">(выплаты 2 раза в месяц без задержек)</span></div>
									<div class="nd-vac__editor">
										<?
										// Флажок — список с одним значением «да»: отмечен, если
										// значение вообще есть.
										$bNdVacVideo = !empty($arItem['PROPERTIES']['BUTTON_VIDEO']['VALUE']);
										// Тело вакансии перехватываем только ради кнопки видео,
										// остальные вакансии выводятся как раньше.
										if($bNdVacVideo){
											ob_start();
										}
										$APPLICATION->IncludeComponent(
											'sprint.editor:blocks',
											'.default',
											array(
												'ELEMENT_ID' => $arItem['ID'],
												'IBLOCK_ID' => $arItem['IBLOCK_ID'],
												'PROPERTY_CODE' => 'EDITOR1',
												'USE_JQUERY' => 'N',
												'USE_FANCYBOX' => 'N',
											),
											$component,
											array('HIDE_ICONS' => 'Y')
										);
										if($bNdVacVideo){
											// data-fancybox со своим именем: пустое значение
											// fancybox склеил бы в галерею со всеми картинками
											// страницы. preload выключен — размер окна задаёт CSS
											// (.nd-vac-video), а подгонять его под чужой iframe
											// fancybox всё равно не сможет. idleTime выключен:
											// иначе через 4 с панель с крестиком прячется до
											// движения мыши, а над видео (iframe) движение до
											// страницы не доходит — крестик пропадал бы совсем.
											ob_start();
											?><button class="nd-vac__btn nd-vac__btn--outline nd-vac__video-btn" type="button"
												data-fancybox="nd-vac-video-<?=$arItem['ID']?>" data-type="iframe"
												data-src="<?=htmlspecialcharsbx($sNdVacVideoSrc)?>"
												data-options='{"slideClass":"nd-vac-video","idleTime":false,"iframe":{"preload":false,"attr":{"scrolling":"no","allow":"autoplay; encrypted-media; fullscreen; picture-in-picture; clipboard-write"}}}'>
												<svg class="nd-vac__btn-ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
													<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
													<path d="M10.2 8.6v6.8l5.4-3.4-5.4-3.4Z" fill="currentColor"/>
												</svg>
												<span>Видео-презентация вакансии</span>
											</button><?
											$sNdVacVideoBtn = ob_get_clean();
											echo ndVacAddVideoBtn(ob_get_clean(), $sNdVacVideoBtn);
										}
										?>
									</div>
								</div>
							</div>
						</div>
					<?endforeach;?>
				</div>
			</div>
		<?endforeach;?>
	</div>
<?endif;?>
