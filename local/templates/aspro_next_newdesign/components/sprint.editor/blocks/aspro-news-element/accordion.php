<? /**
 * @var $block array
 * @var $this \SprintEditorBlocksComponent
 */ ?>
<?// Строка пункта: слева метка-квадрат (место под иконку услуги), справа
   // плюс, раскрытый пункт — красная метка и минус (Ирина, 11 сентября 2026).
   // Классы accordeon_lat / acc-head / acc-body прежние — на них завязан скрипт.?>
<div class="accordeon_lat">
    <? foreach ($block['items'] as $item): ?>
       <div class="accordion-item">
	   <div class="acc-head" role="button" tabindex="0" aria-expanded="false">
            <span class="acc-head__ico" aria-hidden="true"></span>
            <span class="acc-head__title"><?= $item['title'] ?></span>
            <span class="acc-head__sign" aria-hidden="true"></span>
        </div>
        <div class="acc-body">

            <? foreach ($item['blocks'] as $itemblock): ?>
                <? $this->includeBlock($itemblock) ?>
		   <? endforeach; ?>
		   </div>
		</div>
<? endforeach; ?>

</div>
