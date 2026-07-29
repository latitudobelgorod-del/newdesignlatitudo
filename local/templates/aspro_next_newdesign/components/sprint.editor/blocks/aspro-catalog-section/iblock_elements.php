<?/** @var $block array */?><?
$catalog_id = CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_catalog'][0];
$tizers_id = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_tizers"][0];
$videos_id = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_video"][0];
$projects_id = CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_projects"][0];

if (empty($block['iblock_id']))  {
  return;
}
elseif ($catalog_id == $block['iblock_id']) {
    include __DIR__ . '/iblock_elements__aspro-catalog.php';
} elseif ($tizers_id == $block['iblock_id']){
    include __DIR__ . '/iblock_elements__aspro-tizers.php';
} 
elseif ($projects_id == $block['iblock_id']){
    include __DIR__ . '/iblock_elements__aspro-projects.php';
} 
elseif ($videos_id == $block['iblock_id']){
    include __DIR__ . '/iblock_elements__aspro-videos.php';
} 
else {
    include __DIR__ . '/iblock_elements__default.php';
}   
