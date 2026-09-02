<?php
/**
 * Быстрый подбор товаров каталога по неполным словам.
 *
 * Ищем по названию товара, его артикулу и производителю, а также по названиям
 * и артикулам его торговых предложений (инфоблоки 19 и 20). Слов может быть
 * несколько, каждое ищется как подстрока и в любом порядке — «террас лати
 * графит» находит «Террасная доска Latitudo … Графит».
 *
 * Штатный поиск Битрикса для этого не годится: он работает по индексу
 * словоформ и ищет только целые слова, поэтому «террас» не находил
 * «Террасную», а артикул торгового предложения не находился вовсе.
 *
 * Устройство. Один раз собираем в кеш «сеновал» — строку уникальных
 * нормализованных слов на каждый товар — и дальше перебираем её в PHP.
 * Прямой SQL с несколькими LIKE и присоединёнными предложениями занимал на
 * этом каталоге ~90 мс на запрос, перебор кеша — доли миллисекунды.
 *
 * Ключ кеша содержит количество элементов и максимальный TIMESTAMP_X
 * инфоблоков 19 и 20 (запрос на 3 мс), поэтому после правки товара или обмена
 * с 1С кеш перестраивается сам — отдельных обработчиков событий не нужно.
 *
 * Пользуются: подсказки в шапке (result_modifier.php шаблонов search.title
 * newdesign и newdesign_mobile) и страница выдачи /catalog/?q= (шаблон
 * catalog.search/main).
 */

