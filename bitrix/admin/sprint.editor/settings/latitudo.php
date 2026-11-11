<?php


$settings = array(
    'title' => 'latitudo.ru',

    'layout_classes' => array(
        'type1' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'latitudo-md-3', 'latitudo-md-4')
        ),
        'type2' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12','latitudo-md-3', 'latitudo-md-4' ),
        ),
        'type3' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'latitudo-md-3', 'latitudo-md-4'),

        ),
        'type4' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'latitudo-md-3', 'latitudo-md-4' ),

        ),
    ),

    'layout_enabled'   => [
        'layout_1',
        'layout_2',
        'layout_3',
        'layout_4',
    ],
	
    'layout_defaults' => array(
        'type1' => '',
        'type2' => 'col-md-6',
        'type3' => 'col-md-4',
        'type4' => 'col-md-3',
    ),

    'layout_titles' => array(
        'col-md-12' => '100%',
        'col-md-9' => '75%',
        'col-md-8' => '66.66%',
        'col-md-6' => '50%',
        'col-md-4' => '33.33%',
        'col-md-3' => '25%',

    ),

    'block_settings' => array(
        'text' => array(

            'csslist' => array(
                'type' => 'hidden',
                'value' => array(
                    'sp-text-1' => 'Стиль 1',
                    'sp-text-2' => 'Стиль 2',
					'gr_block' => 'Зеленая заливка блока',
                )

            )
        ),
		       'iblock_elements' => array(
            
                     
              'param1' => array(
                'type'  => 'select',
                'value' => array(
                    'style1' => 'Шаблон (для страницы услуг)',
                    'style2' => 'Шаблон (как в Портфолио)',
                    // лента со стрелками и счётчиком для каталога товаров —
                    // iblock_elements__aspro-catalog.php шаблона нового дизайна
                    // (Ирина, 11 сентября 2026)
                    'style3' => 'Каталог товаров — слайдер',
                    // портфолио: карточка проекта со своей подписью вместо названия —
                    // подписи задаются рядом с элементом (local/admin/sprint.editor/blocks/iblock_elements)
                    // (Ирина, 14 октября 2026)
                    'style4' => 'Элемент со своей подписью',
                    // сертификаты: все документы с галочкой «Показывать на странице Лицензии и сертификаты»,
                    // табы по производителям (iblock_elements__aspro-licenses.php, Ирина, 10 ноября 2026)
                    'style5' => 'Сертификаты — табы по производителям (по галочке)',
                    // монтажные схемы и инструкции: все активные документы ИБ 13 со словом про монтаж
                    // в названии и активной маркой (iblock_elements__nd-instructions.php, Ирина, 11 ноября 2026)
                    'style6' => 'Монтажные схемы и инструкции — все (по названию)',
                ),
            ),
        ),
				
    ),
	
'snippets' => [
        [
            'file'        => 'brands_catalog.php',
            'title'       => 'brands_catalog',
            'description' => '<strong>Пример сниппета</strong>',
        ],
 [
            'file'        => 'kupit_dosky.php',
            'title'       => 'kupit_dosky',
            'description' => '<strong>Пример сниппета</strong>',
        ],

  
    ],


);


$formlist = [];
if (\CModule::IncludeModule('form')){
    $by = 's_name';
    $order = 'asc';
    $isfiltered = null;
    $dbres = \CForm::GetList($by, $order, array(
        'ACTIVE' => 'Y'
    ),$isfiltered);

    while ($form = $dbres->Fetch()){
        $formlist[ $form['SID'] ] = $form['NAME'];
    }
}

if (!empty($formlist)){
    $settings['block_settings']['button_link'] = array(
        'form_id' => array(
            'type' => 'select',
            'value' => $formlist
        ),
    );

}

if (!empty($formlist)){
    $settings['block_settings']['form_inline'] = array(
        'form_id' => array(
            'type' => 'select',
            'value' => $formlist
        ),
    );

}

