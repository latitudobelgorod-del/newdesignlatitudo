<? $APPLICATION->RestartBuffer();?>
<?
$application = \Bitrix\Main\Application::getInstance();
$request = $application->getContext()->getRequest();
$post = $request->getPostList();

$_SESSION['REVIEW_SORT_PROP'] = $request['sort'];
$_SESSION['REVIEW_SORT_ORDER'] = $request['order'];

if ($post && isset($post['reviews_filter'])) {
	$_SESSION['REVIEW_FILTER'] = $post['filter'];
}

$ajaxData = [
	'IBLOCK_ID' => $arParams['IBLOCK_ID'],
	'ELEMENT_ID' => $arElement['ID'],
	'SITE_ID' => SITE_ID,
];
?>
<script>
	$.ajax({
		url: <?=CUtil::PhpToJSObject($request['ajax_url']);?> + '?' + <?=CUtil::PhpToJSObject(bitrix_sessid_get());?>,
		type: 'post',
		data:  <?=CUtil::PhpToJSObject($ajaxData);?>,
		success: function(html){
			$('#<?=$request['containerId'];?>').html(
				$(html).find('#<?=$request['containerId'];?>').html()
			);
		}
	});
</script>