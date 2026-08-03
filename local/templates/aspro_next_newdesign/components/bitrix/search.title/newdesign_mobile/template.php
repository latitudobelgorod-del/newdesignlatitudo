<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Поле поиска в мобильной панели «Меню» нового дизайна.
 *
 * Копия шаблона newdesign (тот, в свою очередь, копия аспровского «corp»):
 * подсказки, ajax.php, result_modifier.php и script.js взяты оттуда без
 * изменений, переписана только вёрстка строки поиска — в макете
 * (Figma, узел 20506:103302) это строка без рамки: иконка 24, тонкая
 * вертикальная черта-курсор и подпись «Найти товар» 18/25.2.
 */
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(!strlen($INPUT_ID))
	$INPUT_ID = "nd-msearch-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(!strlen($CONTAINER_ID))
	$CONTAINER_ID = "nd-msearch";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);
?>
<div class="nd-msearch" id="<?=$CONTAINER_ID?>">
	<form class="nd-msearch__form" action="<?=$arResult["FORM_ACTION"]?>">
		<button class="nd-msearch__submit" type="submit" name="s" aria-label="Найти">
			<i class="nd-ico" style="-webkit-mask-image:url('<?=SITE_TEMPLATE_PATH?>/images/newdesign/mobile/search24.svg');mask-image:url('<?=SITE_TEMPLATE_PATH?>/images/newdesign/mobile/search24.svg')"></i>
		</button>
		<input class="nd-msearch__input"
		       id="<?=$INPUT_ID?>"
		       type="text"
		       name="q"
		       value=""
		       placeholder="Найти товар"
		       maxlength="50"
		       autocomplete="off">
	</form>
</div>
<script type="text/javascript">
	new JCTitleSearch2({
		'AJAX_PAGE'    : '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>',
		'CONTAINER_ID' : '<?=$CONTAINER_ID?>',
		'INPUT_ID'     : '<?=$INPUT_ID?>',
		'INPUT_ID_TMP' : '<?=$INPUT_ID?>',
		'MIN_QUERY_LEN': 2
	});
</script>
