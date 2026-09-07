<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/**
 * Горизонтальный умный фильтр — тот же ряд плашек, что в портфолио
 * (класс .nd-filter, стили в css/newdesign.css). Ставится НАД списком,
 * левая колонка на такой странице не нужна.
 *
 * Почему не ajax-шаблон темы (main_newdesign): тот рисует вертикальный
 * список групп и целиком завязан на скрипт smartFilter — счётчики, всплывающие
 * окна, авто-применение. Здесь, как и в портфолио, обычная GET-форма: выбрали
 * значения, нажали «Показать» — страница перезагрузилась. Меньше кода и
 * никаких гонок с ajax-пересчётом списка.
 *
 * Имена полей берём у компонента (CONTROL_NAME / CONTROL_ID), поэтому фильтр
 * применяется штатно. Скрытые поля из HIDDEN обязательны: на поиске в них
 * лежит сам запрос `q`, без них он потеряется при отправке формы.
 *
 * Ирина, 7 сентября 2026.
 */

/* Группы, которых на поиске быть не должно (Ирина, 7 сентября 2026).
   По коду свойства — надёжнее названия; «Наши предложения» кода не имеет,
   это виртуальный блок темы, поэтому он в списке названий.
   Код каждой группы виден в разметке в data-nd-code — так проще добавить
   сюда следующую, не залезая в базу. */
$ndSkipCodes = array(
	'USAGE_DOSKA_DPK',    // Применение
	'MATERIAL_DOSKA_DPK', // Материал ДПК
	'VID',                // Виды террасной доски из ДПК
);
$ndSkipNames = array('Наши предложения');

$ndChevron = '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';

/* Собираем только те группы, где есть что выбрать: у пустых плашка была бы
   с пустым выпадающим списком. Числовые группы (цена, вес и пр.) узнаём по
   наличию VALUES[MIN] — у них два поля «от» и «до». */
$ndGroups = array();

foreach ($arResult['ITEMS'] as $arItem)
{
	if (in_array($arItem['CODE'], $ndSkipCodes, true) || in_array($arItem['NAME'], $ndSkipNames, true))
		continue;

	if (isset($arItem['VALUES']['MIN']) && isset($arItem['VALUES']['MAX']))
	{
		$min = $arItem['VALUES']['MIN'];
		$max = $arItem['VALUES']['MAX'];

		/* Разброса нет — выбирать нечего. */
		if ($min['VALUE'] === '' || $max['VALUE'] === '' || $min['VALUE'] == $max['VALUE'])
			continue;

		$ndGroups[] = array(
			'TYPE' => 'RANGE',
			'FIELDS' => array($min['CONTROL_NAME'], $max['CONTROL_NAME']),
			'CODE' => $arItem['CODE'],
			'NAME' => $arItem['NAME'],
			'MIN' => $min,
			'MAX' => $max,
			'SELECTED' => (($min['HTML_VALUE'] !== '' && $min['HTML_VALUE'] !== null)
				|| ($max['HTML_VALUE'] !== '' && $max['HTML_VALUE'] !== null)) ? 1 : 0,
		);

		continue;
	}

	$values = array();
	$selected = 0;

	foreach ($arItem['VALUES'] as $ar)
	{
		if (!isset($ar['CONTROL_NAME']) || $ar['VALUE'] === '' || $ar['VALUE'] === null)
			continue;

		/* Значения, под которые товаров не осталось, не показываем: выбрать их
		   всё равно нельзя, а плашку они удлиняют. */
		if (!empty($ar['DISABLED']))
			continue;

		$values[] = $ar;

		if (!empty($ar['CHECKED']))
			$selected++;
	}

	if (!$values)
		continue;

	$ndFields = array();

	foreach ($values as $ar)
		$ndFields[] = $ar['CONTROL_NAME'];

	$ndGroups[] = array(
		'TYPE' => 'LIST',
		'FIELDS' => $ndFields,
		'CODE' => $arItem['CODE'],
		'NAME' => $arItem['NAME'],
		'VALUES' => $values,
		'SELECTED' => $selected,
	);
}

/* Адрес «снять только эту группу»: текущий запрос без её полей. Так же
   устроено на маркетплейсах — крестик на подсвеченной плашке (Ирина,
   7 сентября 2026). Строим на сервере, чтобы работало без скрипта. */
