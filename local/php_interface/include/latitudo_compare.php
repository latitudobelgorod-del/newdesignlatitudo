<?php
/**
 * Сравнение товаров (Ирина, ТЗ 15.10.2026).
 *
 * Пока идёт скрытый тест, сравнение видят только авторизованные пользователи
 * группы «Тестирование сравнения» (STRING_ID ND_COMPARE_TEST). Для остальных
 * сайт не меняется: ни кнопок, ни ссылок в HTML.
 *
 * Как кнопка не попадает в кеш. Шаблоны карточки и плитки списка кешируются
 * (с 28.09.2026), поэтому в них стоит только HTML-комментарий <!--ND_CMP:ID-->.
 * ndCompareTokens (OnEndBufferContent) на отдаче заменяет его кнопкой — только
 * для группы; у остальных комментарий вырезается. Если обработчик почему-то не
 * сработает (ответ ajax мимо буфера), комментарий так и останется комментарием —
 * ни ссылки, ни текста. Атрибуты кнопки — в одинарных кавычках: метка бывает
 * внутри JSON-строки ответа ajax, двойная кавычка порвала бы JSON.
 *
 * Список — ID товаров (не торговых предложений: у доски предложения по длине,
 * и одна доска встала бы в таблицу дважды) в порядке добавления. Хранится в
 * сессии и в cookie ND_CMP на 30 дней на весь домен (иначе на поддомене города
 * список был бы свой).
 *
 * Страница — /compare/ (обёртка /compare/index.php → local/compare/page.php),
 * таблица — компонент latitudo:catalog.compare, кнопки и счётчик —
 * js/newdesign-compare.js шаблона, запросы — /local/ajax/compare.php.
 *
 * Открытие для всех — ndCompareAllowed() возвращает true без проверки группы
 * (см. ND_COMPARE_PUBLIC), плюс robots страницы и цели Метрики.
 */

/** Сравнение открыто для всех (после «открываем сравнение»). Пока — только группа. */
const ND_COMPARE_PUBLIC = false;
const ND_COMPARE_GROUP = 'ND_COMPARE_TEST';
const ND_COMPARE_COOKIE = 'ND_CMP';
const ND_COMPARE_MAX = 40;
const ND_COMPARE_IBLOCK = 19;

/** Видит ли текущий посетитель сравнение. */
function ndCompareAllowed(): bool
{
	static $allowed = null;
	if ($allowed !== null) {
		return $allowed;
	}
	if (ND_COMPARE_PUBLIC) {
		return $allowed = true;
	}
	global $USER;
	if (!is_object($USER) || !$USER->IsAuthorized()) {
		return $allowed = false;
	}
	$groupId = ndCompareGroupId();
	return $allowed = ($groupId > 0 && in_array($groupId, array_map('intval', $USER->GetUserGroupArray()), true));
}

/** ID группы «Тестирование сравнения» (0 — группы нет). */
function ndCompareGroupId(): int
{
	$row = \Bitrix\Main\GroupTable::getList([
		'filter' => ['=STRING_ID' => ND_COMPARE_GROUP],
		'select' => ['ID'],
		'cache' => ['ttl' => 3600],
	])->fetch();
	return $row ? (int)$row['ID'] : 0;
}

/** Домен cookie: весь latitudo.ru, чтобы список был один на всех поддоменах городов. */
function ndCompareCookieDomain(): string
{
	$host = strtolower(preg_replace('~:\d+$~', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
	$parts = explode('.', $host);
	if (count($parts) >= 2 && !filter_var($host, FILTER_VALIDATE_IP)) {
		return '.' . implode('.', array_slice($parts, -2));
	}
	return '';
}

/** Список ID в порядке добавления. */
function ndCompareList(): array
{
	if (isset($_SESSION['ND_COMPARE']) && is_array($_SESSION['ND_COMPARE'])) {
		$ids = $_SESSION['ND_COMPARE'];
	} else {
		$ids = explode('.', (string)($_COOKIE[ND_COMPARE_COOKIE] ?? ''));
	}
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	return array_slice($ids, -ND_COMPARE_MAX);
}

function ndCompareSave(array $ids): void
{
	$ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), -ND_COMPARE_MAX);
	$_SESSION['ND_COMPARE'] = $ids;
	setcookie(ND_COMPARE_COOKIE, implode('.', $ids), [
		'expires' => $ids ? time() + 30 * 86400 : time() - 3600,
		'path' => '/',
		'domain' => ndCompareCookieDomain(),
		'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
		'httponly' => false,
		'samesite' => 'Lax',
	]);
}

/**
 * Вкладка сравнения для каждого товара: сравниваем только товары одного раздела.
 * Возвращает [ID => ['KEY', 'TITLE', 'TYPE', 'URL']]. TYPE выбирает набор строк
 * таблицы (ndCompareRows): board, steps, fence, supports, furniture, default.
 */