if (!class_exists('LatitudoQuickSearch')) {

class LatitudoQuickSearch
{
	/** Каталог товаров и торговые предложения. */
	const IBLOCK_PRODUCTS = 19;
	const IBLOCK_OFFERS   = 20;

	/** CML2_ARTICLE (артикул товара), BRAND (производитель — элемент ИБ 12). */
	const PROP_ARTICLE = 145;
	const PROP_BRAND   = 134;
	/** ARTICLE (артикул предложения), CML2_LINK (предложение → товар). */
	const PROP_OFFER_ARTICLE = 307;
	const PROP_OFFER_LINK    = 167;

	/** Свои марки идут в подсказках первыми: сначала EasyDecking, следом
	   LATITUDO, потом все остальные (Ирина, 2 сентября 2026). Ключи — ID
	   элементов справочника брендов (ИБ 12), значения — вес сортировки. */
	private static $brandWeight = array(
		26365 => 1, // EasyDecking
		24936 => 2, // LATITUDO
	);
	/** Вес для всех прочих марок и товаров без марки. */
	const BRAND_WEIGHT_OTHER = 3;

	/** Короче двух символов не ищем: выдача была бы бессмысленной. */
	const MIN_QUERY_LEN = 2;
	/** Больше шести слов в запросе не бывает, остальное отбрасываем. */
	const MAX_WORDS = 6;
	const MAX_WORD_LEN = 30;

	const CACHE_TTL  = 86400;
	const CACHE_PATH = '/latitudo/quick_search';

	private static $index = null;
	private static $articles = null;

	/** Нижний регистр и «ё» → «е». Дальше этой формы работает транслитерация. */
	public static function normalizeBase($text)
	{
		return strtr(mb_strtolower((string)$text, 'UTF-8'), array('ё' => 'е'));
	}

	/**
	 * Схлопывает в звёздочку латинскую «x», кириллическую «х» и знак умножения:
	 * размеры в каталоге записаны как «146х23х3010», а печатают их и
	 * «146x23x3010», и «146*23*3010». Замена посимвольная и делается по обе
	 * стороны сравнения, поэтому обычные слова («верх», «extra») от неё не
	 * страдают — они меняются одинаково.
	 */
	public static function foldSizes($text)
	{
		return strtr((string)$text, array('x' => '*', 'х' => '*', '×' => '*'));
	}

	/** Приводит строку к виду, в котором сравниваем. */
	public static function normalize($text)
	{
		return self::foldSizes(self::normalizeBase($text));
	}

	/**
	 * Разбирает запрос на слова. Возвращает слова в базовой форме (без
	 * схлопывания размеров) — из неё variants() делает варианты для сравнения.
	 */
	public static function splitQuery($query)
	{
		$parts = preg_split('/[^\p{L}\p{N}*.\/-]+/u', self::normalizeBase($query), -1, PREG_SPLIT_NO_EMPTY);
		if (!$parts) {
			return array();
		}

		$words = array();
		foreach ($parts as $part) {
			// Точки и дефисы по краям — это пунктуация, внутри слова («146-23»,
			// «H1.5») они значимы, поэтому режем только края.
			$part = trim($part, "-./");
			if ($part === '') {
				continue;
			}
			if (mb_strlen($part, 'UTF-8') > self::MAX_WORD_LEN) {
				$part = mb_substr($part, 0, self::MAX_WORD_LEN, 'UTF-8');
			}
			$words[$part] = $part;
			if (count($words) >= self::MAX_WORDS) {
				break;
			}
		}

		return array_values($words);
	}

	/**
	 * Группы равнозначных написаний — то, что транслитерация буква в букву не
	 * берёт, потому что записано по звучанию. Слово из запроса подтягивает все
	 * остальные формы своей группы.
	 *
	 * Таблицу пополняем руками по мере жалоб: «изи» вместо EasyDecking,
	 * «вуд» вместо Wood в «Вуд-Икс» и так далее. Марки, которые переводятся
	 * побуквенно (латитудо → latitudo, хилст → hilst, левел → level,
	 * милларго → millargo, поливан → polivan, легро → legro, бругган →
	 * bruggan), сюда вносить не нужно — их берёт transliterate.
	 */
	private static $synonymGroups = array(
		array('изи', 'easy'),
		array('изидекинг', 'изидэкинг', 'easydecking'),
		array('декинг', 'дэкинг', 'decking'),
		array('вуд', 'wood'),
		array('икс', 'iks'),
		array('некствуд', 'нэкствуд', 'nextwood'),
		array('форсис', '4sis'),
		array('коэкструзия', 'ко-экструзия', 'коекструзия'),
		array('нусадуа', 'нуссадуа', 'nusadua'),
		array('терапол', 'террапол'),
		array('терасная', 'террасная'),
		array('терраса', 'террас'),
	);

	/**
	 * Варианты написания слова, любой из которых считается совпадением: как
	 * набрано, в латинице, в кириллице, в другой раскладке клавиатуры и по
	 * таблице синонимов.
	 *
	 * Транслитерация нужна из-за марок: в каталоге производитель записан
	 * латиницей («LATITUDO»), а печатают его и по-русски — «лати». Обратная
	 * сторона тоже встречается: «terras» → «террас». Раскладка спасает, когда
	 * забыли переключить язык: «nthhfc» — это «террас».
	 */
	public static function variants($word)
	{
		$forms = array($word => true);

		foreach (array(
			self::toLatin($word),
			self::toCyrillic($word),
			self::swapLayout($word, true),
			self::swapLayout($word, false),
		) as $form) {
			if ($form !== '' && $form !== $word) {
				$forms[$form] = true;
			}
		}

		foreach (self::synonymsFor($word) as $form) {
			$forms[$form] = true;
		}

		$out = array();
		foreach (array_keys($forms) as $form) {
			$out[self::foldSizes($form)] = true;
		}

		return array_keys($out);
	}

	/**
	 * Формы из таблицы синонимов. Слово принимается и по началу: «из» ещё
	 * дотягивается до «изи» (человек только начал печатать), «изидекинг» — до
	 * «изи» (напечатал больше, чем в таблице).
	 */
	private static function synonymsFor($word)
	{
		if (mb_strlen($word, 'UTF-8') < 2) {
			return array();
		}

		$out = array();
		foreach (self::$synonymGroups as $group) {
			$hit = false;
			foreach ($group as $form) {
				if (strpos($word, $form) === 0 || strpos($form, $word) === 0) {
					$hit = true;
					break;
				}
			}
			if ($hit) {
				foreach ($group as $form) {
					$out[$form] = true;
				}
			}
		}

		return array_keys($out);
	}

	/**
	 * Слово, набранное не в той раскладке. $toCyrillic = true — считаем, что
	 * печатали русскими буквами по латинской раскладке («nthhfc» → «террас»),
	 * false — наоборот.
	 */
	private static function swapLayout($word, $toCyrillic)
	{
		static $en = array(
			'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н',
			'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ',
			'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р',
			'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', "'" => 'э',
			'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т',
			'm' => 'ь', ',' => 'б', '.' => 'ю', '`' => 'ё',
		);
		static $ru = null;
		if ($ru === null) {
			$ru = array_flip($en);
		}

		return strtr($word, $toCyrillic ? $en : $ru);
	}

	private static function toLatin($word)
	{
		static $map = array(
			'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
			'е' => 'e',  'ж' => 'zh', 'з' => 'z',  'и' => 'i',  'й' => 'y',
			'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',  'о' => 'o',
			'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',  'у' => 'u',
			'ф' => 'f',  'х' => 'h',  'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',
			'щ' => 'sch','ъ' => '',   'ы' => 'y',  'ь' => '',   'э' => 'e',
			'ю' => 'yu', 'я' => 'ya',
		);

		return strtr($word, $map);
	}

	private static function toCyrillic($word)
	{
		static $map = array(
			'a' => 'а', 'b' => 'б', 'c' => 'к', 'd' => 'д', 'e' => 'е',
			'f' => 'ф', 'g' => 'г', 'h' => 'х', 'i' => 'и', 'j' => 'дж',
			'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о',
			'p' => 'п', 'q' => 'к', 'r' => 'р', 's' => 'с', 't' => 'т',
			'u' => 'у', 'v' => 'в', 'w' => 'в', 'x' => 'кс', 'y' => 'у',
			'z' => 'з',
		);

		return strtr($word, $map);
	}

	/**
	 * Возвращает ID товаров, подходящих под запрос, в порядке убывания
	 * осмысленности. $limit = 0 — без ограничения (нужно странице выдачи).
	 */
	public static function findProducts($query, $limit = 0)
	{
		$words = self::splitQuery($query);
		if (!$words) {
			return array();
		}
		if (mb_strlen(implode('', $words), 'UTF-8') < self::MIN_QUERY_LEN) {
			return array();
		}

		$terms = array();
		foreach ($words as $word) {
			$terms[] = self::variants($word);
		}

		// Запрос целиком — чтобы поймать точное совпадение артикула.
		$whole = ' '.trim(self::normalize($query)).' ';

		$found = array();
		foreach (self::getIndex() as $id => $row) {
			foreach ($terms as $forms) {
				if (self::firstHit($row['h'], $forms) === false) {
					continue 2;
				}
			}

			/* Ранг: 0 — запрос совпал с артикулом товара или его предложения;
			   1 — все слова есть в самом названии и первое стоит в начале;
			   2 — все слова в названии; 3 — часть слов нашлась только в
			   артикуле, производителе или названии предложения. */
			$rank = 3;
			$pos  = PHP_INT_MAX;

			$inName = true;
			foreach ($terms as $forms) {
				if (self::firstHit($row['n'], $forms) === false) {
					$inName = false;
					break;
				}
			}
			if ($inName) {
				$pos  = self::firstHit($row['n'], $terms[0]);
				$rank = ($pos === 0) ? 1 : 2;
			}
			if (strpos($row['a'], $whole) !== false) {
				$rank = 0;
				$pos  = 0;
			}

			/* Вес марки. Точное попадание в артикул (ранг 0) марке не
			   уступает: по такому запросу человек ищет конкретную позицию, и
			   увести её вниз ради чужого бренда нельзя. Для остальных вес
			   марки — главный ключ. */
			$brand = ($rank === 0)
				? 0
				: (isset($row['b']) ? $row['b'] : self::BRAND_WEIGHT_OTHER);

			$found[] = array($rank, $pos, $row['n'], $id, $brand);
		}

		/* Порядок: сначала точный артикул, затем свои марки (EasyDecking,
		   LATITUDO, остальные), внутри марки — по релевантности, положению
		   первого слова в названии и алфавиту. */
		usort($found, function ($a, $b) {
			if ($a[4] !== $b[4]) {
				return $a[4] < $b[4] ? -1 : 1;
			}
			if ($a[0] !== $b[0]) {
				return $a[0] < $b[0] ? -1 : 1;
			}
			if ($a[1] !== $b[1]) {
				return $a[1] < $b[1] ? -1 : 1;
			}

			return strcmp($a[2], $b[2]);
		});

		if ($limit > 0 && count($found) > $limit) {
			$found = array_slice($found, 0, $limit);
		}

		$ids = array();
		foreach ($found as $row) {
			$ids[] = $row[3];
		}

		return $ids;
	}

	/**
	 * Обводит найденные куски названия в <b>, остальное экранирует.
	 * Работает по символам: нормализованная строка посимвольно соответствует
	 * исходной, поэтому позиции совпадений переносятся один в один.
	 */
	public static function highlight($name, array $words)
	{
		$chars     = preg_split('//u', (string)$name, -1, PREG_SPLIT_NO_EMPTY);
		$normChars = preg_split('//u', self::normalize($name), -1, PREG_SPLIT_NO_EMPTY);
		$len       = count($chars);

		// Подстраховка: если нормализация изменила длину, подсветку не рисуем.
		if (!$len || count($normChars) !== $len) {
			return htmlspecialcharsbx((string)$name);
		}

		$norm = implode('', $normChars);
		$mark = array_fill(0, $len, false);
		foreach ($words as $word) {
			foreach (self::variants($word) as $form) {
				$formLen = mb_strlen($form, 'UTF-8');
				if (!$formLen) {
					continue;
				}
				$offset = 0;
				while (($at = mb_strpos($norm, $form, $offset, 'UTF-8')) !== false) {
					for ($i = 0; $i < $formLen; $i++) {
						$mark[$at + $i] = true;
					}
					$offset = $at + 1;
				}
			}
		}

		$out   = '';
		$chunk = '';
		$state = false;
		for ($i = 0; $i < $len; $i++) {
			if ($mark[$i] !== $state) {
				$out  .= $state ? '<b>'.htmlspecialcharsbx($chunk).'</b>' : htmlspecialcharsbx($chunk);
				$chunk = '';
				$state = $mark[$i];
			}
			$chunk .= $chars[$i];
		}
		$out .= $state ? '<b>'.htmlspecialcharsbx($chunk).'</b>' : htmlspecialcharsbx($chunk);

		return $out;
	}

	/**
	 * Индекс: ID товара => ['n' => название, 'h' => сеновал, 'a' => артикулы].
	 * Все три строки нормализованы; 'h' и 'a' обрамлены пробелами, чтобы
	 * можно было искать целое слово как ' слово '.
	 */
	public static function getIndex()
	{
		self::load();

		return self::$index;
	}

	/**
	 * Точное попадание по артикулу: если запрос целиком совпал с артикулом
	 * товара или его торгового предложения, возвращает
	 * array('PRODUCT_ID' => …, 'OFFER_ID' => … | 0), иначе null.
	 *
	 * Нужен, чтобы по Enter на артикуле открывалась карточка (а для артикула
	 * предложения — сразу нужное предложение), а не список из одной строки.
	 * Неоднозначные артикулы (один и тот же у разных товаров) пропускаем —
	 * там честнее показать список.
	 */
	public static function findByArticle($query)
	{
		$article = trim(self::normalize($query));
		if ($article === '' || strpos($article, ' ') !== false) {
			return null;
		}

		self::load();

		return isset(self::$articles[$article]) ? self::$articles[$article] : null;
	}

	private static function load()
	{
		if (self::$index !== null) {
			return;
		}

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		/* v2 — в кеш добавлена карта артикулов; v3 — вес марки у каждой записи.
		   Версию в ключе поднимаем при смене формата: со старым ключом в кеше
		   лежит запись без нужных полей. */
		$key = 'v3|'.self::indexVersion();

		if ($cache->initCache(self::CACHE_TTL, $key, self::CACHE_PATH)) {
			$data = $cache->getVars();
		} else {
			$data = self::buildIndex();
			$cache->startDataCache();
			$cache->endDataCache($data);
		}

		self::$index    = isset($data['index']) ? $data['index'] : array();
		self::$articles = isset($data['articles']) ? $data['articles'] : array();
	}

	/**
	 * Отпечаток каталога для ключа кеша: любое добавление, удаление или правка
	 * элемента меняет либо количество, либо максимальный TIMESTAMP_X.
	 */
	private static function indexVersion()
	{
		global $DB;

		$row = $DB->Query(
			'SELECT COUNT(*) AS C, MAX(TIMESTAMP_X) AS T FROM b_iblock_element '
			.'WHERE IBLOCK_ID IN ('.self::IBLOCK_PRODUCTS.', '.self::IBLOCK_OFFERS.')'
		)->Fetch();

		return $row ? $row['C'].'@'.$row['T'] : '0@';
	}

	private static function buildIndex()
	{
		global $DB;

		$raw = array();
		// артикул → куда вести; '' в значении означает «встретился дважды»
		$articles = array();

		$rs = $DB->Query(
			'SELECT e.ID, e.NAME, art.VALUE AS ARTICLE, brand.NAME AS BRAND, br.VALUE_NUM AS BRAND_ID '
			.'FROM b_iblock_element e '
			.'LEFT JOIN b_iblock_element_property art '
				.'ON art.IBLOCK_ELEMENT_ID = e.ID AND art.IBLOCK_PROPERTY_ID = '.self::PROP_ARTICLE.' '
			.'LEFT JOIN b_iblock_element_property br '
				.'ON br.IBLOCK_ELEMENT_ID = e.ID AND br.IBLOCK_PROPERTY_ID = '.self::PROP_BRAND.' '
			.'LEFT JOIN b_iblock_element brand ON brand.ID = br.VALUE_NUM '
			.'WHERE e.IBLOCK_ID = '.self::IBLOCK_PRODUCTS.' '
				.'AND e.ACTIVE = \'Y\' AND e.WF_STATUS_ID = 1 AND e.WF_PARENT_ELEMENT_ID IS NULL'
		);
		while ($row = $rs->Fetch()) {
			$id   = (int)$row['ID'];
			$name = self::plainText($row['NAME']);
			$brandId = (int)$row['BRAND_ID'];
			$raw[$id] = array(
				'name' => $name,
				'hay'  => $name.' '.$row['ARTICLE'].' '.self::plainText($row['BRAND']),
				'art'  => (string)$row['ARTICLE'],
				'bw'   => isset(self::$brandWeight[$brandId])
					? self::$brandWeight[$brandId]
					: self::BRAND_WEIGHT_OTHER,
			);
			self::addArticle($articles, $row['ARTICLE'], $id, 0);
		}

		// Предложения приписываем родительскому товару: по решению от
		// 17 августа 2026 в выдаче они сводятся к нему.
		$rs = $DB->Query(
			'SELECT o.ID AS OFFER_ID, lnk.VALUE_NUM AS PRODUCT_ID, o.NAME, oart.VALUE AS ARTICLE '
			.'FROM b_iblock_element o '
			.'INNER JOIN b_iblock_element_property lnk '
				.'ON lnk.IBLOCK_ELEMENT_ID = o.ID AND lnk.IBLOCK_PROPERTY_ID = '.self::PROP_OFFER_LINK.' '
			.'LEFT JOIN b_iblock_element_property oart '
				.'ON oart.IBLOCK_ELEMENT_ID = o.ID AND oart.IBLOCK_PROPERTY_ID = '.self::PROP_OFFER_ARTICLE.' '
			.'WHERE o.IBLOCK_ID = '.self::IBLOCK_OFFERS.' '
				.'AND o.ACTIVE = \'Y\' AND o.WF_STATUS_ID = 1 AND o.WF_PARENT_ELEMENT_ID IS NULL'
		);
		while ($row = $rs->Fetch()) {
			$id = (int)$row['PRODUCT_ID'];
			if (!isset($raw[$id])) {
				continue;
			}
			$raw[$id]['hay'] .= ' '.self::plainText($row['NAME']).' '.$row['ARTICLE'];
			if ((string)$row['ARTICLE'] !== '') {
				$raw[$id]['art'] .= ' '.$row['ARTICLE'];
			}
			self::addArticle($articles, $row['ARTICLE'], $id, (int)$row['OFFER_ID']);
		}

		$index = array();
		foreach ($raw as $id => $item) {
			$index[$id] = array(
				'n' => self::normalize($item['name']),
				// Повторы слов схлопываем: у товара с десятком предложений
				// сеновал иначе раздувается в разы без пользы для поиска.
				'h' => self::wordSet($item['hay']),
				'a' => self::wordSet($item['art']),
				'b' => $item['bw'],
			);
		}

		// Неоднозначные артикулы выбрасываем — по ним ведём в список, а не в карточку.
		foreach ($articles as $article => $target) {
			if ($target === '' || !isset($index[$target['PRODUCT_ID']])) {
				unset($articles[$article]);
			}
		}

		return array('index' => $index, 'articles' => $articles);
	}

	/** Копит карту «артикул → карточка», помечая пустой строкой повторы. */
	private static function addArticle(&$articles, $article, $productId, $offerId)
	{
		$article = trim(self::normalize($article));
		if ($article === '') {
			return;
		}
		if (isset($articles[$article])) {
			$articles[$article] = '';

			return;
		}
		$articles[$article] = array('PRODUCT_ID' => $productId, 'OFFER_ID' => $offerId);
	}

	/**
	 * Названия из 1С местами приезжают уже с html-мнемониками («&quot;Эквадор&quot;
	 * журнальный стол»). Раскрываем их: иначе в подсказках, где мы экранируем
	 * сами, выходило «&amp;quot;Эквадор&amp;quot;».
	 */
	public static function plainText($text)
	{
		$text = (string)$text;

		return (strpos($text, '&') === false)
			? $text
			: html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Позиция первого из вариантов написания в строке или false, если не нашёлся
	 * ни один. Строки уже нормализованы, поэтому обычный strpos по байтам.
	 */
	private static function firstHit($haystack, array $forms)
	{
		$best = false;
		foreach ($forms as $form) {
			$at = strpos($haystack, $form);
			if ($at !== false && ($best === false || $at < $best)) {
				$best = $at;
			}
		}

		return $best;
	}

	/** Уникальные нормализованные слова строки, обрамлённые пробелами. */
	private static function wordSet($text)
	{
		$parts = preg_split('/[^\p{L}\p{N}*.\/-]+/u', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY);
		if (!$parts) {
			return ' ';
		}

		return ' '.implode(' ', array_keys(array_flip($parts))).' ';
	}
}

}
