<? /**
 * @var $this   SprintEditorBlocksComponent
 * @var $layout array
 *
 * Если сетка состоит из 1 колонки без оформления, выводим простой список блоков
 * Иначе выводим нормальный шаблон сетки
 * Переделайте этот шаблон на свое усмотрение
 */
$isSimpleGrid = (count($layout['columns']) == 1 && empty($layout['columns'][0]['css']));
$replaceCss = array('latitudo-md-4'=>'col-md-3 col-xs-6', 'latitudo-md-3'=>'col-md-4 col-xs-6');


$searchCss = array_keys($replaceCss);
$replaceCss = array_values($replaceCss);


?>
<? if ($isSimpleGrid) { ?>
    <? foreach ($layout['columns'] as $column) { ?>
        <? foreach ($column['blocks'] as $block) { ?>
            <? $this->includeBlock($block) ?>
        <? } ?>
    <? } ?>
<? } else { ?>
    <div class="sp-container">
        <div class="row">
            <? foreach ($layout['columns'] as $column) { ?>
                <div <? if (!empty($column['css'])) { ?> class="<?= str_replace($searchCss,$replaceCss,$column['css']) ?>"<? } ?>>
                    <? foreach ($column['blocks'] as $block) { ?>
                        <? $this->includeBlock($block) ?>
                    <? } ?>
                </div>
            <? } ?>
        </div>
    </div>
<? } ?>



