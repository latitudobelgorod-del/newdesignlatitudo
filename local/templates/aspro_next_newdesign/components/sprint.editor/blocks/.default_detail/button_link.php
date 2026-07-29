<? /** @var $block array */ ?>
<? $rand_link = '_'.md5($_SERVER['REQUEST_TIME_FLOAT']); 
$razreshenniye_simvoli = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$rand_link_sp = substr(str_shuffle($razreshenniye_simvoli), 0, 15);
// Возможный вариант результата: WpXq7uTs4ViJaxQ
?>





<? if (!empty($block['title'])): ?>

    <? if (!empty($block['settings']['form_id'])): ?>
        <div class="block">
            <span class="btn btn-default btn-lg  animate-load" data-event="jqm" data-param-form_id="<?=$block['settings']['form_id']?>" data-name="spbutton<?=$block['settings']['form_id']?><?=$rand_link_sp?>">
                <span><?=$block['title']?></span>
            </span>
        </div>
    <? elseif (!empty($block['url'])): ?>
        <a class=" editor_btn btn-lg w_icons btn btn-default transition_bg" <? if (!empty($block['target'])): ?>target="<?= $block['target'] ?>" <? endif; ?> href="<?= $block['url'] ?>"><?= $block['title'] ?></a>
    <? endif; ?>
<? endif; ?>

