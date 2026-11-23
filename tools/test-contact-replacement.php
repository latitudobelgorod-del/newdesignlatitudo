<?php
// Isolated checks: no Bitrix bootstrap, database, mail or CRM requests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
function loadFunctions(string $file, array $names): void
{
    $tokens = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $start = $i;
        do { $i++; } while (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE);
        if (!is_array($tokens[$i]) || !in_array($tokens[$i][1], $names, true)) continue;
        $code = ''; $depth = 0; $opened = false;
        for ($i = $start; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $code .= is_array($token) ? $token[1] : $token;
            if ($token === '{') { $depth++; $opened = true; }
            if ($token === '}') $depth--;
            if ($opened && $depth === 0) break;
        }
        eval($code);
    }
}
function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
loadFunctions($root.'/local/init.php', [
    'ndUtmSetCookie', 'ndUtmRemember', 'ndIsUtmVisit', 'ndShouldReplaceContacts',
    'ndRegionMailPodmena', 'ndOfficePhonePodmena',
]);
loadFunctions($root.'/local/php_interface/include/latitudo_catalog_cache.php', ['ndUtmCacheKey']);
define('ND_UTM_COOKIE', 'ND_UTM');
define('ND_UTM_COOKIE_DAYS', 30);
define('ND_UTM_VISIT_COOKIE', 'ND_UTM_VISIT');
define('ND_UTM_VISIT_SECONDS', 86400);
define('B_PROLOG_INCLUDED', true);
define('SITE_TEMPLATE_PATH', '/local/templates/aspro_next_newdesign');
function bitrix_sessid_post() { return ''; }
function htmlspecialcharsbx($s) { return htmlspecialchars($s, ENT_QUOTES); }
class CIBlock { static function GetArrayByID(...$args) { return ''; } }
class ContactTemplateFixture
{
    function setFrameMode($value) {}
    function AddEditAction(...$args) {}
    function GetEditAreaId($id) { return 'fixture-'.$id; }
    function render($path, $arResult): string
    {
        ob_start();
        try { include $path; return ob_get_contents(); }
        finally { ob_end_clean(); }
    }
}
$fixture = new ContactTemplateFixture();
$regions = [
    ['Belgorod', 'belgorod@latitudo.ru', '+74722407337', '+79205534300'],
    ['Voronezh', 'voronezh@latitudo.ru', '+74732299800', '+79204078281'],
    ['Moscow', 'm@latitudo.ru', '+74951359305', '+79262621310'],
    ['Krasnodar', 'krasnodar@latitudo.ru', '+78612900717', '+79284243135'],
    ['Rostov', 'rostov@latitudo.ru', '+78632299950', '+79381734026'],
];
$scenarios = [[], ['utm_source'=>'yandex'], ['utm_medium'=>'cpc'], ['utm_source'=>'vk'],
    ['utm_campaign'=>'campaign-only'], ['utm_source'=>'custom-newsletter']];
$renders = 0;
foreach ($regions as [$city, $mail, $phone, $replacement]) {
    $arRegion = ['ID'=>10039, 'PROPERTY_REGION_TAG_EMAIL_PODMENA_VALUE'=>'latitudo-info@yandex.ru'];
    foreach ($scenarios as $utm) {
        $_REQUEST = $utm; $_SESSION = []; $_COOKIE = [];
        $_SERVER['HTTP_HOST'] = 'latitudo.test';
        ndUtmRemember();
        check(($_SESSION['UTM'] ?? []) === $utm, 'UTM collection changed');
        check(ndIsUtmVisit() === (bool)$utm, 'Visit detection changed');
        check(!ndShouldReplaceContacts(), 'Replacement must stay disabled');
        check(ndUtmCacheKey() === 'contacts-original-v1', 'Old cache key reused');
        $html = '<a href="mailto:'.$mail.'">'.$mail.'</a><a href="tel:'.$phone.'">'.$phone.'</a>';
        $original = $html;
        ndRegionMailPodmena($html); ndOfficePhonePodmena($html);
        check($html === $original, 'Output handler changed original contacts');
        $props = ['PHONE'=>['VALUE'=>[$phone]], 'PHONE_PODMENA'=>['VALUE'=>$replacement],
            'EMAIL'=>['DISPLAY_VALUE'=>$mail]];
        $item = ['ID'=>1, 'IBLOCK_ID'=>10, 'EDIT_LINK'=>'', 'NAME'=>$city, 'ND_CITY'=>$city,
            'ND_URL'=>'', 'ND_PIC'=>'', 'DISPLAY_PROPERTIES'=>$props,
            'PROPERTIES'=>['REGION_TAG_PHONE'=>['VALUE'=>$phone],
                'REGION_TAG_PHONE_PODMENA'=>['VALUE'=>$replacement]]];
        foreach ([
            '/local/templates/aspro_next_newdesign/components/bitrix/news.list/contacts_newdesign/template.php',
            '/local/templates/aspro_next/components/bitrix/news.list/other_regions_footer/template.php',
            '/local/templates/aspro_next_newdesign/components/bitrix/news.list/other_regions_footer/template.php',
        ] as $template) {
            // Legacy templates expect all UTM keys to be present.
            $_SESSION['UTM'] = $utm + array_fill_keys(['utm_source','utm_medium','utm_campaign','utm_content','utm_term'], '');
            $rendered = $fixture->render($root.$template, ['ITEMS'=>[$item]]);
            check(str_contains($rendered, 'tel:'.$phone), $template.': original phone missing');
            check(!str_contains($rendered, $replacement), $template.': replacement phone leaked');
            check(!str_contains($rendered, 'latitudo-info@yandex.ru'), $template.': replacement email leaked');
            if (str_contains($template, '/contacts_newdesign/')) {
                check(str_contains($rendered, 'mailto:'.$mail), 'Original email missing');
            }
            $renders++;
        }
    }
}
$_REQUEST = []; $_SESSION = [];
$_COOKIE = ['ND_UTM'=>json_encode(['utm_source'=>'yandex']), 'ND_UTM_VISIT'=>'1'];
ndUtmRemember();
check($_SESSION['UTM']['utm_source'] === 'yandex' && ndIsUtmVisit(), 'Existing cookie attribution lost');
check(!ndShouldReplaceContacts(), 'Existing advertising cookie enabled replacement');
echo "PASS: $renders template renders, five regions, six UTM scenarios; handlers, cache and existing cookies.\n";
