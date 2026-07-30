<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Плитки разделов портфолио — общая страница /projects/.
 *
 * В старом дизайне то же самое делал page_block `sections_3` через шаблон
 * news.list `catalog-sections-3_pr`: разделы первого уровня, картинка раздела,
 * название и ссылка. Здесь та же выборка, но разметка нового макета
 * (три плитки в ряд, картинка 429×272, подпись под ней — как у карточек
 * проектов, чтобы сетки не разъезжались).
 *
 * Ждёт от вызывающего кода $ndIb — ID инфоблока портфолио.
 */
$ndIb = (int) ($ndIb ?? 0);
if (!$ndIb) {
	return;
}

$ndTilesCache = new CPHPCache();
$ndTilesCacheId = 'nd_projects_section_tiles_'.$ndIb.'_'.SITE_ID;
$ndTilesCacheDir = '/nd/projects_sections';
$ndTiles = [];

if ($ndTilesCache->InitCache(86400, $ndTilesCacheId, $ndTilesCacheDir)) {
	$vars = $ndTilesCache->GetVars();
	$ndTiles = is_array($vars['TILES'] ?? null) ? $vars['TILES'] : [];
} else {
	$taggedCache = Bitrix\Main\Application::getInstance()->getTaggedCache();
	$taggedCache->startTagCache($ndTilesCacheDir);
	$taggedCache->registerTag('iblock_id_'.$ndIb);

	$rsSections = CIBlockSection::GetList(
		['SORT' => 'ASC', 'NAME' => 'ASC'],
		['IBLOCK_ID' => $ndIb, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'DEPTH_LEVEL' => 1],
		false,
		['ID', 'NAME', 'SECTION_PAGE_URL', 'PICTURE', 'DETAIL_PICTURE']
	);
	while ($section = $rsSections->GetNext()) {
		$fileId = (int) ($section['PICTURE'] ?: $section['DETAIL_PICTURE']);
		$image = $fileId > 0
			? CFile::ResizeImageGet($fileId, ['width' => 660, 'height' => 420], BX_RESIZE_IMAGE_EXACT, true)
			: [];

		$ndTiles[] = [
			'ID' => (int) $section['ID'],
			'NAME' => $section['NAME'],
			'URL' => $section['SECTION_PAGE_URL'],
			'SRC' => $image['src'] ?? '',
		];
	}

	$taggedCache->endTagCache();

	if ($ndTilesCache->StartDataCache()) {
		$ndTilesCache->EndDataCache(['TILES' => $ndTiles]);
	}
}

if (!$ndTiles) {
	?><div class="alert alert-warning"><?= GetMessage('SECTION_EMPTY') ?></div><?
	return;
}
?>
<? /* Свои классы, а не .nd-projects*: те живут в style.css шаблона news.list,
      который на этой странице не подключается — сетка бы рассыпалась. */ ?>
<section class="nd-sectiles">
	<? foreach ($ndTiles as $ndTile): ?>
		<a class="nd-sectiles__item" href="<?= htmlspecialcharsbx($ndTile['URL']) ?>">
			<span class="nd-sectiles__pic">
				<? if ($ndTile['SRC']): ?>
					<img src="<?= htmlspecialcharsbx($ndTile['SRC']) ?>" alt="<?= htmlspecialcharsbx($ndTile['NAME']) ?>" loading="lazy">
				<? endif; ?>
			</span>
			<span class="nd-sectiles__name"><?= htmlspecialcharsbx($ndTile['NAME']) ?></span>
		</a>
	<? endforeach; ?>
</section>
