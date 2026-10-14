<?php
/**
 * «Галерея наших объектов» в SEO-тексте раздела «Террасная доска из ДПК» (#98,
 * UF_EDITOR2_DEFAULT): 12 загруженных картинок с подписями → один блок
 * «Инфоблоки. Элементы» с проектами портфолио, вариант «Элемент со своей
 * подписью» (style4) — подписи те же, теперь это ссылки на проекты (Ирина, 14.10.2026).
 *
 *   php local/tools/nd_gallery_to_portfolio.php <DOCUMENT_ROOT> [--apply]
 *   php local/tools/nd_gallery_to_portfolio.php <DOCUMENT_ROOT> rollback <файл бэкапа>
 * Без --apply только показывает, что будет сделано. Бэкап поля — в домашнюю папку.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/bitrix')) {
	exit("usage: php nd_gallery_to_portfolio.php <DOCUMENT_ROOT> [--apply|rollback файл]\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const ND_IB = 19;
const ND_SECTION = 98;
const ND_FIELD = 'UF_EDITOR2_DEFAULT';
const ND_PORTFOLIO_IB = 18;
// файл картинки в редакторе → проект портфолио (найден сравнением изображений)
$map = [
	107117 => 25907, // терраса на крыше — «Террасы из ДПК для ночного клуба на эксплуатируемой крыше на 30 этаже»
	107120 => 24606, // беседка и садовые дорожки — «Восьмиугольная беседка из ДПК под заказ»
	107124 => 25778, // благоустройство пляжа — «Пляжные террасы в парковой зоне»
	107118 => 25193, // беседка — «Беседка из ДПК с гриль-зоной»
	107121 => 25808, // зона отдыха на эксплуатируемой кровле — «…на эксплуатируемой крыше с перголой из ДПК»
	107125 => 25724, // терраса ресторана в горах — «Терраса на "Курорте Газпром" в Сочи»
	107122 => 25489, // мост в парке — «Мосты из ДПК для парка»
	107123 => 24615, // терраса к дому — «Терраса к дому и отделка потолка из ДПК»
	107126 => 24706, // патио по проекту дизайнера — «Уютное патио по проекту дизайнера»
	107127 => 25214, // большая терраса коттеджа — «2 большие террасы на берегу пруда»
	107128 => 26448, // отделка набережной — «Материалы для строительства набережной»
	107129 => 25222, // подиум бассейна — «Изящный подиум вокруг бассейна»
];

$mode = $argv[2] ?? '';
$sec = new CIBlockSection;
if ($mode === 'rollback') {
	$old = file_get_contents($argv[3]);
	json_decode($old) !== null or exit("битый бэкап\n");
	$sec->Update(ND_SECTION, [ND_FIELD => $old]) or exit("ERR {$sec->LAST_ERROR}\n");
	CIBlock::clearIblockTagCache(ND_IB);
	exit("поле восстановлено\n");
}
$apply = $mode === '--apply';

$row = CIBlockSection::GetList([], ['IBLOCK_ID' => ND_IB, 'ID' => ND_SECTION], false, ['ID', ND_FIELD])->Fetch();
$raw = (string) $row[ND_FIELD];
$j = json_decode($raw, true);
if (!$j || empty($j['blocks'])) {
	exit("поле пустое или не JSON\n");
}

// где галерея: ряды, в которых только картинки и тексты-подписи, после заголовка «Галерея наших объектов»
$titleIdx = null;
foreach ($j['blocks'] as $i => $b) {
	if ($b['name'] === 'htag' && trim($b['value']) === 'Галерея наших объектов') {
		$titleIdx = $i;
	}
}
if ($titleIdx === null) {
	exit("не нашла заголовок «Галерея наших объектов»\n");
}
$titleLayout = (int) explode(',', $j['blocks'][$titleIdx]['layout'])[0];

$ids = [];
$captions = [];
$drop = [];
$dropLayouts = [];
$pendingFile = null;
foreach ($j['blocks'] as $i => $b) {
	$lay = (int) explode(',', $b['layout'])[0];
	if ($lay <= $titleLayout) {
		continue;
	}
	if ($b['name'] === 'image') {
		$pendingFile = (int) $b['file']['ID'];
	} elseif ($b['name'] === 'text' && $pendingFile) {
		if (!isset($map[$pendingFile])) {
			exit("нет проекта для картинки $pendingFile\n");
		}
		$ids[] = $map[$pendingFile];
		$captions[(string) $map[$pendingFile]] = trim(html_entity_decode(strip_tags($b['value']), ENT_QUOTES, 'UTF-8'));
		$pendingFile = null;
	} else {
		exit("в галерее неожиданный блок [$i] {$b['name']} — остановилась\n");
	}
	$drop[] = $i;
	$dropLayouts[$lay] = true;
}
if (!$ids || $pendingFile) {
	exit("не разобрала галерею\n");
}
// ряды галереи должны быть последними — иначе пришлось бы перенумеровать layout у блоков ниже
if (max(array_keys($dropLayouts)) !== count($j['layouts']) - 1 || min(array_keys($dropLayouts)) !== $titleLayout + 1) {
	exit("ряды галереи не в конце поля — остановилась\n");
}

$names = [];
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_PORTFOLIO_IB, 'ID' => $ids], false, false, ['ID', 'NAME', 'ACTIVE', 'DETAIL_PAGE_URL']);
while ($e = $r->GetNext()) {
	$names[$e['ID']] = "[{$e['ACTIVE']}] {$e['~NAME']}  {$e['DETAIL_PAGE_URL']}";
}
foreach ($ids as $id) {
	echo "  {$captions[$id]}  →  $id ".($names[$id] ?? 'НЕ НАЙДЕН')."\n";
	isset($names[$id]) or exit("проект $id не найден\n");
}
if (count(array_unique($ids)) !== count($ids)) {
	exit("один проект на две картинки — остановилась\n");
}
echo "убираю блоков: ".count($drop).", рядов: ".count($dropLayouts)."\n";
if (!$apply) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

foreach ($drop as $i) {
	unset($j['blocks'][$i]);
}
foreach (array_keys($dropLayouts) as $lay) {
	unset($j['layouts'][$lay]);
}
$new = [
	'iblock_id' => ND_PORTFOLIO_IB,
	'element_ids' => $ids,
	'captions' => $captions,
	'name' => 'iblock_elements',
	'settings' => ['param1' => 'style4'],
	'layout' => $titleLayout.',0',
	'meta' => [],
];
$blocks = array_values($j['blocks']);
array_splice($blocks, $titleIdx + 2, 0, [$new]); // после заголовка и вводной фразы
$j['blocks'] = $blocks;
$j['layouts'] = array_values($j['layouts']);

$bk = (getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT'])).'/nd_gallery_backup_'.ND_SECTION.'_'.date('Ymd-His').'.json';
file_put_contents($bk, $raw);
$sec->Update(ND_SECTION, [ND_FIELD => json_encode($j, JSON_UNESCAPED_UNICODE)]) or exit("ERR {$sec->LAST_ERROR}\n");
CIBlock::clearIblockTagCache(ND_IB);
echo "записано\nбэкап: $bk\n";
