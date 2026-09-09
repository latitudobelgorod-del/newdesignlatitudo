<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * Ссылки на посадочные страницы каталога из поля раздела UF_MENULINK_TOP.
 *
 * В поле лежит множественное значение — готовый HTML вида
 * `<a href="/catalog/stupeni-iz-dpk/polnotelyye-stupeni-iz-dpk/">Полнотелые ступени</a>`.
 * Разделами такие страницы не являются (это посадочные из инфоблока 21), но в
 * выпадающем каталоге шапки они показаны наравне с подразделами — см.
 * components/bitrix/menu/catalog_wide_newdesign/template.php. Здесь та же
 * разборка вынесена отдельно, чтобы ряд плиток на странице раздела показывал
 * их так же (Ирина, 9 сентября 2026): у «Ступеней из ДПК» и «Заборной доски
 * из ДПК» своих подразделов нет, и ряд оставался пустым.
 *
 * Картинку берём у посадочной: сначала по названию (текст ссылки пишут по
 * нему, это надёжнее), потом по URL из свойства CPY_FILTER_TAG.
 */
if (!function_exists('ndSectionLandingPics')) {
	/**
	 * Карта картинок посадочных страниц каталога: по названию и по URL.
	 *
	 * @return array {NAME: {строчное название: ID файла}, URL: {url: ID файла}}
	 */
	function ndSectionLandingPics()
	{
		static $arPics;
		if (isset($arPics))
			return $arPics;

		$arPics = array('URL' => array(), 'NAME' => array());
		$res = CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => 21, 'ACTIVE' => 'Y'),
			false,
			false,
			array('ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_CPY_FILTER_TAG')
		);
		while ($arLanding = $res->Fetch()) {
			if (!$arLanding['PREVIEW_PICTURE'])
				continue;

			$url = trim((string)$arLanding['PROPERTY_CPY_FILTER_TAG_VALUE']);
			/* Один URL встречается у разных посадочных (опечатка в
			   CPY_FILTER_TAG), поэтому занятый ключ не перезаписываем —
			   картинка досталась бы чужой ссылке. */
			if ($url !== '') {
				$key = rtrim($url, '/') . '/';
				if (!isset($arPics['URL'][$key]))
					$arPics['URL'][$key] = $arLanding['PREVIEW_PICTURE'];
			}
			$arPics['NAME'][mb_strtolower(trim($arLanding['NAME']))] = $arLanding['PREVIEW_PICTURE'];
		}

		return $arPics;
	}
}

if (!function_exists('ndSectionMenuLinks')) {
	/**
	 * Разбирает UF_MENULINK_TOP раздела в список карточек.
	 *
	 * @param array $arSection раздел; поле может прийти массивом (как отдаёт
	 *                         компонент) или сериализованной строкой (как лежит
	 *                         в b_uts_iblock_19_section). Если его нет вовсе —
	 *                         догрузим по $arSection['ID'].
	 * @param int   $iBlockId  инфоблок раздела, если его нет в $arSection.
	 * @return array список {LINK, TEXT, PIC_ID}
	 */
	function ndSectionMenuLinks($arSection, $iBlockId = 0)
	{
		$raw = isset($arSection['UF_MENULINK_TOP']) ? $arSection['UF_MENULINK_TOP'] : null;

		/* Компонент каталога отдаёт в $arSection лишь часть пользовательских
		   полей (UF_COMMENT_PRICE, UF_ELEMENTS…), UF_MENULINK_TOP среди них
		   нет — догружаем сами. Один поиск по первичному ключу
		   b_uts_iblock_<N>_section, результат держим на время хита. */
		if ($raw === null && !empty($arSection['ID'])) {
			static $arLoaded = array();
			$sectionId = (int)$arSection['ID'];
			if (!array_key_exists($sectionId, $arLoaded)) {
				$iBlockId = (int)($iBlockId ?: $arSection['IBLOCK_ID']);
				$arLoaded[$sectionId] = false;
				if ($iBlockId) {
					global $USER_FIELD_MANAGER;
					$arUF = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iBlockId . '_SECTION', $sectionId, LANGUAGE_ID);
					if (isset($arUF['UF_MENULINK_TOP']['VALUE']))
						$arLoaded[$sectionId] = $arUF['UF_MENULINK_TOP']['VALUE'];
				}
			}
			$raw = $arLoaded[$sectionId];
		}

		if (empty($raw))
			return array();

		if (is_string($raw)) {
			$unserialized = @unserialize($raw);
			$raw = ($unserialized !== false) ? $unserialized : array($raw);
		}
		if (!is_array($raw))
			return array();

		$arPics = ndSectionLandingPics();
		$arCards = array();

		foreach ($raw as $sHtml) {
			if (!is_string($sHtml) || $sHtml === '')
				continue;

			$sHtml = htmlspecialchars_decode($sHtml);
			if (!preg_match('/<a\s+([^>]*?)>(.*?)<\/a>/is', $sHtml, $m))
				continue;

			$text = trim(strip_tags($m[2]));
			$href = '';
			if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $m[1], $mh))
				$href = trim($mh[1]);

			if ($text === '' || $href === '')
				continue;

			$picId = 0;
			$nameKey = mb_strtolower($text);
			if (isset($arPics['NAME'][$nameKey]))
				$picId = $arPics['NAME'][$nameKey];
			if (!$picId) {
				$urlKey = rtrim($href, '/') . '/';
				if (isset($arPics['URL'][$urlKey]))
					$picId = $arPics['URL'][$urlKey];
			}

			$arCards[] = array(
				'LINK'   => $href,
				'TEXT'   => $text,
				'PIC_ID' => (int)$picId,
			);
		}

		return $arCards;
	}
}