foreach ($ndGroups as $i => $ndGroup)
{
	if (!$ndGroup['SELECTED'])
		continue;

	$ndQuery = $_GET;

	foreach ($ndGroup['FIELDS'] as $ndField)
		unset($ndQuery[$ndField]);

	unset($ndQuery['del_filter']);
	$ndQuery['set_filter'] = 'Y';

	$ndGroups[$i]['CLEAR_URL'] = $APPLICATION->GetCurPage(false).'?'.http_build_query($ndQuery);
}

if (!$ndGroups)
	return;
?>
<form class="nd-filter nd-filter--horizontal smartfilter"
      name="<?=$arResult["FILTER_NAME"]."_form"?>"
      action="<?=$arResult["FORM_ACTION"]?>"
      method="get">

	<?foreach($arResult["HIDDEN"] as $arHidden):?>
		<input type="hidden" name="<?=$arHidden["CONTROL_NAME"]?>" value="<?=$arHidden["HTML_VALUE"]?>" />
	<?endforeach;?>

	<?foreach($ndGroups as $ndGroup):?>
		<details class="nd-filter__drop<?=$ndGroup['SELECTED'] ? ' is-selected' : ''?>" data-nd-code="<?=htmlspecialcharsbx($ndGroup['CODE'])?>">
			<summary class="nd-filter__head">
				<?=$ndChevron?><span><?=htmlspecialcharsbx($ndGroup['NAME'])?><?=$ndGroup['SELECTED'] ? ' ('.$ndGroup['SELECTED'].')' : ''?></span>
				<?if(!empty($ndGroup['CLEAR_URL'])):?>
					<?/* Останавливаем всплытие: иначе клик по крестику заодно
					     раскрывал бы список — событие дошло бы до summary. */?>
					<a class="nd-filter__clear" href="<?=htmlspecialcharsbx($ndGroup['CLEAR_URL'])?>"
					   title="Снять этот фильтр" aria-label="Снять фильтр «<?=htmlspecialcharsbx($ndGroup['NAME'])?>»"
					   onclick="event.stopPropagation();">&times;</a>
				<?endif;?>
			</summary>
			<div class="nd-filter__panel">
				<?if($ndGroup['TYPE'] === 'RANGE'):?>
					<div class="nd-filter__range">
						<input class="nd-filter__num" type="text" inputmode="numeric"
						       name="<?=$ndGroup['MIN']["CONTROL_NAME"]?>"
						       id="<?=$ndGroup['MIN']["CONTROL_ID"]?>"
						       value="<?=$ndGroup['MIN']["HTML_VALUE"]?>"
						       placeholder="от <?=$ndGroup['MIN']["VALUE"]?>" />
						<input class="nd-filter__num" type="text" inputmode="numeric"
						       name="<?=$ndGroup['MAX']["CONTROL_NAME"]?>"
						       id="<?=$ndGroup['MAX']["CONTROL_ID"]?>"
						       value="<?=$ndGroup['MAX']["HTML_VALUE"]?>"
						       placeholder="до <?=$ndGroup['MAX']["VALUE"]?>" />
					</div>
				<?else:?>
					<?foreach($ndGroup['VALUES'] as $ar):?>
						<label class="nd-filter__opt">
							<input type="checkbox"
							       name="<?=$ar["CONTROL_NAME"]?>"
							       id="<?=$ar["CONTROL_ID"]?>"
							       value="<?=$ar["HTML_VALUE"]?>"<?=!empty($ar["CHECKED"]) ? ' checked' : ''?>>
							<span class="nd-filter__box" aria-hidden="true"></span>
							<span class="nd-filter__opt-name"><?=htmlspecialcharsbx($ar["VALUE"])?></span>
							<?if(isset($ar["ELEMENT_COUNT"]) && $ar["ELEMENT_COUNT"] !== ''):?>
								<span class="nd-filter__opt-count"><?=(int)$ar["ELEMENT_COUNT"]?></span>
							<?endif;?>
						</label>
					<?endforeach;?>
				<?endif;?>
				<button type="submit" name="set_filter" value="Y" class="nd-filter__apply">Показать</button>
			</div>
		</details>
	<?endforeach;?>

	<?/* Сброс — обычная кнопка отправки: компонент понимает del_filter в
	     запросе, а скрытые поля сохраняют поисковый запрос. */?>
	<button type="submit" name="del_filter" value="Y" class="nd-filter__reset">Сбросить фильтры</button>
</form>
