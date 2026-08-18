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
 */
$this->setFrameMode(true);
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
										<?$APPLICATION->IncludeComponent(
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
										);?>
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
