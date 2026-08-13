<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Страница «Вакансии» в новом дизайне — весь контент одним блоком.
 *
 * Макет Figma «Чистовик»: десктоп 20493:77227, мобильный 20517:157768.
 * Блоки сверху вниз: обложка с телефоном и двумя кнопками, «Почему стоит
 * присоединиться к нам?», «Миссия компании» на фото, «Наши ценности»,
 * «Наша жизнь» (лента фото), «Вакансии» (аккордеон + колонка HR),
 * плашка HeadHunter с отзывами и нижний баннер «Начать работать легко».
 *
 * Подключается на /info/vakansii/ через редактор блоков контент-менеджера:
 * компонент bitrix:main.include, «из файла», путь
 * ={SITE_DIR."include/newdesign/company/vacancies_page.php"}.
 * Тот файл — однострочная обёртка, которая подключает этот. Разведено так,
 * потому что каталог /include вне Git, а вёрстка должна быть в репозитории
 * (так же сделано у [about_page.php] для /info/company/).
 *
 * Макет нарисован во всю ширину (1440), а страница выводится в правой
 * колонке рядом с меню раздела — поэтому все блоки тянутся по ширине
 * колонки, а «Миссия» вместо выхода на всю ширину экрана получает такой же
 * скруглённый кадр, как остальные плашки.
 *
 * Картинки — images/newdesign/vacancies/ (выгружены из макета),
 * иконки карточек — там же в icons/. Стили и скрипты — отдельными файлами
 * css/newdesign-vacancies.css и js/newdesign-vacancies.js: страница одна,
 * держать её ~1000 строк в общем newdesign.css незачем.
 */

$ndVacImg = SITE_TEMPLATE_PATH.'/images/newdesign/vacancies/';
$ndVacIco = $ndVacImg.'icons/';

/* Телефон и почта HR и ссылка на презентацию — отдельными переменными,
   чтобы менять в одном месте.

   Формы открываются штатным механизмом темы (data-event="jqm"), подпись
   у кнопки лежит прямо внутри span. Заголовок всплывающего окна тема берёт
   из названия веб-формы, поэтому «Заказать звонок» и «Оставить заявку»
   показывали бы «Общая форма» — нужный заголовок задаём атрибутом
   data-nd-form-title, его читает js/newdesign-header.js. */
$ndVacPhone     = '+7 (915) 560-21-35';
$ndVacPhoneHref = '+79155602135';
$ndVacEmail     = 'hr@latitudo.ru';
$ndVacHrName    = 'Ксения Тодераш';
$ndVacPresent   = '/files/outdor_horeca_mobile.pdf';

/* Инфоблок «Вакансии» ищем по символьному коду, а не по номеру: на боевом
   и на локальной копии номер один и тот же, но завязываться на него не стоит.
   Не нашли — секцию со списком просто не выводим. */
$ndVacIblockId = 0;
if (CModule::IncludeModule('iblock')) {
	$arNdVacIblock = CIBlock::GetList(array(), array('CODE' => 'aspro_next_vacancy', 'TYPE' => 'aspro_next_content'))->Fetch();
	if ($arNdVacIblock) {
		$ndVacIblockId = (int)$arNdVacIblock['ID'];
	}
}

/* TODO: подставить реальную страницу компании на hh.ru — в макете ссылки нет.
   Пока ведём на поиск по названию, чтобы кнопка не была битой. */
$ndVacHhUrl = 'https://hh.ru/search/vacancy?text=%D0%9B%D0%B0%D1%82%D0%B8%D1%82%D1%83%D0%B4%D0%BE';

/* Цифры рейтинга взяты из макета. TODO: сверить с карточкой компании на hh. */
$arNdVacHhStats = array(
	array('VALUE' => '92%', 'CAP' => 'сотрудников', 'TEXT' => 'Положительно оценивают компанию и рекомендуют её другим'),
	array('VALUE' => '4,8', 'CAP' => 'общий рейтинг', 'TEXT' => 'Очень хорошо, на основании всех отзывов'),
);

$arNdVacWhy = array(
	array('ICO' => 'vip.svg',    'NAME' => 'Лидеры рынка',            'DESC' => 'Не просто продаем,<br>мы формируем тренды'),
	array('ICO' => 'growth.svg', 'NAME' => 'Стабильно растем',        'DESC' => 'Масштабируемся в 2 раза<br>каждый год'),
	array('ICO' => 'coins.svg',  'NAME' => 'Обеспечиваем перспективу','DESC' => 'Рынок ДПК растет, у нас есть<br>имя и репутация'),
	array('ICO' => 'board.svg',  'NAME' => 'Обучаем и развиваем',     'DESC' => 'Научим необходимому, от продукта<br>до сложных продаж'),
);

