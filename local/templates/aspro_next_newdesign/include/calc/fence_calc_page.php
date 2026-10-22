<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Страница «Калькулятор ограждений» (/info/kalkulyator-ograzhdeniy/), 21.10.2026.
 *
 * Подключается так же, как калькулятор террасы: элемент инфоблока «Информация» (ИБ 25), в редакторе
 * блоков компонент bitrix:main.include «из файла», путь
 * ={SITE_DIR."include/newdesign/calc/fence_calc_page.php"} — однострочная обёртка вне git,
 * которая подключает этот файл.
 *
 * Внешний вид — стили калькулятора террасы (классы ltd-*), свои дополнения — css/newdesign-fence-calc.css.
 * Данные каталога (серии, цвета, детали, цены) — снимок js/newdesign-fence-calc-data.js,
 * пересборка — tools/fence-calc/build.py. Правила сборки серий — RULES в js/newdesign-fence-calc.js.
 * «Рассчитать с менеджером» — та же общая форма и тот же обработчик PDF, что у террасы (data.kind = 'fence').
 *
 * 22.10.2026: открыт всем (до этого — только администраторам, остальным 404). Ссылки: меню шапки,
 * подвал «Клиентам», кнопка в карточках раздела «Ограждения из ДПК» (?series=).
 */

$ndCalcBase = '/local/templates/aspro_next_newdesign';

