<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Страница «Калькулятор террасы» (/info/kalkulyator-terrasy/) — весь калькулятор одним блоком.
 *
 * Подключается так же, как «Вакансии»: элемент инфоблока «Информация», в редакторе блоков
 * компонент bitrix:main.include «из файла», путь
 * ={SITE_DIR."include/newdesign/calc/terrace_calc_page.php"} — однострочная обёртка,
 * которая подключает этот файл (каталог /include вне Git, вёрстка — в репозитории).
 *
 * Стили и скрипт — отдельными файлами, подключаются только здесь. Данные каталога (доски,
 * лаги, кляммеры, опоры, уголки, цены) — снимок в js/newdesign-terrace-calc-data.js.
 * Форма заявки отключена (решение 19.10.2026).
 */
$ndCalcBase = '/local/templates/aspro_next_newdesign'; // фиксированно: калькулятор работает и в старом шаблоне aspro_next
if (!defined('ND_TERRACE_CALC_ASSETS')) {
	define('ND_TERRACE_CALC_ASSETS', true);
	foreach (['css/newdesign-terrace-calc.css', 'js/newdesign-terrace-calc-data.js', 'js/newdesign-terrace-calc.js'] as $ndCalcFile) {
		$ndCalcPath = $_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/'.$ndCalcFile;
		$ndCalcUrl = $ndCalcBase.'/'.$ndCalcFile.'?'.(file_exists($ndCalcPath) ? filemtime($ndCalcPath) : '');
		if (substr($ndCalcFile, -4) === '.css') {
			?><link href="<?=$ndCalcUrl?>" rel="stylesheet"><?
		}
	}
}
?>
<div class="ltd-calc" id="ltdCalc">
  <p class="ltd-lead">Выберите форму и доску из каталога: калькулятор посчитает доски по рядам, лаги, кляммеры и опоры и соберёт смету по ценам сайта. Размеры можно менять прямо на плане.</p>

  <div class="ltd-grid">
    <form class="ltd-form" id="ltdForm" novalidate>
      <section class="ltd-step">
        <div class="ltd-step-h"><b>1</b>Форма террасы</div>
        <div class="ltd-tiles" role="radiogroup" aria-label="Форма террасы" id="ltdShapes"></div>
        <div class="ltd-row2">
          <label class="ltd-field" data-f="L"><span>Длина, м</span><input type="number" id="ltdL" inputmode="decimal" min="0.5" max="50" step="0.1" value="4"><em class="ltd-err"></em></label>
          <label class="ltd-field" data-f="W"><span>Ширина, м</span><input type="number" id="ltdW" inputmode="decimal" min="0.5" max="50" step="0.1" value="3"><em class="ltd-err"></em></label>
        </div>
        <div class="ltd-sub" id="ltdCutBox" hidden>
          <div class="ltd-row2">
            <label class="ltd-field" data-f="a"><span id="ltdALabel">a, м</span><input type="number" id="ltdA" inputmode="decimal" min="0.1" step="0.1" value="2"><em class="ltd-err"></em></label>
            <label class="ltd-field" data-f="b"><span id="ltdBLabel">b, м</span><input type="number" id="ltdB" inputmode="decimal" min="0.1" step="0.1" value="1.5"><em class="ltd-err"></em></label>
          </div>
          <small class="ltd-hint" id="ltdCutHint"></small>
        </div>
        <div class="ltd-field"><span>Направление укладки</span>
          <div class="ltd-tiles" role="radiogroup" aria-label="Направление укладки">
            <label><input type="radio" name="ltdDir" value="len" checked><span><svg viewBox="0 0 48 34" aria-hidden="true"><rect x="2" y="2" width="44" height="30" rx="1"/><path d="M2 9.5h44M2 17h44M2 24.5h44"/></svg>Вдоль длины</span></label>
            <label><input type="radio" name="ltdDir" value="wid"><span><svg viewBox="0 0 48 34" aria-hidden="true"><rect x="2" y="2" width="44" height="30" rx="1"/><path d="M10.8 2v30M19.6 2v30M28.4 2v30M37.2 2v30"/></svg>Вдоль ширины</span></label>
            <label><input type="radio" name="ltdDir" value="diag"><span><svg viewBox="0 0 48 34" aria-hidden="true"><rect x="2" y="2" width="44" height="30" rx="1"/><path d="M2 14L14 2M2 26L26 2M8 32L38 2M20 32L46 6M32 32L46 18"/></svg>Диагональ 45°</span></label>
          </div>
        </div>
        <div class="ltd-field" id="ltdPatternBox"><span>Рисунок укладки</span>
          <div class="ltd-tiles" role="radiogroup" aria-label="Рисунок укладки">
            <label><input type="radio" name="ltdPattern" value="straight"><span><svg viewBox="0 0 48 34" aria-hidden="true"><rect x="2" y="2" width="44" height="30" rx="1"/><path d="M2 9.5h44M2 17h44M2 24.5h44M28 2v30"/></svg>Прямой</span></label>
            <label><input type="radio" name="ltdPattern" value="stagger" checked><span><svg viewBox="0 0 48 34" aria-hidden="true"><rect x="2" y="2" width="44" height="30" rx="1"/><path d="M2 9.5h44M2 17h44M2 24.5h44M30 2v7.5M16 9.5v7.5M30 17v7.5M16 24.5v7.5"/></svg>Вразбежку</span></label>
          </div>
          <small class="ltd-hint" id="ltdPatternHint"></small>
        </div>
      </section>

      <section class="ltd-step">
        <div class="ltd-step-h"><b>2</b>Террасная доска</div>
        <label class="ltd-field"><span>Бренд</span><select class="iks-ignore" id="ltdBrand"></select></label>
        <div class="ltd-row3">
          <label class="ltd-field"><span>Длина доски</span><select class="iks-ignore" id="ltdBLsel"></select></label>
          <label class="ltd-field" data-f="gap"><span>Зазор, мм</span><select class="iks-ignore" id="ltdGap"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5" selected>5</option></select><em class="ltd-err"></em></label>
          <label class="ltd-field" data-f="res"><span>Запас, %</span><input type="number" id="ltdRes" inputmode="decimal" min="0" step="1" value="7"><em class="ltd-err"></em></label>
        </div>
        <small class="ltd-hint" id="ltdResHint"></small>
        <label class="ltd-field" data-f="bl" id="ltdBLwrap" hidden><span>Своя длина, м</span><input type="number" id="ltdBL" inputmode="decimal" min="0.5" step="0.1" value="2.2"><em class="ltd-err"></em></label>
        <div class="ltd-sub" id="ltdCatBox">
          <div class="ltd-pick">
            <input type="text" id="ltdFind" placeholder="Найти доску: модель, цвет, размер" autocomplete="off" aria-label="Найти доску">
            <div class="ltd-list" id="ltdList" role="listbox" aria-label="Доски бренда"></div>
          </div>
          <div class="ltd-prod" id="ltdBoardInfo"></div>
        </div>
        <div class="ltd-sub" id="ltdOwnBox" hidden>
          <div class="ltd-row2">
            <label class="ltd-field" data-f="bw"><span>Ширина доски, мм</span><input type="number" id="ltdBW" inputmode="numeric" min="1" step="1" value="140"><em class="ltd-err"></em></label>
            <label class="ltd-field" data-f="price"><span>Цена за доску, ₽</span><input type="number" id="ltdPrice" inputmode="decimal" min="0" step="1" placeholder="необязательно"><em class="ltd-err"></em></label>
          </div>
        </div>
      </section>

      <section class="ltd-step ltd-compact" id="ltdOpts">
        <div class="ltd-step-h"><b>3</b>Лаги и опоры</div>
        <div class="ltd-optrow">
          <label class="ltd-toggle"><input type="checkbox" id="ltdLagOn" checked>Лаги</label>
          <label class="ltd-field" data-f="step"><span>Шаг, см</span><input type="number" id="ltdStep" inputmode="decimal" min="1" step="1" value="35"><em class="ltd-err"></em></label>
          <label class="ltd-field" id="ltdLagWrap"><span>Модель</span><select class="iks-ignore" id="ltdLag"></select></label>
        </div>
        <small class="ltd-hint" id="ltdStepHint"></small>
        <div class="ltd-optrow">
          <label class="ltd-toggle"><input type="checkbox" id="ltdSupOn">Опоры</label>
          <label class="ltd-field" data-f="h"><span>Высота, мм</span><input type="number" id="ltdH" inputmode="numeric" min="1" step="1" value="60"><em class="ltd-err"></em></label>
          <label class="ltd-field" data-f="sup"><span>Шаг, см</span><input type="number" id="ltdSupStep" inputmode="numeric" min="10" step="5" value="50"><em class="ltd-err"></em></label>
        </div>
        <label class="ltd-field" id="ltdSupBox" hidden><span>Опора</span><select class="iks-ignore" id="ltdSup"></select></label>
        <small class="ltd-hint" id="ltdSupHint">Высота опоры: от основания до низа лаги. Шаг лаг влияет и на число кляммеров.</small>
      </section>
    </form>

    <section class="ltd-out" id="ltdOut" aria-live="polite">
      <div class="ltd-head ltd-dim">
        <div class="ltd-big" id="ltdBoards">—</div>
        <p id="ltdBoardsSub">&nbsp;</p>
        <div class="ltd-sum" id="ltdSumBox" hidden><div>Итого по смете</div><strong id="ltdSum"></strong></div>
      </div>
      <p class="ltd-area ltd-dim" id="ltdArea"></p>

      <div class="ltd-note bad" id="ltdInvalid" hidden></div>

      <div class="ltd-plans ltd-dim">
        <figure class="ltd-main">
          <div class="ltd-tabs" role="tablist" aria-label="Планы">
            <button type="button" role="tab" data-tab="boards" aria-selected="true">План укладки доски</button>
            <button type="button" role="tab" data-tab="joists" aria-selected="false">План лаг</button>
            <button type="button" role="tab" data-tab="sup" aria-selected="false" id="ltdTabSup">План опор</button>
            <button type="button" role="tab" data-tab="all" aria-selected="false">Всё вместе</button>
            <em id="ltdTabCap">тяните за красные точки, чтобы изменить размеры</em>
          </div>
          <div data-pane="boards">
            <svg id="ltdSvgBoards" viewBox="0 0 640 360" role="img" aria-label="План укладки террасной доски"></svg>
            <div class="ltd-tip" id="ltdTip" hidden></div>
            <div class="ltd-legend"><span><i style="background:var(--ltd-board)"></i>доска</span><span><i style="background:var(--ltd-joint);height:3px"></i>стык на лаге</span><span><i style="background:var(--ltd-accent);border-radius:50%;width:8px"></i>изменить размер</span></div>
          </div>
          <div data-pane="joists" hidden>
            <svg id="ltdSvgJoists" viewBox="0 0 400 300" role="img" aria-label="План укладки лаг"></svg>
            <div class="ltd-legend"><span><i style="background:var(--ltd-joist);height:4px"></i>лага</span><span id="ltdJoistCap"></span></div>
          </div>
          <div data-pane="all" hidden>
            <svg id="ltdSvgAll" viewBox="0 0 640 360" role="img" aria-label="Доски, лаги и опоры вместе"></svg>
            <div class="ltd-legend"><span><i style="background:var(--ltd-board);opacity:.55"></i>доска</span><span><i style="background:var(--ltd-joist);height:4px"></i>лага</span><span><i style="background:var(--ltd-support);border-radius:50%;width:8px"></i>опора</span></div>
          </div>
          <div data-pane="sup" hidden>
            <svg id="ltdSvgSup" viewBox="0 0 400 300" role="img" aria-label="Схема расстановки опор"></svg>
            <div class="ltd-legend"><span><i style="background:var(--ltd-support);border-radius:50%;width:8px"></i>опора</span><span id="ltdSupCap"></span></div>
          </div>
        </figure>
      </div>

      <div class="ltd-tbl ltd-dim">
        <table>
          <thead><tr><th>Позиция</th><th style="text-align:right">Кол-во</th><th style="text-align:right">Цена</th><th style="text-align:right">Сумма</th></tr></thead>
          <tbody id="ltdTable"></tbody>
          <tfoot id="ltdFoot"></tfoot>
        </table>
      </div>

      <div class="ltd-note warn" id="ltdWaste" hidden></div>
      <div class="ltd-note info"><b>Стыки досок</b>Стык двух досок должен приходиться на лагу. Под каждым стыком ставится двойная лага, чтобы у каждой доски была своя опора: она уже в смете, на плане лаг отмечена красным.</div>
      <p class="ltd-disclaimer">Расчёт ориентировочный, уточните у менеджера. Цены взяты из каталога сайта.</p>

      <!-- форма заявки отключена по решению 19.10.2026; включить: вернуть разметку #ltdLead -->
    </section>
  </div>
<script src="<?=$ndCalcBase?>/js/newdesign-terrace-calc-data.js?<?=filemtime($_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/js/newdesign-terrace-calc-data.js')?>"></script>
<script src="<?=$ndCalcBase?>/js/newdesign-terrace-calc.js?<?=filemtime($_SERVER['DOCUMENT_ROOT'].$ndCalcBase.'/js/newdesign-terrace-calc.js')?>"></script>
</div>
