<?php
/**
 * Синхронизация отзывов о работодателе с hh.ru / Dream Job в инфоблок сайта.
 *
 * Отзывы о Латитудо на hh.ru отдаёт не сам hh, а его партнёр Dream Job:
 * на странице работодателя стоит микрофронтенд employer_reviews, а полный
 * список лежит на dreamjob.ru. Поэтому источников два:
 *   - сводка (рейтинг, доля рекомендаций, число отзывов) — виджет hh
 *     /employer_reviews/proxy_components/big_widget?employerId=<id>,
 *     состояние лежит в <template class="EmployerReviewsFront-InitialState">;
 *   - сами отзывы — страница dreamjob.ru/employers/<djId>, разметка
 *     .review.review-fl (36 штук на момент написания, пагинации нет).
 *
 * Что делает скрипт:
 *   1) создаёт инфоблок «Отзывы на HH» (тип aspro_next_content, код
 *      nd_hh_reviews), если его ещё нет, вместе со свойствами;
 *   2) скачивает и разбирает источники, складывая результат в data.json
 *      рядом со скриптом;
 *   3) заводит/обновляет элементы по XML_ID (идентификатор отзыва на Dream Job)
 *      и деактивирует те, которых в выдаче больше нет.
 *
 * Запуск: /local/tools/hh_reviews_sync.php?key=<ключ>[&mode=...]
 *   mode=all     (по умолчанию) — скачать и импортировать
 *   mode=fetch   — только скачать в data.json
 *   mode=import  — только импортировать из data.json
 * Второй режим нужен, если с прода нет исхода в интернет: data.json лежит
 * в репозитории, приезжает вместе с кодом, и на проде достаточно mode=import.
 *
 * Ключ (ND_HH_SYNC_KEY) — чтобы страницу нельзя было дёргать посторонним:
 * скрипт меняет данные и ходит наружу.
 */

define('ND_HH_SYNC_KEY', 'latitudo-hh-2026');

define('ND_HH_EMPLOYER_ID', 2821821);   // id работодателя на hh.ru
define('ND_HH_DJ_ID', 142262);          // он же на dreamjob.ru
define('ND_HH_IBLOCK_CODE', 'nd_hh_reviews');
define('ND_HH_IBLOCK_TYPE', 'aspro_next_content');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (($_GET['key'] ?? '') !== ND_HH_SYNC_KEY) {
	die('forbidden');
}

header('Content-Type: text/plain; charset=UTF-8');

if (!CModule::IncludeModule('iblock')) {
	die('нет модуля iblock');
}

$mode = $_GET['mode'] ?? 'all';
$dataFile = __DIR__.'/hh_reviews.json';

/* ------------------------------------------------------------------ утилиты */

function nd_hh_get($url, $referer = '')
{
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_TIMEOUT => 60,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
		CURLOPT_HTTPHEADER => array_filter(array(
			'Accept-Language: ru-RU,ru;q=0.9',
			$referer ? 'Referer: '.$referer : null,
		)),
		// у части хостингов не проходит проверка отзыва сертификата
		CURLOPT_SSL_VERIFYPEER => true,
	));
	$body = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	curl_close($ch);

	return array('code' => $code, 'body' => $body, 'error' => $err);
}

/** Внутренний текст первого совпадения, очищенный от тегов и пробелов. */
function nd_hh_text($html)
{
	$s = preg_replace('~<br\s*/?>~iu', "\n", (string)$html);
	$s = strip_tags($s);
	$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$s = str_replace("\xC2\xA0", ' ', $s);          // неразрывный пробел
	$s = preg_replace('~[ \t]+~u', ' ', $s);
	$s = preg_replace('~\s*\n\s*~u', "\n", $s);

	return trim($s);
}

/* ------------------------------------------------- 1. сводка из виджета hh */

