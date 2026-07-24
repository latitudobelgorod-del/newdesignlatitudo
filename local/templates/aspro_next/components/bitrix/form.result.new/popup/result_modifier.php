<?	
$phoneField = $arResult['QUESTIONS']['PHONE'];
$phoneField['HTML_CODE'] = str_replace(
    '<input',
    '<input data-mask="+79 999 999 999"',
    $phoneField['HTML_CODE']
);
?>