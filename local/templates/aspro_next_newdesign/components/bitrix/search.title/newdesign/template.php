<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Поле поиска в шапке нового дизайна.
 *
 * Копия шаблона aspro «corp» с разметкой по макету: подсказки, ajax.php,
 * result_modifier.php и script.js (класс JCTitleSearch2) взяты оттуда без
 * изменений, переписана только вёрстка самой строки поиска.
 */
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(!strlen($INPUT_ID))
	$INPUT_ID = "nd-title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(!strlen($CONTAINER_ID))
	$CONTAINER_ID = "nd-title-search";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);
?>
<div class="nd-search" id="<?=$CONTAINER_ID?>">
	<form class="nd-search__form" action="<?=$arResult["FORM_ACTION"]?>">
		<button class="nd-search__submit" type="submit" name="s" aria-label="Найти">
			<img src="<?=SITE_TEMPLATE_PATH?>/images/newdesign/header/search.svg" alt="" width="24" height="24">
		</button>
		<input class="nd-search__input"
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