function nd_hh_fetch_summary()
{
	$url = 'https://hh.ru/employer_reviews/proxy_components/big_widget?employerId='.ND_HH_EMPLOYER_ID
		.'&isNoticeableComplainButton=True&isOnPreviewPage=False';
	$res = nd_hh_get($url, 'https://hh.ru/employer/'.ND_HH_EMPLOYER_ID);
	if ($res['code'] !== 200 || !$res['body']) {
		return array('error' => 'виджет hh: HTTP '.$res['code'].' '.$res['error']);
	}

	if (!preg_match('~<template[^>]*EmployerReviewsFront-InitialState[^>]*>(.*?)</template>~su', $res['body'], $m)) {
		return array('error' => 'в ответе hh нет блока состояния');
	}

	$json = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$data = json_decode($json, true);
	if (!is_array($data) || empty($data['employerReviews'])) {
		return array('error' => 'состояние hh не разобралось');
	}

	$er = $data['employerReviews'];

	return array(
		'rating' => (string)($er['totalRating'] ?? ''),
		'reviewsCount' => (int)($er['reviewsCount'] ?? 0),
		'recommendPercent' => (int)($er['recommendationsPercent'] ?? 0),
		'djId' => (string)($er['employerDjId'] ?? ''),
	);
}

/* --------------------------------------------- 2. отзывы со страницы Dream Job */

function nd_hh_fetch_reviews()
{
	$url = 'https://dreamjob.ru/employers/'.ND_HH_DJ_ID;
	$res = nd_hh_get($url);
	if ($res['code'] !== 200 || !$res['body']) {
		return array('error' => 'dreamjob: HTTP '.$res['code'].' '.$res['error']);
	}

	$html = $res['body'];

	// Каждый отзыв — <div class="review review-fl" id="review<ID>" ...>
	$parts = preg_split('~<div class="review review-fl"~u', $html);
	array_shift($parts); // всё до первого отзыва

	$out = array();
	foreach ($parts as $chunk) {
		// обрезаем по началу следующего блока-соседа, чтобы не тащить хвост страницы
		$chunk = preg_split('~<a class="vacanciesInjectWidget"~u', $chunk);
		$chunk = $chunk[0];

		$id = '';
		if (preg_match('~id="review(\d+)"~u', $chunk, $m)) {
			$id = $m[1];
		}
		if (!$id) {
			continue;
		}

		$post = '';
		if (preg_match('~<h2 class="review__header-title"[^>]*>(.*?)</h2>~su', $chunk, $m)) {
			$post = nd_hh_text($m[1]);
		}

		$place = '';
		if (preg_match('~<span class="review__location">(.*?)</span>~su', $chunk, $m)) {
			$place = nd_hh_text($m[1]);
		}

		$duration = '';
		if (preg_match('~<div class="tags__item tags__item_grey">(.*?)</div>~su', $chunk, $m)) {
			$duration = nd_hh_text($m[1]);
		}

		$rating = '';
		if (preg_match('~<div class="d-none d-md-block dj-rating[^"]*"[^>]*>\s*([0-9,\.]+)\s*</div>~su', $chunk, $m)) {
			$rating = trim($m[1]);
		}

		/* Тексты идут парами «заголовок → .review__text»: «Что нравится?» и
		   «Что можно улучшить?». Берём по порядку, а не по заголовку — он
		   у части отзывов отличается. */
		$texts = array();
		if (preg_match_all('~<div class="review__text">(.*?)</div>~su', $chunk, $mm)) {
			foreach ($mm[1] as $t) {
				$texts[] = nd_hh_text($t);
			}
		}

		$answer = '';
		if (preg_match('~<div class="review__answer-text">(.*?)</div>~su', $chunk, $m)) {
			$answer = nd_hh_text($m[1]);
		}

		/* «Москва, Март 2026» → город и дата отдельно. У части отзывов города
		   нет и в строке стоит только месяц с годом — тогда это дата. */
		$city = '';
		$date = '';
		if (strpos($place, ',') !== false) {
			list($city, $date) = array_map('trim', explode(',', $place, 2));
		} elseif (preg_match('~\d{4}\s*$~u', $place)) {
			$date = $place;
		} else {
			$city = $place;
		}

		$out[] = array(
			'id' => $id,
			'post' => $post,
			'city' => $city,
			'date' => $date,
			'duration' => $duration,
			'rating' => $rating,
			'pros' => $texts[0] ?? '',
			'cons' => $texts[1] ?? '',
			'answer' => $answer,
			'url' => 'https://dreamjob.ru/employers/'.ND_HH_DJ_ID.'?review_id='.$id,
		);
	}

	return array('items' => $out);
}

