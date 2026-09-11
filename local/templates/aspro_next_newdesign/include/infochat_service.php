<?php
/* Блок «Уточните наличие и условия доставки» внутри страницы услуги — новый
   дизайн, как на детальной проекта (news.detail/projects_newdesign): на широком
   экране строка с фото, контактами и кнопкой (infochat_projects.php рядом), на
   телефоне — карточка из /include/infochat_newdesign.php.

   Подключают его файлы редактора услуг /include/service/<услуга>/<услуга>_info.php
   (пирсы на сваях, эксплуатируемая кровля, металлокаркас; блок «Компонент»
   в EDITOR1) — только при шаблоне нового дизайна, старому дизайну они печатают
   прежний блок. Сами эти файлы вне Git, поэтому разметка живёт здесь
   (Ирина, 11 сентября 2026). */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
?>
<div class="nd-service-infochat">
	<div class="infochat nd-infochat-wrap hidden-xs">
		<?include __DIR__.'/infochat_projects.php';?>
	</div>
	<div class="infochat nd-infochat-wrap visible-xs">
		<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat_newdesign.php",
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "",
				"AREA_FILE_RECURSIVE" => "Y",
				"EDIT_TEMPLATE" => "standard.php"
			),
			false
		);?>
	</div>
</div>
