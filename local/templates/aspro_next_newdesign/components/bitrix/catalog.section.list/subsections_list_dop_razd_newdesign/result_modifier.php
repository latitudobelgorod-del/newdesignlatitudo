<?
	/* ND_FLAT=Y — не собирать дерево, выводить разделы как пришли.
	   Нужен блоку редактора (sprint.editor, iblock_sections__aspro-catalog):
	   там состав задан списком ID, разделы могут быть любого уровня и родителей
	   их в выборке нет. Дерево ниже кладёт раздел 2-го уровня внутрь
	   $arSections[родитель], а раз родителя в списке нет — создаётся пустая
	   заготовка, и вместо плиток выходили пустые карточки без имени и ссылки.
	   Страница раздела каталога передаёт TOP_DEPTH=1 и сюда не заходит. */
	/* Скрытые из меню разделы не показываем и плитками (Ирина, 7 сентября
	   2026): галка «показывать в меню» (UF_SECTION_IN_MENU) — одно правило
	   на оба места. Блок редактора (ND_FLAT) не трогаем: там состав задан
	   списком ID руками, и галка к нему отношения не имеет.

	   Галку спрашиваем СВОИМ запросом. Штатный параметр SECTION_USER_FIELDS
	   не годится: компонент дописывает поле в список выбираемых, а он у
	   этого вызова пуст — «все поля» превратились бы в «только эти».
	   Запрос один на страницу и уезжает в кеш компонента вместе с
	   разметкой. */
	if(($arParams["ND_FLAT"] ?? '') !== 'Y' && !empty($arResult["SECTIONS"]) && CModule::IncludeModule('iblock')){
		$ndIds = array();

		foreach($arResult["SECTIONS"] as $arItem)
			$ndIds[] = (int)$arItem['ID'];

		$ndInMenu = array();
		$rsFlags = CIBlockSection::GetList(
			array(),
			array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ID' => $ndIds),
			false,
			array('ID', 'UF_SECTION_IN_MENU')
		);

		while($arFlag = $rsFlags->Fetch()){
			if((string)$arFlag['UF_SECTION_IN_MENU'] === '1')
				$ndInMenu[(int)$arFlag['ID']] = true;
		}

		foreach($arResult["SECTIONS"] as $key => $arItem){
			if(!isset($ndInMenu[(int)$arItem['ID']]))
				unset($arResult["SECTIONS"][$key]);
		}
	}

	if($arParams["TOP_DEPTH"]>1 && ($arParams["ND_FLAT"] ?? '') !== 'Y'){
		$arSections = array();
		$arSectionsDepth3 = array();
		foreach( $arResult["SECTIONS"] as $arItem ) {
			if( $arItem["DEPTH_LEVEL"] == 1 ) { $arSections[$arItem["ID"]] = $arItem;}
			elseif( $arItem["DEPTH_LEVEL"] == 2 ) {$arSections[$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;}
			elseif( $arItem["DEPTH_LEVEL"] == 3 ) {$arSectionsDepth3[] = $arItem;}
		}
		if($arSectionsDepth3){
			foreach( $arSectionsDepth3 as $arItem) {
				foreach( $arSections as $key => $arSection) {
					if (is_array($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]) && !empty($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]])) {
						$arSections[$key]["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;
					}
				}
			}
		}
		$arResult["SECTIONS"] = $arSections;
	}
?>