/* ------------------------------------------------------- 3. инфоблок и свойства */

/** Свойства инфоблока: код => описание. */
function nd_hh_props()
{
	return array(
		'RATING' => array('NAME' => 'Оценка', 'TYPE' => 'S'),
		'REVIEW_DATE' => array('NAME' => 'Дата отзыва', 'TYPE' => 'S'),
		'CITY' => array('NAME' => 'Город', 'TYPE' => 'S'),
		'DURATION' => array('NAME' => 'Стаж в компании', 'TYPE' => 'S'),
		'CONS' => array('NAME' => 'Что можно улучшить', 'TYPE' => 'S', 'ROW_COUNT' => 5),
		'ANSWER' => array('NAME' => 'Ответ компании', 'TYPE' => 'S', 'ROW_COUNT' => 5),
		'REVIEW_URL' => array('NAME' => 'Ссылка на отзыв', 'TYPE' => 'S'),
	);
}

function nd_hh_iblock_id(&$log)
{
	$res = CIBlock::GetList(array(), array('TYPE' => ND_HH_IBLOCK_TYPE, 'CODE' => ND_HH_IBLOCK_CODE, 'CHECK_PERMISSIONS' => 'N'));
	if ($row = $res->Fetch()) {
		$log[] = 'инфоблок найден: '.$row['ID'];
		$id = (int)$row['ID'];
	} else {
		$ib = new CIBlock();
		$id = (int)$ib->Add(array(
			'ACTIVE' => 'Y',
			'NAME' => 'Отзывы на HH',
			'CODE' => ND_HH_IBLOCK_CODE,
			'IBLOCK_TYPE_ID' => ND_HH_IBLOCK_TYPE,
			'SITE_ID' => array(SITE_ID),
			'SORT' => 500,
			'GROUP_ID' => array('2' => 'R'),
			'VERSION' => 2,
			'INDEX_ELEMENT' => 'N',
			'INDEX_SECTION' => 'N',
			'DESCRIPTION_TYPE' => 'text',
			'DESCRIPTION' => '',
		));
		if (!$id) {
			$log[] = 'не удалось создать инфоблок: '.$ib->LAST_ERROR;

			return 0;
		}
		$log[] = 'инфоблок создан: '.$id;
	}

	// свойства
	$have = array();
	$rs = CIBlockProperty::GetList(array(), array('IBLOCK_ID' => $id));
	while ($p = $rs->Fetch()) {
		$have[$p['CODE']] = true;
	}
	$sort = 100;
	foreach (nd_hh_props() as $code => $prop) {
		$sort += 100;
		if (isset($have[$code])) {
			continue;
		}
		$obj = new CIBlockProperty();
		$ok = $obj->Add(array(
			'IBLOCK_ID' => $id,
			'NAME' => $prop['NAME'],
			'CODE' => $code,
			'PROPERTY_TYPE' => $prop['TYPE'],
			'SORT' => $sort,
			'MULTIPLE' => 'N',
			'ROW_COUNT' => $prop['ROW_COUNT'] ?? 1,
			'COL_COUNT' => 30,
		));
		$log[] = $ok ? 'свойство добавлено: '.$code : 'свойство НЕ добавлено: '.$code.' '.$obj->LAST_ERROR;
	}

	return $id;
}

