<?php
// Картинка анонса из первой страницы PDF для 26798, 33618, 33619 (ИБ 13, 10.11.2026).
// php nd_set_previews.php <DOCUMENT_ROOT> dry|run|rollback   (у них картинки не было — откат = убрать)
$root = $argv[1]; $mode = $argv[2] ?? 'dry'; $_SERVER['DOCUMENT_ROOT'] = $root;
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true); define('NO_AGENT_CHECK', true);
require $root.'/bitrix/modules/main/include/prolog_before.php'; CModule::IncludeModule('iblock');
$el = new CIBlockElement;
foreach ([26798, 33618, 33619] as $id) {
	$cur = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => 13], false, false, ['ID', 'PREVIEW_PICTURE'])->Fetch();
	if ($mode === 'rollback') { $el->Update($id, ['PREVIEW_PICTURE' => ['del' => 'Y']]); echo "$id cleared\n"; continue; }
	if ($cur['PREVIEW_PICTURE']) { echo "$id already has picture, skip\n"; continue; }
	if ($mode !== 'run') { echo "$id would set\n"; continue; }
	$f = CFile::MakeFileArray(__DIR__."/prev/$id.jpg");
	echo $id, ' ', $el->Update($id, ['PREVIEW_PICTURE' => $f]) ? 'ok' : $el->LAST_ERROR, "\n";
}
CIBlock::clearIblockTagCache(13);
