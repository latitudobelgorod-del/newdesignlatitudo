<?php
/**
 * Письмо на content@latitudo.ru за два дня до окончания акции — чтобы успеть продлить
 * или снять её. (Ирина, 12.11.2026; повод — акция на доску Latitudo Евро)
 *
 *     php local/tools/promo_expiry_notify.php                 # крон, раз в день
 *     php local/tools/promo_expiry_notify.php --dry           # только показать, что ушло бы
 *     php local/tools/promo_expiry_notify.php --dry --date=2026-11-28   # «как будто сегодня»
 *     php local/tools/promo_expiry_notify.php --install       # создать почтовое событие и шаблон
 *     php local/tools/promo_expiry_notify.php --uninstall     # удалить их
 *
 * Что берём (окончание — сегодня, завтра или послезавтра, но ещё не наступило):
 *  - активные акции ИБ 17 (раздел /sale/) по полю «Окончание активности»;
 *  - активные правила корзины (они и дают скидку в ценах) по «Окончанию активности».
 * Правила в письме отдельно: продлить акцию на сайте мало — у правила дата своя
 * (так было с Евро 10.11.2026, см. правило №264).
 * Про каждую акцию/правило пишем один раз на одну дату окончания: отправленное
 * запоминаем в b_option (main / nd_promo_expiry_sent). Продлили — письмо придёт
 * снова за два дня до новой даты.
 *
 * Письмо идёт почтовым событием ND_PROMO_EXPIRY, шаблон правится в админке:
 * Настройки → Почтовые и СМС события → Почтовые шаблоны.
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
CModule::IncludeModule('sale');

const ND_PROMO_IB = 17;
const ND_PROMO_DAYS = 2;
const ND_PROMO_EVENT = 'ND_PROMO_EXPIRY';
const ND_PROMO_EMAIL = 'content@latitudo.ru';
const ND_PROMO_OPT = 'nd_promo_expiry_sent';

$args = implode(' ', array_slice($argv, 1));
$dry = strpos($args, '--dry') !== false;

if (strpos($args, '--install') !== false) {
	if (!CEventType::GetList(array('TYPE_ID' => ND_PROMO_EVENT, 'LID' => 'ru'))->Fetch()) {
		(new CEventType)->Add(array(
			'LID' => 'ru',
			'EVENT_NAME' => ND_PROMO_EVENT,
			'NAME' => 'Скоро закончится акция',
			'DESCRIPTION' => "#COUNT# — сколько акций и правил\n#LIST# — список: название, дата окончания, ссылки",
		));
	}
	if (!CEventMessage::GetList('id', 'asc', array('TYPE_ID' => ND_PROMO_EVENT))->Fetch()) {
		$id = (new CEventMessage)->Add(array(
			'ACTIVE' => 'Y',
			'EVENT_NAME' => ND_PROMO_EVENT,
			'LID' => array('s1'),
			'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
			'EMAIL_TO' => ND_PROMO_EMAIL,
			'SUBJECT' => 'Латитудо: через два дня заканчиваются акции (#COUNT#)',
			'BODY_TYPE' => 'text',
			'MESSAGE' => "Через два дня или раньше на сайте latitudo.ru заканчиваются акции.\n"
				."Если акцию продлеваете — поменяйте дату окончания и у акции в разделе «Акции», и у правила корзины "
				."(Магазин → Маркетинг → Правила работы с корзиной), иначе цены со скидкой пропадут.\n\n"
				."#LIST#\n",
		));
		echo "template $id\n";
	}
	echo "installed\n";
	exit;
}
if (strpos($args, '--uninstall') !== false) {
	$r = CEventMessage::GetList('id', 'asc', array('TYPE_ID' => ND_PROMO_EVENT));
	while ($m = $r->Fetch()) CEventMessage::Delete($m['ID']);
	CEventType::Delete(ND_PROMO_EVENT);
	COption::RemoveOption('main', ND_PROMO_OPT);
	echo "uninstalled\n";
	exit;
}

// «Сейчас»: по --date считаем, что сейчас 9 утра этого дня (время запуска крона).
$now = preg_match('/--date=(\d{4}-\d{2}-\d{2})/', $args, $m) ? strtotime($m[1].' 09:00:00') : time();
$until = strtotime(date('Y-m-d', $now).' 23:59:59 +'.ND_PROMO_DAYS.' days');
$fmt = 'd.m.Y H:i:s';

$sent = json_decode(COption::GetOptionString('main', ND_PROMO_OPT, '{}'), true) ?: array();
$new = array();

$promos = array();
$r = CIBlockElement::GetList(
	array('ACTIVE_TO' => 'ASC', 'NAME' => 'ASC'),
	array('IBLOCK_ID' => ND_PROMO_IB, 'ACTIVE' => 'Y', '>DATE_ACTIVE_TO' => date($fmt, $now), '<=DATE_ACTIVE_TO' => date($fmt, $until)),
	false, false, array('ID', 'NAME', 'ACTIVE_TO', 'DETAIL_PAGE_URL')
);
while ($a = $r->GetNext()) {
	$key = 'e'.$a['ID'];
	if (isset($sent[$key]) && $sent[$key] === $a['ACTIVE_TO']) continue;
	$promos[] = '— '.$a['~NAME']."\n"
		.'  заканчивается: '.$a['ACTIVE_TO']."\n"
		.'  на сайте: https://latitudo.ru'.$a['~DETAIL_PAGE_URL']."\n"
		.'  в админке: https://latitudo.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.ND_PROMO_IB.'&type=aspro_next_content&ID='.$a['ID']."\n";
	$new[$key] = $a['ACTIVE_TO'];
}

$rules = array();
$r = CSaleDiscount::GetList(
	array('ACTIVE_TO' => 'ASC'),
	array('ACTIVE' => 'Y', '>ACTIVE_TO' => date($fmt, $now), '<=ACTIVE_TO' => date($fmt, $until)),
	false, false, array('ID', 'NAME', 'ACTIVE_TO')
);
while ($a = $r->Fetch()) {
	$key = 'd'.$a['ID'];
	if (isset($sent[$key]) && $sent[$key] === $a['ACTIVE_TO']) continue;
	$rules[] = '— №'.$a['ID'].' '.trim($a['NAME'])."\n"
		.'  заканчивается: '.$a['ACTIVE_TO']."\n"
		.'  https://latitudo.ru/bitrix/admin/sale_discount_edit.php?ID='.$a['ID'].'&lang=ru'."\n";
	$new[$key] = $a['ACTIVE_TO'];
}

if (!$promos && !$rules) {
	if ($dry) echo 'nothing ('.date($fmt, $now).' .. '.date($fmt, $until).")\n";
	exit;
}
$list = '';
if ($promos) $list .= "АКЦИИ НА САЙТЕ (раздел «Акции»):\n\n".implode("\n", $promos)."\n";
if ($rules) $list .= "ПРАВИЛА КОРЗИНЫ (скидка в ценах):\n\n".implode("\n", $rules);
$fields = array('COUNT' => count($promos) + count($rules), 'LIST' => rtrim($list));
if ($dry) {
	echo 'would send ('.date($fmt, $now).' .. '.date($fmt, $until).'), ', $fields['COUNT'], ":\n", $fields['LIST'], "\n";
	exit;
}
CEvent::Send(ND_PROMO_EVENT, 's1', $fields, 'N');
// Чистим отметки об уже закончившихся, чтобы опция не росла.
foreach ($sent as $k => $d) if (MakeTimeStamp($d) < $now) unset($sent[$k]);
COption::SetOptionString('main', ND_PROMO_OPT, json_encode($new + $sent));
echo 'sent ', $fields['COUNT'], "\n";