// Серии — плитками, как подразделы на странице «Ограждения из ДПК»: название и картинка раздела каталога
// (ИБ 19, ветка раздела 104). Плитку без картинки калькулятор рисует с фото столба серии.
$ndFenceSections = [];
if (\Bitrix\Main\Loader::includeModule('iblock') && ($ndFenceRoot = CIBlockSection::GetByID(104)->Fetch())) {
	$ndRes = CIBlockSection::GetList([], ['IBLOCK_ID' => 19, '>LEFT_MARGIN' => $ndFenceRoot['LEFT_MARGIN'], '<RIGHT_MARGIN' => $ndFenceRoot['RIGHT_MARGIN']], false, ['ID', 'NAME', 'PICTURE']);
	while ($ndSec = $ndRes->Fetch()) {
		$ndImg = $ndSec['PICTURE'] ? CFile::ResizeImageGet($ndSec['PICTURE'], ['width' => 240, 'height' => 160], BX_RESIZE_IMAGE_PROPORTIONAL) : null;
		$ndFenceSections[(int)$ndSec['ID']] = ['n' => trim(preg_replace('/\s+/u', ' ', $ndSec['NAME'])), 'img' => $ndImg ? $ndImg['src'] : ''];
	}
}
foreach (['css/newdesign-terrace-calc.css', 'css/newdesign-fence-calc.css'] as $ndCalcFile) {
	$ndCalcPath = $_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/'.$ndCalcFile;
	?><link href="<?=$ndCalcBase.'/'.$ndCalcFile.'?'.(file_exists($ndCalcPath) ? filemtime($ndCalcPath) : '')?>" rel="stylesheet"><?
}
?>
<div class="ltd-calc lfc" id="lfcCalc">
  <p class="ltd-lead">Укажите длину ограждения в погонных метрах и высоту, выберите вид ограждения и серию из каталога: калькулятор расставит столбы, посчитает перила, балясины, крепёж, крышки и соберёт смету по ценам сайта.</p>

  <div class="ltd-grid">
    <form class="ltd-form" id="lfcForm" novalidate>
      <section class="ltd-step">
        <div class="ltd-step-h"><b>1</b>Ограждение</div>
        <label class="ltd-toggle"><input type="checkbox" id="lfcSkirt" checked>Юбки на столбы</label>
        <div class="lfc-ends" role="radiogroup" aria-label="По краям"><span>По краям:</span>
          <label class="ltd-toggle"><input type="radio" name="lfcEnds" value="posts" checked>столбы ДПК</label>
          <label class="ltd-toggle"><input type="radio" name="lfcEnds" value="walls">блоки или стены</label>
        </div>
        <div class="ltd-row2">
          <label class="ltd-field" data-f="L"><span id="lfcLLabel">Длина ограждения, пог. м</span><input type="number" id="lfcL" inputmode="decimal" min="0.5" max="500" step="0.1" value="6"><em class="ltd-err"></em></label>
          <label class="ltd-field" data-f="step"><span>Шаг столбов, м</span><input type="number" id="lfcStep" inputmode="decimal" min="0.5" step="0.05" value="1.5"><em class="ltd-err"></em></label>
        </div>
        <div class="ltd-row2">
          <label class="ltd-field" data-f="N" id="lfcNField" style="display:none"><span>Количество проёмов</span><input type="number" id="lfcN" inputmode="numeric" min="1" max="100" step="1" value="1"><em class="ltd-err"></em></label>
          <label class="ltd-field" data-f="H"><span>Высота ограждения, мм</span><input type="number" id="lfcH" inputmode="numeric" min="600" max="1500" step="10" value="1100"><em class="ltd-err"></em></label>
        </div>
        <small class="ltd-hint" id="lfcStepHint"></small>
        <div class="ltd-field"><span>Вид ограждения</span>
          <div class="ltd-tiles lfc-types" role="radiogroup" aria-label="Вид ограждения" id="lfcTypes"></div>
        </div>
      </section>

      <section class="ltd-step">
        <div class="ltd-step-h"><b>2</b>Серия ограждения</div>
        <label class="ltd-field"><span>Производитель</span><select class="iks-ignore" id="lfcBrand"></select></label>
        <div class="ltd-tiles lfc-series" role="radiogroup" aria-label="Серии ограждений" id="lfcList"></div>
        <label class="ltd-field"><span>Цвет</span><select class="iks-ignore" id="lfcColor"></select></label>
        <div class="lfc-opts" id="lfcOpts"></div>
        <div class="ltd-prod" id="lfcSeriesInfo"></div>
      </section>
    </form>

    <section class="ltd-out" id="lfcOut" aria-live="polite">
      <div class="ltd-head ltd-dim">
        <div class="ltd-big" id="lfcBig">—</div>
        <p id="lfcBigSub">&nbsp;</p>
        <div class="ltd-sum" id="lfcSumBox" hidden><div>Итого по смете</div><strong id="lfcSum"></strong></div>
      </div>

      <div class="ltd-note bad" id="lfcInvalid" hidden></div>

      <div class="ltd-plans ltd-dim">
        <figure class="ltd-main">
          <div class="ltd-tabs">
            <span class="lfc-cap">Схема секций</span>
            <button type="button" class="ltd-zoom" id="lfcZoom" title="Увеличить" aria-label="Увеличить схему"></button>
            <em id="lfcCap"></em>
          </div>
          <svg id="lfcSvg" viewBox="0 0 640 300" role="img" aria-label="Схема секций ограждения"></svg>
          <div class="ltd-legend"><span><i style="background:var(--ltd-joist);height:8px"></i>столб</span><span><i style="background:var(--ltd-board);height:4px"></i>перила, поручень</span><span><i style="background:var(--ltd-board-2);width:4px;height:12px"></i>балясина</span><span id="lfcLegendNote"></span></div>
        </figure>
      </div>

      <div class="ltd-tbl ltd-dim">
        <table>
          <thead><tr><th>Позиция</th><th style="text-align:right">Кол-во</th><th style="text-align:right">Цена</th><th style="text-align:right">Сумма</th></tr></thead>
          <tbody id="lfcTable"></tbody>
          <tfoot id="lfcFoot"></tfoot>
        </table>
      </div>

      <div class="ltd-cart">
        <button type="button" class="ltd-btn" id="lfcCart">Добавить всё в корзину</button>
        <span class="ltd-hint" id="lfcCartMsg" aria-live="polite">Положим в корзину все отмеченные позиции сметы с ценой в нужном количестве — заказ можно оформить сразу.</span>
      </div>

      <div class="ltd-note info" id="lfcRules"></div>
      <p class="ltd-disclaimer">Расчёт ориентировочный, уточните у менеджера. Цены взяты из каталога сайта. Метизы (саморезы, анкеры) в смету не входят.</p>

      <div class="ltd-pdf">
        <?/* Общая форма MAINFORM, как у калькулятора террасы: по подписи «Рассчитать с менеджером» сервер
             прикладывает PDF расчёта к письму и лиду (latitudo_terrace_calc_crm.php). */?>
        <button type="button" class="ltd-btn animate-load" id="lfcPdfOpen" data-event="jqm" data-param-form_id="MAINFORM" data-name="question" data-nd-form-title="Рассчитать с менеджером">Рассчитать с менеджером</button>
        <span class="ltd-hint">Отправим ваш расчёт менеджеру: он проверит смету, пришлёт PDF со схемой и ответит на вопросы.</span>
      </div>
    </section>
  </div>
<script>window.LFC_LEAD_URL = '/local/ajax/nd_terrace_calc_lead.php'; window.LFC_IMG = '<?=$ndCalcBase?>/images/fence-calc/'; window.LFC_SECTIONS = <?=json_encode($ndFenceSections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)?>;</script>
<script src="<?=$ndCalcBase?>/js/newdesign-fence-calc-data.js?<?=filemtime($_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/js/newdesign-fence-calc-data.js')?>"></script>
<script src="<?=$ndCalcBase?>/js/newdesign-calc-basket.js?<?=filemtime($_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/js/newdesign-calc-basket.js')?>"></script>
<script src="<?=$ndCalcBase?>/js/newdesign-fence-calc.js?<?=filemtime($_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/js/newdesign-fence-calc.js')?>"></script>
</div>
