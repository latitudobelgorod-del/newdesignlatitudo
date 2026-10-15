<?php
/**
 * Группа «Тестирование сравнения» (STRING_ID ND_COMPARE_TEST) для скрытого
 * теста сравнения товаров (15.10.2026) и добавление в неё пользователя.
 * Прав группа не даёт никаких — по ней только показывается сравнение
 * (ndCompareAllowed, local/php_interface/include/latitudo_compare.php).
 *
 *   php nd_compare_group.php <DOCROOT> <email или логин> [--apply]
 *   php nd_compare_group.php <DOCROOT> rollback   — удалить группу (и членство в ней)
 * Без --apply — только показывает.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

const ND_GROUP = 'ND_COMPARE_TEST';

$group = CGroup::GetList('id', 'asc', ['STRING_ID' => ND_GROUP])->Fetch();

if (($argv[2] ?? '') === 'rollback') {
	if (!$group) {
		exit("группы нет\n");
	}
	CGroup::Delete((int)$group['ID']);
	exit("группа {$group['ID']} удалена\n");
}

$who = (string)($argv[2] ?? '');
$user = CUser::GetList('id', 'asc', ['=EMAIL' => $who])->Fetch()
	?: CUser::GetByLogin($who)->Fetch();
if (!$user) {
	exit("пользователь «{$who}» не найден\n");
}
echo "пользователь: {$user['ID']} {$user['LOGIN']} {$user['EMAIL']}\n";
echo 'группа: ' . ($group ? "есть, ID {$group['ID']}" : 'нет — будет создана') . "\n";
if (!in_array('--apply', $argv, true)) {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}

if (!$group) {
	$gid = (new CGroup)->Add([
		'ACTIVE' => 'Y',
		'C_SORT' => 900,
		'NAME' => 'Тестирование сравнения',
		'DESCRIPTION' => 'Скрытый тест сравнения товаров: видят кнопки «Сравнить» и /compare/. Прав не даёт.',
		'STRING_ID' => ND_GROUP,
	]);
	if (!$gid) {
		exit("не создалась\n");
	}
	echo "группа создана: $gid\n";
} else {
	$gid = (int)$group['ID'];
}
$groups = CUser::GetUserGroup((int)$user['ID']);
if (!in_array($gid, array_map('intval', $groups), true)) {
	// только добавляем: SetUserGroup переписал бы все группы и сроки членства
	CUser::AppendUserGroup((int)$user['ID'], [$gid]);
	echo "пользователь добавлен в группу\n";
} else {
	echo "пользователь уже в группе\n";
}
