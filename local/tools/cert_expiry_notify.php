<?php
/**
 * Письмо на content@latitudo.ru за неделю до окончания срока действия документа
 * из «Лицензий и сертификатов» (ИБ 13) — чтобы успеть запросить у поставщика свежий.
 * (Ирина, 10.11.2026)
 *
 *     php local/tools/cert_expiry_notify.php                 # крон, раз в день
 *     php local/tools/cert_expiry_notify.php --dry           # только показать, что ушло бы
 *     php local/tools/cert_expiry_notify.php --dry --date=2027-04-16   # «как будто сегодня»
 *     php local/tools/cert_expiry_notify.php --install       # создать почтовое событие и шаблон
 *     php local/tools/cert_expiry_notify.php --uninstall     # удалить их
 *
 * Что берём: активные документы ИБ 13, у которых заполнено «Дата окончания срока
 * действия» (DATE) и до неё 7 дней или меньше (но срок ещё не прошёл). Про каждый
 * документ пишем один раз на одну дату: отправленное запоминаем в b_option
 * (main / nd_cert_expiry_sent). Новая дата у документа (загрузили свежий сертификат) —
 * письмо придёт снова, когда и она подойдёт.
 *
 * Когда срок прошёл, документ сам пропадает со страницы /info/sertifikaty-i-litsenzii/
 * и из «Документации» на странице производителя — фильтр по DATE в
 * iblock_elements__aspro-licenses.php и partners_newdesign/page_blocks/element_1.php.
 *
 * Письмо идёт почтовым событием ND_CERT_EXPIRY (как письма форм сайта), шаблон
 * правится в админке: Настройки → Почтовые и СМС события → Почтовые шаблоны.
 */

if (PHP_SAPI !== 'cli') {
	die('CLI only');
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__.'/../..');
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const ND_CERT_IB = 13;
const ND_CERT_DAYS = 7;
const ND_CERT_EVENT = 'ND_CERT_EXPIRY';
const ND_CERT_EMAIL = 'content@latitudo.ru';

$args = implode(' ', array_slice($argv, 1));
$dry = strpos($args, '--dry') !== false;

if (strpos($args, '--install') !== false) {
	if (!CEventType::GetList(array('TYPE_ID' => ND_CERT_EVENT, 'LID' => 'ru'))->Fetch()) {
		(new CEventType)->Add(array(
			'LID' => 'ru',
			'EVENT_NAME' => ND_CERT_EVENT,
			'NAME' => 'Скоро закончится срок действия сертификата',
			'DESCRIPTION' => "#COUNT# — сколько документов\n#LIST# — список: название, производитель, дата, ссылка в админку",
		));
	}
	if (!CEventMessage::GetList('id', 'asc', array('TYPE_ID' => ND_CERT_EVENT))->Fetch()) {
		$id = (new CEventMessage)->Add(array(
			'ACTIVE' => 'Y',
			'EVENT_NAME' => ND_CERT_EVENT,
			'LID' => array('s1'),
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => ND_CERT_EMAIL,
			'SUBJECT' => 'Латитудо: через неделю заканчивается срок действия документов (#COUNT#)',
			'BODY_TYPE' => 'text',
			'MESSAGE' => "Через неделю или раньше заканчивается срок действия документов на сайте latitudo.ru.\n"
				."Запросите у поставщиков свежие и загрузите их в «Лицензии и сертификаты» (новый файл, картинку и дату).\n"
				."После даты окончания документ сам пропадёт со страницы «Сертификаты и лицензии» и со страницы производителя.\n\n"
				."#LIST#\n",
		));
		echo "template $id\n";
	}
	echo "installed\n";
	exit;
}
if (strpos($args, '--uninstall') !== false) {
	$r = CEventMessage::GetList('id', 'asc', array('TYPE_ID' => ND_CERT_EVENT));
	while ($m = $r->Fetch()) CEventMessage::Delete($m['ID']);
	CEventType::Delete(ND_CERT_EVENT);
	COption::RemoveOption('main', 'nd_cert_expiry_sent');
	echo "uninstalled\n";
	exit;
}

$today = preg_match('/--date=(\d{4}-\d{2}-\d{2})/', $args, $m) ? $m[1] : date('Y-m-d');
$until = date('Y-m-d', strtotime($today.' +'.ND_CERT_DAYS.' days'));

$sent = json_decode(COption::GetOptionString('main', 'nd_cert_expiry_sent', '{}'), true) ?: array();

$brands = array();
$r = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 12), false, false, array('ID', 'NAME'));
while ($a = $r->Fetch()) $brands[$a['ID']] = $a['NAME'];

$list = array(); $ids = array();
$r = CIBlockElement::GetList(
	array('PROPERTY_DATE' => 'ASC', 'NAME' => 'ASC'),
	array('IBLOCK_ID' => ND_CERT_IB, 'ACTIVE' => 'Y', '>=PROPERTY_DATE' => $today, '<=PROPERTY_DATE' => $until),
	false, false, array('ID', 'IBLOCK_ID', 'NAME')
);
while ($ob = $r->GetNextElement()) {
	$f = $ob->GetFields(); $p = $ob->GetProperties(array(), array());
	$date = $p['DATE']['VALUE'];
	if (isset($sent[$f['ID']]) && $sent[$f['ID']] === $date) continue;
	$b = array();
	foreach ((array)$p['BRAND']['VALUE'] as $x) if ($x && isset($brands[$x])) $b[] = $brands[$x];
	$list[] = '— '.$f['~NAME']."\n"
		.'  производитель: '.($b ? implode(', ', $b) : 'не указан')."\n"
		.'  действует до: '.$date.($p['SERT_LICENSE']['VALUE'] ? ' (сейчас на странице «Сертификаты и лицензии»)' : '')."\n"
		.'  https://latitudo.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.ND_CERT_IB.'&type=aspro_next_content&ID='.$f['ID']."\n";
	$ids[$f['ID']] = $date;
}

if (!$list) {
	if ($dry) echo "nothing ($today .. $until)\n";
	exit;
}
$fields = array('COUNT' => count($list), 'LIST' => implode("\n", $list));
if ($dry) {
	echo "would send ($today .. $until), ", $fields['COUNT'], ":\n", $fields['LIST'];
	exit;
}
CEvent::Send(ND_CERT_EVENT, 's1', $fields, 'N');
// Чистим старые отметки (дата уже прошла), чтобы опция не росла.
foreach ($sent as $id => $d) if (strtotime($d) < strtotime($today)) unset($sent[$id]);
COption::SetOptionString('main', 'nd_cert_expiry_sent', json_encode($ids + $sent));
echo 'sent ', count($list), "\n";
