						<? /**
 * @var $block array
 * @var $this \SprintEditorBlocksComponent
 */ ?><?
?><? if (!empty($block['files'])): ?>
    <ul>
        <? foreach ($block['files'] as $item): ?>
            <li><a target="_blank" title="<?= $item['desc'] ?>" href="<?= $item['file']['SRC'] ?>">
			<?=$item['desc'] ? $item['desc'] : $item['file']['ORIGINAL_NAME']?>
			
			
			</a></li>
        <? endforeach; ?>
    </ul>
<? endif; ?>