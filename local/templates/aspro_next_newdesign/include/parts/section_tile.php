<?
/**
 * Плитка раздела портфолио — общая разметка.
 *
 * Одна и та же плитка нужна в двух местах: на общей странице /projects/
 * (page_blocks/sections_list_newdesign.php) и в блоке редактора
 * «Разделы инфоблока» (sprint.editor, iblock_sections__aspro-projects).
 * Держим разметку здесь, чтобы они не разъехались — так же сделано для
 * карточки проекта (include/parts/project_card.php).
 *
 * Стили `.nd-sectiles*` лежат в css/newdesign.css, он подключается на всех
 * страницах нового дизайна, так что плитка работает где угодно.
 *
 * Картинку ждём уже готовой ссылкой: источники у вызывающих разные
 * (PICTURE раздела или DETAIL_PICTURE), а размер один — 660×420 EXACT.
 */
if (!function_exists('ndSectionTileImage')) {
	/** ID файла → ссылка на превью 660×420. Пусто, если картинки нет. */
	function ndSectionTileImage($fileId)
	{
		$fileId = (int) $fileId;
		if ($fileId <= 0) {
			return '';
		}
		$image = CFile::ResizeImageGet($fileId, ['width' => 660, 'height' => 420], BX_RESIZE_IMAGE_EXACT, true);

		return $image['src'] ?? '';
	}
}

if (!function_exists('ndSectionTile')) {
	function ndSectionTile($name, $url, $src, $editAreaId = '')
	{
		?>
		<a class="nd-sectiles__item" href="<?= htmlspecialcharsbx($url) ?>"<?= $editAreaId ? ' id="'.$editAreaId.'"' : '' ?>>
			<span class="nd-sectiles__pic">
				<? if ($src): ?>
					<img src="<?= htmlspecialcharsbx($src) ?>" alt="<?= htmlspecialcharsbx($name) ?>" loading="lazy">
				<? endif; ?>
			</span>
			<span class="nd-sectiles__name"><?= htmlspecialcharsbx($name) ?></span>
		</a>
		<?
	}
}
