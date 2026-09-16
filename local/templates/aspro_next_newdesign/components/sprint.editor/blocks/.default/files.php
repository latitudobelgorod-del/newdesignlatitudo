<? /**
 * @var $block array
 * @var $this \SprintEditorBlocksComponent
 */ ?><?
/* Блок «Файлы» редактора — новый дизайн (Ирина, 11 сентября 2026). Раньше
   выводился маркированным списком ссылок; теперь как список документов в
   «Материалах» и на странице марки (news.list/news-documents_newdesign):
   сетка в три колонки, контурная иконка листа, название и строка «Скачать
   PDF (27,2 Мб)» со стрелкой. Классы и стили общие — .nd-docs* в
   css/newdesign.css, отдельного оформления здесь нет.

   Этот же файл лежит в .default_detail — держать одинаковыми. Остальные
   папки блоков (aspro-*) своего files.php не имеют и берут его из .default.

   Название — подпись файла из редактора, а если её нет — имя файла без
   расширения («Виды ограждений.pdf» → «Виды ограждений»). Тип и размер —
   тем же CNext::GetFileInfo, что и в списке документов, чтобы строка
   «Скачать …» читалась одинаково. */
if (empty($block['files'])) {
	return;
}
?>
<div class="nd-docs nd-docs--editor">
	<? foreach ($block['files'] as $item): ?>
		<?
		$ndFile = (array) ($item['file'] ?? []);
		$ndSrc = (string) ($ndFile['SRC'] ?? '');
		if ($ndSrc === '') {
			continue;
		}

		$ndInfo = !empty($ndFile['ID']) ? CNext::GetFileInfo((int) $ndFile['ID']) : [];
		$ndOrig = (string) ($ndFile['ORIGINAL_NAME'] ?? ($ndInfo['ORIGINAL_NAME'] ?? basename($ndSrc)));
		$ndExt = strtoupper((string) pathinfo($ndOrig !== '' ? $ndOrig : $ndSrc, PATHINFO_EXTENSION));
		$ndName = trim((string) ($item['desc'] ?? ''));
		if ($ndName === '') {
			$ndName = $ndExt !== '' ? preg_replace('/\.[^.]+$/u', '', $ndOrig) : $ndOrig;
		}
		$ndSize = (string) ($ndInfo['FILE_SIZE_FORMAT'] ?? '');
		?>
		<div class="nd-docs__cell">
			<a class="nd-docs__item" href="<?= htmlspecialcharsbx($ndSrc) ?>" target="_blank" title="<?= htmlspecialcharsbx($ndName) ?>">
				<svg class="nd-docs__ico" width="32" height="40" viewBox="0 0 32 40" fill="none" aria-hidden="true" focusable="false">
					<path d="M19 1H5a4 4 0 0 0-4 4v30a4 4 0 0 0 4 4h22a4 4 0 0 0 4-4V13L19 1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
					<path d="M19 1v8a4 4 0 0 0 4 4h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
				</svg>
				<span class="nd-docs__body">
					<span class="nd-docs__name"><?= htmlspecialcharsbx($ndName) ?></span>
					<span class="nd-docs__meta">
						<span>Скачать<?= $ndExt !== '' ? ' '.htmlspecialcharsbx($ndExt) : '' ?><?= $ndSize !== '' ? ' ('.htmlspecialcharsbx($ndSize).')' : '' ?></span>
						<svg class="nd-docs__arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
							<path d="M8 3v10m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</span>
			</a>
		</div>
	<? endforeach; ?>
</div>
