<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Разделы портфолио плашками — по макету нового дизайна: под панелью
 * фильтров, над сеткой карточек. Раздел, в котором мы находимся, подсвечен.
 *
 * Ждёт от вызывающего кода:
 *  $ndIb            — ID инфоблока портфолио;
 *  $ndSectionId     — ID текущего раздела (0 на общей странице /projects/).
 *
 * Список разделов один и тот же на всех страницах портфолио, поэтому держим
 * его в кеше с тегом инфоблока — сбросится сам при правке разделов.
 */
$ndIb = (int) ($ndIb ?? 0);
$ndSectionId = (int) ($ndSectionId ?? 0);

if (!$ndIb) {
	return;
}

$ndSectionsCache = new CPHPCache();
$ndSectionsCacheId = 'nd_projects_sections_'.$ndIb.'_'.SITE_ID;
$ndSectionsCacheDir = '/nd/projects_sections';
$ndSections = [];

if ($ndSectionsCache->InitCache(86400, $ndSectionsCacheId, $ndSectionsCacheDir)) {
	$vars = $ndSectionsCache->GetVars();
	$ndSections = is_array($vars['SECTIONS'] ?? null) ? $vars['SECTIONS'] : [];
} else {
	$taggedCache = Bitrix\Main\Application::getInstance()->getTaggedCache();
	$taggedCache->startTagCache($ndSectionsCacheDir);
	$taggedCache->registerTag('iblock_id_'.$ndIb);

	$rsSections = CIBlockSection::GetList(
		['SORT' => 'ASC', 'NAME' => 'ASC'],
		['IBLOCK_ID' => $ndIb, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1],
		false,
		['ID', 'NAME', 'SECTION_PAGE_URL']
	);
	while ($section = $rsSections->GetNext()) {
		$ndSections[] = [
			'ID' => (int) $section['ID'],
			'NAME' => $section['NAME'],
			'URL' => $section['SECTION_PAGE_URL'],
		];
	}

	$taggedCache->endTagCache();

	if ($ndSectionsCache->StartDataCache()) {
		$ndSectionsCache->EndDataCache(['SECTIONS' => $ndSections]);
	}
}

if (!$ndSections) {
	return;
}
?>
<nav class="nd-sections" aria-label="Разделы портфолио">
	<? foreach ($ndSections as $ndSection): ?>
		<? if ($ndSection['ID'] === $ndSectionId): ?>
			<span class="nd-sections__item nd-sections__item--active" aria-current="page"><?= htmlspecialcharsbx($ndSection['NAME']) ?></span>
		<? else: ?>
			<a class="nd-sections__item" href="<?= htmlspecialcharsbx($ndSection['URL']) ?>"><?= htmlspecialcharsbx($ndSection['NAME']) ?></a>
		<? endif; ?>
	<? endforeach; ?>
</nav>