function ndCompareGroups(array $ids): array
{
	$ids = array_values(array_filter(array_map('intval', $ids)));
	if (!$ids || !\Bitrix\Main\Loader::includeModule('iblock')) {
		return [];
	}
	static $roots = [
		98  => ['board', 'Террасная доска'],
		114 => ['steps', 'Ступени'],
		102 => ['fence', 'Заборная доска'],
		278 => ['supports', 'Регулируемые опоры'],
		503 => ['furniture', ''],
	];

	$items = [];
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'ID' => $ids], false, false,
		['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'PROPERTY_FORMAT']);
	while ($row = $rs->Fetch()) {
		$id = (int)$row['ID'];
		if (!isset($items[$id])) {
			$items[$id] = ['SECTION' => (int)$row['IBLOCK_SECTION_ID'], 'FORMAT' => []];
		}
		if ($row['PROPERTY_FORMAT_VALUE'] !== null && $row['PROPERTY_FORMAT_VALUE'] !== '') {
			$items[$id]['FORMAT'][] = (string)$row['PROPERTY_FORMAT_VALUE'];
		}
	}

	static $chains = [];
	$out = [];
	foreach ($items as $id => $item) {
		$sid = $item['SECTION'];
		if (!isset($chains[$sid])) {
			$chains[$sid] = [];
			if ($sid) {
				foreach (CIBlockSection::GetNavChain(ND_COMPARE_IBLOCK, $sid, ['ID', 'NAME'], true) as $s) {
					// GetNavChain не разбирает шаблон адреса — берём готовый у раздела
					$s['SECTION_PAGE_URL'] = ndCompareSectionUrl((int)$s['ID']);
					$chains[$sid][] = $s;
				}
			}
		}
		$chain = $chains[$sid];
		$group = null;
		foreach ($chain as $level => $s) {
			$rootId = (int)$s['ID'];
			if (!isset($roots[$rootId])) {
				continue;
			}
			[$type, $title] = $roots[$rootId];
			if ($type === 'supports' && !in_array('Опора', $item['FORMAT'], true)) {
				$group = ['KEY' => 'supports_acc', 'TITLE' => 'Аксессуары для опор', 'TYPE' => 'default', 'URL' => $s['SECTION_PAGE_URL']];
			} elseif ($type === 'furniture') {
				// мебель — по подразделам: кресло со столом сравнивать незачем
				$sub = $chain[$level + 1] ?? $s;
				$group = ['KEY' => 'f' . $sub['ID'], 'TITLE' => (string)$sub['NAME'], 'TYPE' => 'furniture', 'URL' => $sub['SECTION_PAGE_URL']];
			} else {
				$group = ['KEY' => $type, 'TITLE' => $title, 'TYPE' => $type, 'URL' => $s['SECTION_PAGE_URL']];
			}
			break;
		}
		if (!$group) {
			$top = $chain[0] ?? ['ID' => 0, 'NAME' => 'Другие товары', 'SECTION_PAGE_URL' => '/catalog/'];
			$group = ['KEY' => 's' . $top['ID'], 'TITLE' => (string)$top['NAME'], 'TYPE' => 'default', 'URL' => $top['SECTION_PAGE_URL']];
		}
		$out[$id] = $group;
	}
	return $out;
}

function ndCompareSectionUrl(int $sectionId): string
{
	static $urls = [];
	if (!isset($urls[$sectionId])) {
		$s = CIBlockSection::GetList([], ['IBLOCK_ID' => ND_COMPARE_IBLOCK, 'ID' => $sectionId], false, ['ID', 'SECTION_PAGE_URL'])->GetNext();
		$urls[$sectionId] = $s ? (string)$s['SECTION_PAGE_URL'] : '/catalog/';
	}
	return $urls[$sectionId];
}

/**
 * Кнопка «Сравнить» вместо метки (атрибуты — в одинарных кавычках, см. шапку).
 * В плитке — галочка с подписью, в карточке — квадрат с иконкой, как в шапке
 * (Ирина, 15.10.2026): js/newdesign-compare.js ставит его в строку H1 рядом с номером.
 */
function ndCompareButtonHtml(int $id, string $place): string
{
	if ($place === 'detail') {
		return "<button type='button' class='nd-cmp-btn nd-cmp-btn--detail' data-nd-cmp='" . $id . "'"
			. " title='Добавить к сравнению' aria-label='Добавить к сравнению'>"
			. "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' aria-hidden='true'><path d='M5 20V10M12 20V4M19 20v-7' stroke='currentColor' stroke-width='2' stroke-linecap='round'/></svg>"
			. "<span class='nd-cmp-btn__text'>Сравнить</span></button>";
	}
	return "<button type='button' class='nd-cmp-btn nd-cmp-btn--" . $place . "' data-nd-cmp='" . $id . "'"
		. " title='Добавить к сравнению'><span class='nd-cmp-btn__box'></span>"
		. "<span class='nd-cmp-btn__text'>Сравнить</span></button>";
}

/**
 * OnEndBufferContent: метки <!--ND_CMP:ID--> (плитка) и <!--ND_CMP_D:ID-->
 * (карточка) → кнопка для группы, иначе — вырезать.
 */
function ndCompareTokens(&$content)
{
	if (!is_string($content) || strpos($content, '<!--ND_CMP') === false) {
		return;
	}
	$allowed = ndCompareAllowed();
	$content = preg_replace_callback('~<!--ND_CMP(_D)?:(\d+)-->~', static function ($m) use ($allowed) {
		return $allowed ? ndCompareButtonHtml((int)$m[2], $m[1] ? 'detail' : 'list') : '';
	}, $content);
}
