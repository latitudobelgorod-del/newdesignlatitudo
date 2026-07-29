<?/** @var $block array */?><?

$catalog_id = CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];
$projects_id = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_projects"][0];



if ($catalog_id == $block['iblock_id']) {
    include __DIR__ . '/iblock_sections__aspro-catalog.php';
} 
 elseif ($projects_id == $block['iblock_id']){
    include __DIR__ . '/iblock_sections__aspro-projects.php';
} 
else {
    include __DIR__ . '/iblock_sections__default.php';
}

