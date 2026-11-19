<?php
/** CLI only. check is read-only; enable requires separately approved production deployment. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? 'check';
if (!in_array($mode, ['check', 'enable', 'disable'], true)) throw new RuntimeException('Use check|enable|disable');
$formId = (int)($argv[2] ?? 1);
$audit = json_decode(file_get_contents(__DIR__.'/mappings.json'), true, 512, JSON_THROW_ON_ERROR);
if (!isset($audit[$formId])) throw new RuntimeException('Unaudited form');
$expected = $audit[$formId];
$option = 'nd_form'.$formId.'_rest_enabled';
$cutoverOption = 'nd_form'.$formId.'_rest_cutover';
$root = realpath(__DIR__.'/../..');
if ($mode !== 'check' && str_replace('\\', '/', $root) !== '/var/www/u0916578/data/www/latitudo.ru') {
    throw new RuntimeException('Mutation is restricted to the verified latitudo.ru document root.');
}
if ($mode !== 'check' && posix_getpwuid(posix_geteuid())['name'] !== 'u0916578') throw new RuntimeException('Wrong hosting user');
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTP_HOST'] = 'latitudo.ru';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require $root.'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('form');
require_once $root.'/local/php_interface/include/latitudo_form_crm.php';
$db = $GLOBALS['DB'];
$form = CForm::GetByID($formId)->Fetch();
$link = CFormCrm::GetByFormID($formId)->Fetch();
if (($form['SID'] ?? '') !== $expected['sid'] || (int)($link['CRM_ID'] ?? 0) !== $expected['crm']) throw new RuntimeException('Unexpected form/connection');
$mapping = [];
$q = CFormCrm::GetFields($link['ID']);
while ($r = $q->Fetch()) $mapping[] = $r;
if ($mapping !== $expected['fields']) throw new RuntimeException('Mappings changed since audit');
echo 'Form '.$form['SID'].'; mappings='.count($mapping).'; legacy mode='.$link['LINK_TYPE'].'; REST='.(LatitudoFormCrm::enabled($formId) ? 'Y' : 'N')."\n";
if ($mode === 'check') exit;
if ($mode === 'enable' && LatitudoFormCrm::enabled($formId)) {
    if ($link['LINK_TYPE'] !== 'M') throw new RuntimeException('REST active but legacy automatic mode is enabled');
    echo "Already enabled; nothing changed.\n"; exit;
}

// Snapshot schema and all rows touched by the migration outside the public root.
$snapshot = [];
$tables = [
    'b_form_crm_link' => 'FORM_ID='.$formId,
    'b_form_crm_field' => 'LINK_ID='.(int)$link['ID'],
    'b_option' => "MODULE_ID='form' AND NAME IN ('$option','$cutoverOption')",
    'b_agent' => "NAME='".$db->ForSql(LatitudoFormCrm::AGENT)."'",
];
foreach ($tables as $table => $where) {
    $schema = $db->Query('SHOW CREATE TABLE '.$table)->Fetch();
    $rows = []; $q = $db->Query('SELECT * FROM '.$table.' WHERE '.$where);
    while ($r = $q->Fetch()) $rows[] = $r;
    $snapshot[$table] = ['schema' => $schema['Create Table'], 'rows' => $rows];
}
$exists = (bool)$db->Query("SHOW TABLES LIKE 'b_latitudo_form_crm'")->Fetch();
if ($exists) {
    $schema = $db->Query('SHOW CREATE TABLE b_latitudo_form_crm')->Fetch();
    $rows = []; $q = $db->Query('SELECT * FROM b_latitudo_form_crm ORDER BY result_id');
    while ($r = $q->Fetch()) $rows[] = $r;
    $snapshot['b_latitudo_form_crm'] = ['schema' => $schema['Create Table'], 'rows' => $rows];
}
$dir = dirname(dirname($root)).'/codex-backups';
if (!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Cannot create backup directory');
$file = $dir.'/form'.$formId.'-rest-'.$mode.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
if (file_put_contents($file,$json) === false) throw new RuntimeException('Cannot write backup');
chmod($file,0600);
if (json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR) !== $snapshot) throw new RuntimeException('Backup verification failed');
echo 'Verified backup: '.$file."\n";

if ($mode === 'disable') {
    COption::SetOptionString('form', $option, 'N');
    if (!array_filter(LatitudoFormCrmDelivery::FORMS, [LatitudoFormCrm::class,'enabled'])) CAgent::RemoveAgent(LatitudoFormCrm::AGENT, 'form');
    // Do not reactivate a broken OTP connection or replay pending results.
    // Retain mappings, all saved forms, queue records and confirmed lead IDs.
    echo "REST stopped. Legacy connection remains manual. Results/mail remain active. Queue and IDs preserved.\n";
    exit;
}

$probe = LatitudoFormCrm::request('crm.lead.list', ['filter'=>['=ORIGINATOR_ID'=>LatitudoFormCrmDelivery::ORIGIN,
    '=ORIGIN_ID'=>'preflight-no-result'], 'select'=>['ID']]);
if (!isset($probe['result']) || !is_array($probe['result'])) throw new RuntimeException('Webhook read preflight failed; nothing enabled');
if (!$exists) {
    $db->Query("CREATE TABLE b_latitudo_form_crm (
        result_id INT UNSIGNED NOT NULL PRIMARY KEY,
        status VARCHAR(16) NOT NULL,
        lead_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        attempts INT UNSIGNED NOT NULL DEFAULT 0,
        last_error TEXT NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY queue_status (status,updated_at)
    ) ENGINE=InnoDB");
}
$cols = []; $q=$db->Query('SHOW COLUMNS FROM b_latitudo_form_crm');
while ($r=$q->Fetch()) $cols[]=$r['Field'];
if ($cols !== ['result_id','status','lead_id','attempts','last_error','updated_at']) throw new RuntimeException('Unexpected queue schema');
$cutover = $db->Query('SELECT COALESCE(MAX(ID),0) AS ID FROM b_form_result WHERE FORM_ID='.$formId)->Fetch()['ID'];
// Persist the cutover before changing routing; missing new queue rows can be recovered.
COption::SetOptionString('form', $cutoverOption, (string)$cutover);
$db->Query("UPDATE b_form_crm_link SET LINK_TYPE='M' WHERE ID=".(int)$link['ID']." AND FORM_ID=".$formId." AND CRM_ID=".$expected['crm']);
try {
    $existingAgent = CAgent::GetList([], ['NAME'=>LatitudoFormCrm::AGENT])->Fetch();
    $agent = $existingAgent ? $existingAgent['ID'] : CAgent::AddAgent(LatitudoFormCrm::AGENT, 'form', 'N', 60, '', 'Y', ConvertTimeStamp(time()+120,'FULL'), 100);
    if (!$agent) throw new RuntimeException('Cannot schedule REST queue');
    // Enable last. Other forms and email templates are never changed.
    COption::SetOptionString('form', $option, 'Y');
    if (!LatitudoFormCrm::enabled($formId) || CFormCrm::GetByFormID($formId)->Fetch()['LINK_TYPE'] !== 'M') {
        throw new RuntimeException('Activation verification failed');
    }
} catch (Throwable $e) {
    COption::SetOptionString('form', $option, 'N');
    if (!array_filter(LatitudoFormCrmDelivery::FORMS, [LatitudoFormCrm::class,'enabled'])) CAgent::RemoveAgent(LatitudoFormCrm::AGENT, 'form');
    // Routing was changed but REST has not been validated. Keep it manual rather
    // than silently resume the known failing OTP sender.
    throw $e;
}
$after = []; $q=CFormCrm::GetFields($link['ID']);while($r=$q->Fetch())$after[]=$r;
if ($after !== $mapping) throw new RuntimeException('Mapping verification failed');
echo "Enabled for new form #$formId results after #$cutover, responsible 65. Legacy sender is manual; mappings preserved.\n";