$arNdVacValues = array(
	array('ICO' => 'smile.svg',  'NAME' => 'Клиент',            'DESC' => 'Применяем индивидуальный подход, строим доверительные, честные и уважительные отношения, обеспечивая высокий уровень сервиса'),
	array('ICO' => 'users.svg',  'NAME' => 'Команда',           'DESC' => 'Объединяем усилия для сопровождения клиентов на всех этапах в единой экосистеме'),
	array('ICO' => 'target.svg', 'NAME' => 'Результативность',  'DESC' => 'Берем ответственность за достижение амбициозных целей и обеспечиваем положительные результаты'),
	array('ICO' => 'heart.svg',  'NAME' => 'Доверие и поддержка','DESC' => 'Создаем атмосферу сотрудничества, взаимопомощи и открытости, поддерживая друг друга и наших клиентов'),
);

/* Лента «Наша жизнь». Первые три кадра — те, что стоят в макете, остальные
   добрали из фотографий той же подборки, чтобы у ленты была прокрутка. */
$arNdVacLife = array(
	array('FILE' => 'life-1.jpg', 'ALT' => 'Команда Латитудо в шоу-руме'),
	array('FILE' => 'life-2.jpg', 'ALT' => 'Сотрудники Латитудо на отраслевой выставке'),
	array('FILE' => 'life-3.jpg', 'ALT' => 'Работа с клиентом в офисе Латитудо'),
	array('FILE' => 'life-4.jpg', 'ALT' => 'Коллектив офиса Латитудо'),
	array('FILE' => 'life-5.jpg', 'ALT' => 'Менеджеры Латитудо с образцами продукции'),
	array('FILE' => 'life-6.jpg', 'ALT' => 'Рабочий день в офисе Латитудо'),
);

/* TODO: заменить на реальные отзывы с hh.ru — сейчас это текст из макета.
   Пустой массив просто убирает карусель, оставляя цифры и кнопку. */
$arNdVacReviews = array(
	array('POST' => 'Менеджер по работе с клиентами', 'RATE' => '4,5', 'DATE' => '19 дек 2025', 'TEXT' => 'Очень хорошие условия труда. Приятный чистый офис, понятная мотивация, много входящих заявок. Руководство супер, всегда поддержат и готовы решать проблемы. Регулярные корпоративы.'),
	array('POST' => 'Менеджер по продажам',           'RATE' => '5,0', 'DATE' => '02 ноя 2025', 'TEXT' => 'Стабильные выплаты два раза в месяц, прозрачная система процентов. Продукт понятный, обучение с первого дня, наставник закреплён за новичком.'),
	array('POST' => 'Специалист отдела сервиса',      'RATE' => '4,5', 'DATE' => '14 авг 2025', 'TEXT' => 'Компания растёт, задач много и они интересные. Есть куда развиваться внутри: коллеги переходят в смежные направления и на руководящие позиции.'),
	array('POST' => 'Менеджер по продажам',           'RATE' => '4,0', 'DATE' => '27 май 2025', 'TEXT' => 'Хороший офис в шаговой доступности, дружный коллектив. Входящий поток лидов действительно есть, холодных обзвонов нет.'),
);

/* Стили и скрипт страницы — отдельными файлами. Включаемая область попадает
   в тело документа, где SetAdditionalCSS уже не сработает, поэтому тег пишем
   руками с меткой времени файла вместо версии (так же сделано в
   catalog.section/catalog_blockcolors_newdesign). Защита от повторного
   вывода — на случай, если блок вставят на страницу дважды. */
