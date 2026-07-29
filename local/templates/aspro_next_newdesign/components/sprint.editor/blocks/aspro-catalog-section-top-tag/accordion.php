<? /**
 * @var $block array
 * @var $this \SprintEditorBlocksComponent
 */ ?>
<div class="accordeon_lat">
    <? foreach ($block['items'] as $item): ?>
       <div class="accordion-item"> 
	   <div class="acc-head">
            <?= $item['title'] ?>
        </div>
        <div class="acc-body">

            <? foreach ($item['blocks'] as $itemblock): ?>
                <? $this->includeBlock($itemblock) ?>
		   <? endforeach; ?>
		   </div>  
		</div>
<? endforeach; ?>

</div>