/* ------------------------------------------------------------------- поехали */

$log = array();

if ($mode === 'all' || $mode === 'fetch') {
	$summary = nd_hh_fetch_summary();
	$reviews = nd_hh_fetch_reviews();

	if (!empty($summary['error'])) {
		$log[] = 'СВОДКА: '.$summary['error'];
	}
	if (!empty($reviews['error'])) {
		$log[] = 'ОТЗЫВЫ: '.$reviews['error'];
	}

	if (empty($reviews['items'])) {
		$log[] = 'отзывы не скачались — data.json не трогаем';
	} else {
		$payload = array(
			'fetched' => date('c'),
			'summary' => empty($summary['error']) ? $summary : null,
			'reviews' => $reviews['items'],
		);
		file_put_contents($dataFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		$log[] = 'скачано отзывов: '.count($reviews['items']).', записано в '.basename($dataFile);
	}
}

if ($mode === 'all' || $mode === 'import') {
	if (!file_exists($dataFile)) {
		$log[] = 'нет файла '.basename($dataFile).' — импортировать нечего';
	} else {
		$payload = json_decode(file_get_contents($dataFile), true);
		$items = $payload['reviews'] ?? array();
		$iblockId = nd_hh_iblock_id($log);

		if ($iblockId && $items) {
			// сводку кладём в описание инфоблока — страница читает её оттуда
			if (!empty($payload['summary'])) {
				$ib = new CIBlock();
				$ib->Update($iblockId, array(
					'DESCRIPTION_TYPE' => 'text',
					'DESCRIPTION' => json_encode($payload['summary'], JSON_UNESCAPED_UNICODE),
				));
				$log[] = 'сводка записана в описание инфоблока';
			}

			// что уже есть
			$exists = array();
			$rs = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'XML_ID'));
			while ($row = $rs->Fetch()) {
				$exists[$row['XML_ID']] = (int)$row['ID'];
			}

			$el = new CIBlockElement();
			$seen = array();
			$added = $updated = 0;
			$sort = 0;
			foreach ($items as $item) {
				$sort += 10;
				$seen[$item['id']] = true;
				$fields = array(
					'IBLOCK_ID' => $iblockId,
					'ACTIVE' => 'Y',
					'NAME' => $item['post'] ?: 'Отзыв сотрудника',
					'XML_ID' => $item['id'],
					'SORT' => $sort,
					'PREVIEW_TEXT_TYPE' => 'text',
					'PREVIEW_TEXT' => $item['pros'],
					'PROPERTY_VALUES' => array(
						'RATING' => $item['rating'],
						'REVIEW_DATE' => $item['date'],
						'CITY' => $item['city'],
						'DURATION' => $item['duration'],
						'CONS' => $item['cons'],
						'ANSWER' => $item['answer'],
						'REVIEW_URL' => $item['url'],
					),
				);
				if (isset($exists[$item['id']])) {
					$el->Update($exists[$item['id']], $fields);
					CIBlockElement::SetPropertyValuesEx($exists[$item['id']], $iblockId, $fields['PROPERTY_VALUES']);
					++$updated;
				} else {
					$newId = $el->Add($fields);
					if ($newId) {
						++$added;
					} else {
						$log[] = 'не добавился отзыв '.$item['id'].': '.$el->LAST_ERROR;
					}
				}
			}

			// пропавшие из выдачи гасим, а не удаляем — вдруг это сбой источника
			$off = 0;
			foreach ($exists as $xmlId => $elId) {
				if (!isset($seen[$xmlId])) {
					$el->Update($elId, array('ACTIVE' => 'N'));
					++$off;
				}
			}

			$log[] = "импорт: добавлено $added, обновлено $updated, деактивировано $off";
		}
	}
}

echo implode("\n", $log)."\n";

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
