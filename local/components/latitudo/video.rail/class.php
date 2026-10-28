<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

/**
 * Лента «Видео о продукции» (ТЗ Ирины 28.10.2026, макет 1a): ролики из
 * инфоблока «Видео», фильтр по категориям, окно с плеером и товаром.
 *
 * Выборка — LatitudoVideoRepository::getList() (local/php_interface/include/
 * latitudo_videos.php). Первый потребитель — страница производителя
 * (BRAND_ID), будущая страница «Видео» позовёт тот же компонент без марки.
 *
 * Кеш с тегами: ролики (videos), товары и предложения каталога — правка
 * любого из них в админке сбрасывает ленту. Цены из 1С идут мимо тегов —
 * их освежает срок кеша.
 */
class LatitudoVideoRail extends CBitrixComponent
{
	/** Порядок фильтра: сначала основные категории, остальные — по числу роликов. */
	private const CATEGORY_ORDER = ['Террасная доска', 'Фасады', 'Заборы', 'Ограждения', 'Ступени', 'Садовый паркет'];

	public function onPrepareComponentParams($arParams)
	{
		$arParams['BRAND_ID'] = (int)($arParams['BRAND_ID'] ?? 0);
		$arParams['CATEGORY'] = trim((string)($arParams['CATEGORY'] ?? ''));
		$arParams['TITLE'] = trim((string)($arParams['TITLE'] ?? '')) ?: 'Видео о продукции';
		$arParams['SUBTITLE'] = trim((string)($arParams['SUBTITLE'] ?? 'Снимаем каждый товар сами — на нашем складе и объектах'));
		// GRID — страница «Видео» (/info/video/): все ролики сеткой, фильтр ещё и по маркам
		$arParams['VIEW'] = ($arParams['VIEW'] ?? '') === 'GRID' ? 'GRID' : 'RAIL';
		$arParams['CACHE_TYPE'] = $arParams['CACHE_TYPE'] ?? 'A';
		$arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? (int)$arParams['CACHE_TIME'] : 3600;
		return $arParams;
	}

	public function executeComponent()
	{
		if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog') || !class_exists('LatitudoVideoRepository')) {
			return;
		}
		global $USER;
		if ($this->startResultCache(false, [$USER->GetGroups()])) {
			$items = LatitudoVideoRepository::getList(array_filter([
				'BRAND' => $this->arParams['BRAND_ID'],
				'CATEGORY' => $this->arParams['CATEGORY'],
			]));
			// пустой результат тоже в кеш: у большинства марок роликов нет, шаблон тогда молчит
			$this->arResult['ITEMS'] = $this->prepareItems($items);
			$this->arResult['CATEGORIES'] = $this->categories($items);
			$this->registerTags();
			$this->includeComponentTemplate();
		}
	}

	private function prepareItems(array $items): array
	{
		foreach ($items as &$v) {
			$v['PICTURE'] = null;
			if ($v['PICTURE_ID'] > 0) {
				// карточка 360px — превью вдвое шире для чётких экранов
				$pic = CFile::ResizeImageGet($v['PICTURE_ID'], ['width' => 720, 'height' => 405], BX_RESIZE_IMAGE_EXACT, true);
				$v['PICTURE'] = $pic ? ['SRC' => $pic['src'], 'WIDTH' => (int)$pic['width'], 'HEIGHT' => (int)$pic['height']] : null;
			}
		}
		unset($v);
		return $items;
	}

	/** @return array<string,int> подпись категории => число роликов */
	private function categories(array $items): array
	{
		$count = [];
		foreach ($items as $v) {
			if ($v['CATEGORY'] !== '') {
				$count[$v['CATEGORY']] = ($count[$v['CATEGORY']] ?? 0) + 1;
			}
		}
		uksort($count, function ($a, $b) use ($count) {
			$ia = array_search($a, self::CATEGORY_ORDER, true);
			$ib = array_search($b, self::CATEGORY_ORDER, true);
			if ($ia !== false || $ib !== false) {
				return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
			}
			return $count[$b] <=> $count[$a] ?: strcmp($a, $b);
		});
		return $count;
	}

	/** @return array<int,array{NAME:string,COUNT:int}> марки по числу роликов */
	private function brands(array $items): array
	{
		$count = [];
		foreach ($items as $v) {
			if ($v['BRAND']) {
				$count[$v['BRAND']] = ($count[$v['BRAND']] ?? 0) + 1;
			}
		}
		if (!$count) {
			return [];
		}
		$out = [];
		$rs = CIBlockElement::GetList([], ['ID' => array_keys($count), 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
		while ($b = $rs->Fetch()) {
			$out[(int)$b['ID']] = ['NAME' => $b['NAME'], 'COUNT' => $count[(int)$b['ID']]];
		}
		uasort($out, fn($a, $b) => $b['COUNT'] <=> $a['COUNT'] ?: strcmp($a['NAME'], $b['NAME']));
		return $out;
	}

	private function registerTags(): void
	{
		if (!defined('BX_COMP_MANAGED_CACHE')) {
			return;
		}
		$cache = Application::getInstance()->getTaggedCache();
		foreach ([LatitudoVideoSync::iblockId(), LatitudoVideoSync::CATALOG_IBLOCK, LatitudoVideoSync::OFFERS_IBLOCK] as $iblockId) {
			$cache->registerTag('iblock_id_' . $iblockId);
		}
	}
}
