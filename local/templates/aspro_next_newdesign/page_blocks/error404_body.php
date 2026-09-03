<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Разметка страницы «Страница не найдена» — новый дизайн.
 *
 * Используется в двух местах, и это единственный экземпляр вёрстки:
 *   - корневой /404.php — обычные ненайденные адреса (файл вне git, там только
 *     подключение этого);
 *   - обработчик ndFakePagination404 в local/init.php — ложные адреса
 *     пагинации вида ?PAGEN_5=17 (он срабатывает после отрисовки и подставляет
 *     этот блок вместо содержимого страницы).
 *
 * Раньше вёрстка жила прямо в 404.php и была из старого дизайна: синяя кнопка
 * темы, узкая колонка, крупная картинка-цифра поверх текста. Переписана под
 * новый дизайн (Ирина, 3 сентября 2026).
 *
 * Стили — .nd-e404 в css/newdesign.css.
 */
?>
<div class="nd-e404">
	<div class="nd-e404__code" aria-hidden="true">404</div>

	<h1 class="nd-e404__title">Страница не найдена</h1>
	<p class="nd-e404__text">
		Возможно, адрес набран с опечаткой, страница была удалена или
		переехала. Начните с главной или посмотрите каталог.
	</p>

	<div class="nd-e404__actions">
		<a class="nd-e404__btn" href="<?=SITE_DIR?>">На главную</a>
		<a class="nd-e404__btn nd-e404__btn--ghost" href="<?=SITE_DIR?>catalog/">В каталог</a>
	</div>

	<?/* Частые разделы: человеку, попавшему на битую ссылку, проще уйти сразу
	   в нужное место, чем возвращаться и искать в меню. */?>
	<div class="nd-e404__links">
		<span class="nd-e404__links-title">Куда ещё можно перейти</span>
		<div class="nd-e404__chips">
			<a class="nd-e404__chip" href="<?=SITE_DIR?>catalog/terrasnaya-doska-iz-dpk/">Террасная доска</a>
			<a class="nd-e404__chip" href="<?=SITE_DIR?>catalog/ograzhdeniya-iz-dpk/">Ограждения</a>
			<a class="nd-e404__chip" href="<?=SITE_DIR?>catalog/komplektuyushie/">Комплектующие</a>
			<a class="nd-e404__chip" href="<?=SITE_DIR?>services/">Услуги и работы</a>
			<a class="nd-e404__chip" href="<?=SITE_DIR?>projects/">Портфолио</a>
			<a class="nd-e404__chip" href="<?=SITE_DIR?>contacts/">Контакты</a>
		</div>
	</div>
</div>
