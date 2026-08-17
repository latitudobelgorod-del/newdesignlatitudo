<? /** @var $block array */ ?>
<? $rand_link = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']); 
$razreshenniye_simvoli = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$rand_link_sp = substr(str_shuffle($razreshenniye_simvoli), 0, 15);
// Возможный вариант результата: WpXq7uTs4ViJaxQ
?>



<? if (!empty($block['title'])): ?>

    <? /* nd-editor-btn — см. комментарий в blocks/.default/button_link.php:
          красная кнопка с бордовым фоном на наведении, как «Оставить заявку» в
          шапке. Ссылку ниже класс не получает: в этой папке блок рисует чипы
          тегов раздела (.tag_ank), у них своё оформление в
          newdesign-catalog.css. */ ?>
    <? if (!empty($block['settings']['form_id'])): ?>
        <div class="block 5545">
            <span class="btn btn-default btn-lg  animate-load nd-editor-btn" data-event="jqm" data-param-form_id="<?=$block['settings']['form_id']?>" data-name="spbutton<?=$block['settings']['form_id']?><?=$rand_link_sp?>" data-nd-form-title="<?=htmlspecialcharsbx($block['title'])?>">
                <span><?=$block['title']?></span>
            </span>
        </div>
    <? elseif (!empty($block['url'])): ?>
        

<div class="tag_ank"><a class="" <? if (!empty($block['target'])): ?>target="<?= $block['target'] ?>" <? endif; ?> href="<?= $block['url'] ?>"><?= $block['title'] ?></a></div>

    <? endif; ?>
<? endif; ?>
