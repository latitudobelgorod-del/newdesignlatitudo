<?
	/* ND_FLAT=Y — не собирать дерево, выводить разделы как пришли.
	   Нужен блоку редактора (sprint.editor, iblock_sections__aspro-catalog):
	   там состав задан списком ID, разделы могут быть любого уровня и родителей
	   их в выборке нет. Дерево ниже кладёт раздел 2-го уровня внутрь
	   $arSections[родитель], а раз родителя в списке нет — создаётся пустая
	   заготовка, и вместо плиток выходили пустые карточки без имени и ссылки.
	   Страница раздела каталога передаёт TOP_DEPTH=1 и сюда не заходит. */
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