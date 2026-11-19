<?php
/** CLI only. check is read-only; enable requires separately approved production deployment. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$mode = $argv[1] ?? 'check';
if (!in_array($mode, ['check', 'enable', 'disable'], true)) throw new RuntimeException('Use check|enable|disable');
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
$form = CForm::GetByID(1)->Fetch();
$link = CFormCrm::GetByFormID(1)->Fetch();
if (($form['SID'] ?? '') !== 'TOORDER' || (int)($link['CRM_ID'] ?? 0) !== 3) throw new RuntimeException('Unexpected form/connection');
$mapping = [];
$q = CFormCrm::GetFields($link['ID']);
while ($r = $q->Fetch()) $mapping[] = $r;
if (count($mapping) !== 15) throw new RuntimeException('Expected 15 verified field mappings; re-audit before migration');
$expected = ['TITLE'=>0, 'NAME'=>1, 'PHONE_WORK'=>2, 'EMAIL_WORK'=>3,
    'UF_CRM_1670914333154'=>97, 'UF_CRM_1670914240846'=>96, 'UF_CRM_1671023828597'=>58,
    'UF_CRM_1674473471'=>111, 'UF_CRM_1674473526'=>112, 'UF_CRM_1674473492'=>113,
    'UF_CRM_1674473502'=>114, 'UF_CRM_1674473509'=>115, 'UF_CRM_1670914076118'=>6,
    'COMMENTS'=>4, 'UF_CRM_1674473518'=>123];
foreach ($mapping as $m) {
    if (!isset($expected[$m['CRM_FIELD']]) || $expected[$m['CRM_FIELD']] !== (int)$m['FIELD_ID']
        || ($m['CRM_FIELD'] === 'TITLE' && $m['FIELD_ALT'] !== 'FORM_NAME')) throw new RuntimeException('Field mapping changed');
}
echo 'Form TOORDER; mappings=15; legacy mode='.$link['LINK_TYPE'].'; REST='.(LatitudoFormCrm::enabled() ? 'Y' : 'N')."\n";
if ($mode === 'check') exit;
if ($mode === 'enable' && LatitudoFormCrm::enabled()) {
    if ($link['LINK_TYPE'] !== 'M') throw new RuntimeException('REST active but legacy automatic mode is enabled');
    echo "Already enabled; nothing changed.\n"; exit;
}

// Snapshot schema and all rows touched by the migration outside the public root.
$snapshot = [];
$tables = [
    'b_form_crm_link' => 'FORM_ID=1',
    'b_form_crm_field' => 'LINK_ID='.(int)$link['ID'],
    'b_option' => "MODULE_ID='form' AND NAME IN ('nd_form1_rest_enabled','nd_form1_rest_cutover')",
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
$file = $dir.'/form1-rest-'.$mode.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
if (file_put_contents($file,$json) === false) throw new RuntimeException('Cannot write backup');
chmod($file,0600);
if (json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR) !== $snapshot) throw new RuntimeException('Backup verification failed');
echo 'Verified backup: '.$file."\n";

if ($mode === 'disable') {
    COption::SetOptionString('form', LatitudoFormCrm::OPTION, 'N');
    CAgent::RemoveAgent(LatitudoFormCrm::AGENT, 'form');
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
$cutover = $db->Query('SELECT COALESCE(MAX(ID),0) AS ID FROM b_form_result WHERE FORM_ID=1')->Fetch()['ID'];
// Persist the cutover before changing routing; missing new queue rows can be recovered.
COption::SetOptionString('form', 'nd_form1_rest_cutover', (string)$cutover);
$db->Query("UPDATE b_form_crm_link SET LINK_TYPE='M' WHERE ID=".(int)$link['ID']." AND FORM_ID=1 AND CRM_ID=3");
try {
    $agent = CAgent::AddAgent(LatitudoFormCrm::AGENT, 'form', 'N', 60, '', 'Y', ConvertTimeStamp(time()+120,'FULL'), 100);
    if (!$agent) throw new RuntimeException('Cannot schedule REST queue');
    // Enable last. Other forms and email templates are never changed.
    COption::SetOptionString('form', LatitudoFormCrm::OPTION, 'Y');
    if (!LatitudoFormCrm::enabled() || CFormCrm::GetByFormID(1)->Fetch()['LINK_TYPE'] !== 'M') {
        throw new RuntimeException('Activation verification failed');
    }
} catch (Throwable $e) {
    COption::SetOptionString('form', LatitudoFormCrm::OPTION, 'N');
    CAgent::RemoveAgent(LatitudoFormCrm::AGENT, 'form');
    // Routing was changed but REST has not been validated. Keep it manual rather
    // than silently resume the known failing OTP sender.
    throw $e;
}
$after = []; $q=CFormCrm::GetFields($link['ID']);while($r=$q->Fetch())$after[]=$r;
if ($after !== $mapping) throw new RuntimeException('Mapping verification failed');
echo "Enabled for new TOORDER results after #$cutover, responsible 65. Legacy sender is manual; mappings preserved.\n";
