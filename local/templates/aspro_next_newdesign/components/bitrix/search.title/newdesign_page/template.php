<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * Подсказки для строки поиска на странице результатов (/catalog/?q=…).
 *
 * Разметки у шаблона нет: само поле печатает components/bitrix/catalog/main/
 * search.php — оно уходит в отложенную область над обеими колонками, поэтому
 * собрать его здесь нельзя. Отсюда только запуск JCTitleSearch2 на уже
 * существующие узлы; сам класс на странице есть, его подключает шаблон
 * newdesign в шапке.
 *
 * Подсказки, ajax.php и result_modifier.php — общие с шаблоном newdesign.
 */
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(!strlen($INPUT_ID))
	$INPUT_ID = "nd-searchpage-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(!strlen($CONTAINER_ID))
	$CONTAINER_ID = "nd-searchpage";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);
?>
<script type="text/javascript">
	new JCTitleSearch2({
		'AJAX_PAGE'    : '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>',
		'CONTAINER_ID' : '<?=$CONTAINER_ID?>',
		'INPUT_ID'     : '<?=$INPUT_ID?>',
		'INPUT_ID_TMP' : '<?=$INPUT_ID?>',
		'MIN_QUERY_LEN': 2
	});
</script>