if (!defined('ND_VACANCIES_ASSETS')) {
	define('ND_VACANCIES_ASSETS', true);
	$ndVacCssPath = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/css/newdesign-vacancies.css';
	$ndVacJsPath  = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-vacancies.js';
	?>
	<link href="<?=SITE_TEMPLATE_PATH?>/css/newdesign-vacancies.css?<?=(file_exists($ndVacCssPath) ? filemtime($ndVacCssPath) : '')?>" rel="stylesheet">
	<script src="<?=SITE_TEMPLATE_PATH?>/js/newdesign-vacancies.js?<?=(file_exists($ndVacJsPath) ? filemtime($ndVacJsPath) : '')?>" defer></script>
	<?
}
?>
<div class="nd-vac">

	<?// Обложка: фото шоу-рума, затемнение градиентом, телефон и две кнопки.?>
	<section class="nd-vac__hero">
		<img class="nd-vac__hero-img" src="<?=$ndVacImg?>hero.jpg" alt="Шоу-рум Латитудо" width="1280" height="960">
		<div class="nd-vac__hero-body">
			<div class="nd-vac__hero-text">
				<div class="nd-vac__hero-title">Стройте с нами<br>красивую жизнь</div>
				<p class="nd-vac__hero-desc">Латитудо – это красивые и уютные пространства для жизни, современные технологии, экологические материалы и качественный сервис, а еще это рабочее пространство для наших сотрудников!</p>
			</div>
			<div class="nd-vac__hero-contact">
				<div class="nd-vac__label">Свяжитесь с нами</div>
				<a class="nd-vac__phone" href="tel:<?=$ndVacPhoneHref?>"><?=$ndVacPhone?></a>
			</div>
			<div class="nd-vac__hero-btns">
				<span class="nd-vac__btn nd-vac__btn--red animate-load" data-event="jqm" data-param-form_id="RESUME" data-name="ndVacResume" data-nd-form-title="Отправить резюме">Отправить резюме</span>
				<a class="nd-vac__btn nd-vac__btn--white" href="<?=$ndVacPresent?>" target="_blank">
					<svg class="nd-vac__btn-ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M12 4v11m0 0 4.5-4.5M12 15l-4.5-4.5M4.5 17.5V19a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5v-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span>Презентация компании</span>
				</a>
			</div>
		</div>
	</section>

	<section class="nd-vac__sec">
		<h2 class="nd-vac__h2">Почему стоит присоединиться к нам?</h2>
		<div class="nd-vac__why">
			<?foreach($arNdVacWhy as $arCard):?>
				<div class="nd-vac__card">
					<img class="nd-vac__card-ico" src="<?=$ndVacIco.$arCard['ICO']?>" alt="" width="96" height="96" loading="lazy">
					<div class="nd-vac__card-name"><?=$arCard['NAME']?></div>
					<div class="nd-vac__card-desc"><?=$arCard['DESC']?></div>
				</div>
			<?endforeach;?>
		</div>
		<a class="nd-vac__btn nd-vac__btn--red nd-vac__btn--wide" href="#nd-vac-list">
			<svg class="nd-vac__btn-ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
				<path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
			</svg>
			<span>Стать частью команды</span>
		</a>
	</section>

	<?// «Миссия компании»: фото с затемнением и текстом поверх.?>
	<section class="nd-vac__mission">
		<img class="nd-vac__mission-img" src="<?=$ndVacImg?>mission.jpg" alt="Терраса Латитудо" width="1920" height="1440" loading="lazy">
		<div class="nd-vac__mission-body">
			<h2 class="nd-vac__h1">Миссия компании</h2>
			<p class="nd-vac__mission-text">Создаем красивые и уютные пространства для жизни, воплощая мечты клиентов в реальность с помощью инновационных технологий, экологичных материалов и безупречного сервиса</p>
		</div>
	</section>

	<section class="nd-vac__sec">
		<h2 class="nd-vac__h2">Наши ценности</h2>
		<div class="nd-vac__values">
			<div class="nd-vac__values-grid">
				<?foreach($arNdVacValues as $arCard):?>
					<div class="nd-vac__card nd-vac__card--value">
						<img class="nd-vac__card-ico" src="<?=$ndVacIco.$arCard['ICO']?>" alt="" width="96" height="96" loading="lazy">
						<div class="nd-vac__card-name"><?=$arCard['NAME']?></div>
						<div class="nd-vac__card-desc"><?=$arCard['DESC']?></div>
					</div>
				<?endforeach;?>
			</div>
			<img class="nd-vac__values-img" src="<?=$ndVacImg?>values.jpg" alt="Команда Латитудо" width="1000" height="880" loading="lazy">
		</div>
	</section>

	<?// «Наша жизнь»: лента фото со сдвигом на карточку.?>
	<section class="nd-vac__sec">
		<h2 class="nd-vac__h2">Наша жизнь</h2>
		<div class="nd-vac__slider" data-nd-vac-slider>
			<div class="nd-vac__rail" data-nd-vac-rail>
				<?foreach($arNdVacLife as $arPhoto):?>
					<div class="nd-vac__life-item">
						<img src="<?=$ndVacImg.$arPhoto['FILE']?>" alt="<?=$arPhoto['ALT']?>" loading="lazy">
					</div>
				<?endforeach;?>
			</div>
			<button class="nd-vac__rail-arrow nd-vac__rail-arrow--prev" type="button" data-nd-vac-prev aria-label="Предыдущее фото">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button class="nd-vac__rail-arrow nd-vac__rail-arrow--next" type="button" data-nd-vac-next aria-label="Следующее фото">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>
	</section>

	<?// Список вакансий и колонка HR.?>
	<section class="nd-vac__sec" id="nd-vac-list">
		<h2 class="nd-vac__h1">Вакансии</h2>
		<div class="nd-vac__cols">

			<?// Вакансии подтягиваются из инфоблока «Вакансии» и группируются по
			   // городам — городом служит раздел инфоблока. Раньше страницу собирали
			   // руками в редакторе блоков: заголовок «Филиал Москва» плюс блок
			   // «Инфоблоки. Элементы» с перечисленными вакансиями, и так на каждый
			   // город. Разметка аккордеона — в шаблоне vacancies_newdesign.?>
			<?if($ndVacIblockId):?>
			<?$APPLICATION->IncludeComponent(
				'bitrix:news.list',
				'vacancies_newdesign',
				array(
					'IBLOCK_TYPE' => 'aspro_next_content',
					'IBLOCK_ID' => $ndVacIblockId,
					'NEWS_COUNT' => '100',
					'SORT_BY1' => 'SORT',
					'SORT_ORDER1' => 'ASC',
					'SORT_BY2' => 'ID',
					'SORT_ORDER2' => 'ASC',
					'FIELD_CODE' => array('ID', 'NAME', 'IBLOCK_SECTION_ID', ''),
					'PROPERTY_CODE' => array('PAY', 'EDITOR1', ''),
					'CHECK_DATES' => 'Y',
					'DETAIL_URL' => '',
					'AJAX_MODE' => 'N',
					// Кэш выключен намеренно: тело пункта рисует sprint.editor:blocks
					// уже внутри шаблона, и при включённом кэше правка вакансии в
					// админке не доезжала бы до страницы до сброса кэша.
					'CACHE_TYPE' => 'N',
					'CACHE_TIME' => '36000000',
					'CACHE_FILTER' => 'N',
					'CACHE_GROUPS' => 'N',
					'PREVIEW_TRUNCATE_LEN' => '',
					'ACTIVE_DATE_FORMAT' => 'd.m.Y',
					'SET_TITLE' => 'N',
					'SET_STATUS_404' => 'N',
					'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
					'ADD_SECTIONS_CHAIN' => 'N',
					'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
					'PARENT_SECTION' => '',
					'PARENT_SECTION_CODE' => '',
					'INCLUDE_SUBSECTIONS' => 'Y',
					'DISPLAY_TOP_PAGER' => 'N',
					'DISPLAY_BOTTOM_PAGER' => 'N',
					'PAGER_TITLE' => '',
					'PAGER_SHOW_ALWAYS' => 'N',
					'SET_BROWSER_TITLE' => 'N',
					'SET_META_KEYWORDS' => 'N',
					'SET_META_DESCRIPTION' => 'N',
					'SET_LAST_MODIFIED' => 'N',
					'PAGER_BASE_LINK_ENABLE' => 'N',
				),
				false
			);?>
			<?endif;?>

			<aside class="nd-vac__aside">
				<div class="nd-vac__hr">
					<div class="nd-vac__hr-top">
						<div class="nd-vac__hr-title">Приглашаем сотрудников<br>в офисы компании</div>
						<p class="nd-vac__hr-cities">в Москве, Воронеже, Белгороде,<br>Ростове-на-Дону и Краснодаре.</p>
					</div>
					<p class="nd-vac__hr-note">Для кандидатов из других городов обсуждаем индивидуальные условия и релокационный пакет.</p>
					<div class="nd-vac__hr-person">
						<img class="nd-vac__hr-photo" src="<?=$ndVacImg?>hr.jpg" alt="<?=$ndVacHrName?>" width="334" height="334" loading="lazy">
						<div class="nd-vac__hr-name"><?=$ndVacHrName?></div>
						<a class="nd-vac__hr-link" href="tel:<?=$ndVacPhoneHref?>">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.6 3.5 9 3.9l1 3.6-1.9 1.4a11 11 0 0 0 6 6l1.4-1.9 3.6 1 .4 2.4a2 2 0 0 1-2 2.3A15.5 15.5 0 0 1 4.3 5.5a2 2 0 0 1 2.3-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
							<span><?=$ndVacPhone?></span>
						</a>
						<a class="nd-vac__hr-link" href="mailto:<?=$ndVacEmail?>">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
							<span><?=$ndVacEmail?></span>
						</a>
					</div>
					<span class="nd-vac__btn nd-vac__btn--ghost nd-vac__btn--wide nd-vac__btn--sm animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="ndVacCall" data-nd-form-title="Заказать звонок">Заказать звонок</span>
				</div>

				<div class="nd-vac__ask">
					<div class="nd-vac__ask-title">Не нашли подходящую вакансию?</div>
					<p class="nd-vac__ask-text">Остались вопросы или хотите индивидуальное предложение — оставьте заявку на обратный звонок или обращайтесь по контактам</p>
					<span class="nd-vac__btn nd-vac__btn--red nd-vac__btn--wide animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="ndVacRequest" data-nd-form-title="Оставить заявку">Оставить заявку</span>
				</div>
			</aside>

		</div>
	</section>

	<?// Плашка HeadHunter: цифры рейтинга и отзывы сотрудников.?>
	<section class="nd-vac__hh">
		<div class="nd-vac__hh-top">
			<div class="nd-vac__hh-lead">
				<div class="nd-vac__hh-title">Вакансии на сайте <a href="<?=$ndVacHhUrl?>" target="_blank" rel="nofollow noopener">HeadHunter</a></div>
				<p class="nd-vac__hh-note">Обращаем внимание, что не всегда вакансии на hh есть — на сайте всё актуально</p>
			</div>
			<?foreach($arNdVacHhStats as $arStat):?>
				<div class="nd-vac__hh-stat">
					<div class="nd-vac__hh-stat-top">
						<span class="nd-vac__hh-value"><?=$arStat['VALUE']?></span>
						<span class="nd-vac__hh-cap"><?=$arStat['CAP']?></span>
					</div>
					<div class="nd-vac__hh-stat-text"><?=$arStat['TEXT']?></div>
				</div>
			<?endforeach;?>
		</div>

		<?if($arNdVacReviews):?>
			<div class="nd-vac__hh-reviews">
				<div class="nd-vac__hh-head">
					<h2 class="nd-vac__h2 nd-vac__h2--white">Что говорят сотрудники</h2>
					<div class="nd-vac__nav" data-nd-vac-nav>
						<span class="nd-vac__nav-count" data-nd-vac-count></span>
						<button class="nd-vac__nav-btn" type="button" data-nd-vac-prev aria-label="Предыдущий отзыв">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<button class="nd-vac__nav-btn" type="button" data-nd-vac-next aria-label="Следующий отзыв">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
				</div>
				<div class="nd-vac__slider" data-nd-vac-slider>
					<div class="nd-vac__rail nd-vac__rail--reviews" data-nd-vac-rail>
						<?foreach($arNdVacReviews as $arRev):?>
							<div class="nd-vac__rev">
								<div class="nd-vac__rev-post"><?=$arRev['POST']?></div>
								<div class="nd-vac__rev-meta">
									<span class="nd-vac__rev-rate"><?=$arRev['RATE']?>
										<svg width="19" height="19" viewBox="0 0 24 24" fill="#f5b301" aria-hidden="true"><path d="m12 3 2.7 5.4 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.3l6-.9L12 3Z"/></svg>
										оценка</span>
									<span class="nd-vac__rev-date"><?=$arRev['DATE']?></span>
								</div>
								<p class="nd-vac__rev-text"><?=$arRev['TEXT']?></p>
							</div>
						<?endforeach;?>
					</div>
				</div>
			</div>
		<?endif;?>

		<a class="nd-vac__btn nd-vac__btn--red nd-vac__btn--wide" href="<?=$ndVacHhUrl?>" target="_blank" rel="nofollow noopener"><span>Читать отзывы на HeadHunter</span></a>
	</section>

	<?// Нижний баннер: групповое фото, телефон и повтор призыва.?>
	<section class="nd-vac__cta">
		<img class="nd-vac__cta-img" src="<?=$ndVacImg?>cta.jpg" alt="Команда Латитудо" width="1920" height="1280" loading="lazy">
		<div class="nd-vac__cta-body">
			<div class="nd-vac__hero-text">
				<div class="nd-vac__hero-title">Начать работать в<br>нашей команде легко</div>
				<p class="nd-vac__hero-desc">Свяжитесь с нами любым удобным способом, с радостью ответим.</p>
			</div>
			<div class="nd-vac__hero-contact">
				<div class="nd-vac__label">Свяжитесь с нами</div>
				<a class="nd-vac__phone" href="tel:<?=$ndVacPhoneHref?>"><?=$ndVacPhone?></a>
			</div>
		</div>
	</section>

</div>
