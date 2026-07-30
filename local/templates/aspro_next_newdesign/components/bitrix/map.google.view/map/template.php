<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
//$this->setFrameMode(true);
global $APPLICATION;

		$arThemeValues = CNext::GetFrontParametrsValues(SITE_ID);
		$isBaseCustom = $arThemeValues['BASE_COLOR'] == 'CUSTOM';
		$isMoreCustom = $arThemeValues['MORE_COLOR'] == 'CUSTOM';;
		$arBaseColors = CNext::$arParametrsList['MAIN']['OPTIONS']['BASE_COLOR']['LIST'];
		
        if($isBaseCustom){
            $baseColor = (strpos($arThemeValues['BASE_COLOR_CUSTOM'], '#') === false ? '#' : '').$arThemeValues['BASE_COLOR_CUSTOM'];
        }
        else{
            $baseColor = $arBaseColors[$arThemeValues['BASE_COLOR']]['COLOR'];

        }


if (!$baseColor) {
	$baseColor = '#365edc';
}

$frame = $this->createFrame()->begin('');
$frame->setAnimation(true);
$arTransParams = array(
	'INIT_MAP_TYPE' => $arParams['INIT_MAP_TYPE'],
	'INIT_MAP_LON' => $arResult['POSITION']['google_lon'],
	'INIT_MAP_LAT' => $arResult['POSITION']['google_lat'],
	'INIT_MAP_SCALE' => $arResult['POSITION']['google_scale'],
	'MAP_WIDTH' => $arParams['MAP_WIDTH'],
	'MAP_HEIGHT' => $arParams['MAP_HEIGHT'],
	'CONTROLS' => $arParams['CONTROLS'],
	'OPTIONS' => $arParams['OPTIONS'],
	'MAP_ID' => $arParams['MAP_ID'],
	'API_KEY' => $arParams['API_KEY'],
);

if ($arParams['DEV_MODE'] == 'Y'){
	$arTransParams['DEV_MODE'] = 'Y';
	if ($arParams['WAIT_FOR_EVENT'])
		$arTransParams['WAIT_FOR_EVENT'] = $arParams['WAIT_FOR_EVENT'];
}
$arParams["CLICKABLE"] = ( $arParams["CLICKABLE"] ? $arParams["CLICKABLE"] : "Y" );?>
	<div class="module-map">
		<div class="map-wr module-contacts-map-layout">
			<?$APPLICATION->IncludeComponent('bitrix:map.google.system', '.default', $arTransParams, false, array('HIDE_ICONS' => 'Y'));?>
		</div>
	</div>
<?$APPLICATION->AddHeadScript( $this->__folder.'/markerclustererplus.min.js', true )?>
<?$APPLICATION->AddHeadScript( $this->__folder.'/infobox.js', true )?>
<script>
	if (!window.BX_GMapAddPlacemark_){
		window.BX_GMapAddPlacemark_ = function(markers, bounds, arPlacemark, map_id, clickable){
			var map = GLOBAL_arMapObjects[map_id];
			if (null == map) {
				return false;
			}

			if (!arPlacemark.LAT || !arPlacemark.LON) {
				return false;
			}

			var pt = new google.maps.LatLng(arPlacemark.LAT, arPlacemark.LON);
			bounds.extend(pt);

			var template = ['<svg xmlns="http://www.w3.org/2000/svg" width="46" height="46" class="marker dynamic" viewBox="0 0 46 57"> <defs><style>.cls-marker, .cls-marker3 {fill: %23fff;}.cls-marker, .cls-marker2 {fill-rule: evenodd;}.cls-marker {opacity: 0.5;}.cls-marker2 {fill: {{ color }};}</style></defs> <path data-name="Ellipse 275 copy" class="cls-marker" d="M142.976,200.433L143,200.469s-7.05,5.826-10,10.375c-2.263,3.489-2.974,6.153-5,6.156s-2.737-2.667-5-6.156c-2.95-4.549-10-10.375-10-10.375l0.024-.036A23,23,0,1,1,142.976,200.433Z" transform="translate(-105 -160)"/> <path data-name="Ellipse 253 copy 4" class="cls-marker2" d="M140,198.971L140,199s-6.362,5.91-8.092,8.456C128.351,212.69,128,215,128,215s-0.307-2.084-3.826-7.448C121.8,203.935,116,199,116,199l0-.029A20,20,0,1,1,140,198.971Z" transform="translate(-105 -160)"/><circle data-name="Ellipse 254 copy 5" class="cls-marker3" cx="23" cy="23" r="12"/></svg>'].join('');
			var markerSVG = template.replace('{{ color }}', '<?=$baseColor?>').replace('#', '%23');

			var icon = {
				anchor: new google.maps.Point(19, 47),
				url: 'data:image/svg+xml;utf-8, ' + markerSVG
			}
			
			var obPlacemark = new google.maps.Marker({
				'position': pt,
				'map': map,
				'icon': icon,
				'clickable': (clickable == "Y" ? true : false),
				'title': $(arPlacemark.TEXT).length ? $(arPlacemark.TEXT).find('.title').text() : arPlacemark.TEXT,
				'zIndex': 5,
				'html': arPlacemark.HTML || arPlacemark.TEXT
			});
			markers.push(obPlacemark);
			
			var infowindow = new google.maps.InfoWindow({
				content: arPlacemark.TEXT
			});

			obPlacemark.addListener("click", function(){
				if (null != window['__bx_google_infowin_opened_' + map_id]) {
					window['__bx_google_infowin_opened_' + map_id].close();
				}
				infowindow.open(map, obPlacemark);

				window['__bx_google_infowin_opened_' + map_id] = infowindow;
			});

			google.maps.event.addListener(obPlacemark, 'mouseover', function() {
				obPlacemark.set("opacity","0.9");
			});

			google.maps.event.addListener(obPlacemark, 'mouseout', function() {
				obPlacemark.set("opacity","1");
			});

			if (BX.type.isNotEmptyString(arPlacemark.TEXT)){
				obPlacemark.infowin = new google.maps.InfoWindow({
					content: "Loading..."
				});
				
			}

			return obPlacemark;
		}
	}

	if (null == window.BXWaitForMap_view){
		function BXWaitForMap_view(map_id){
			if (null == window.GLOBAL_arMapObjects)
				return;
		
			if (window.GLOBAL_arMapObjects[map_id])
				window['BX_SetPlacemarks_' + map_id]();
			else
				setTimeout('BXWaitForMap_view(\'' + map_id + '\')', 300);
		}
	}
</script>
<?if (is_array($arResult['POSITION']['PLACEMARKS']) && ($cnt = count($arResult['POSITION']['PLACEMARKS']))):?>
	<script type="text/javascript">
		function BX_SetPlacemarks_<?echo $arParams['MAP_ID']?>(){
			var markers = [];
			bounds = new google.maps.LatLngBounds();
			<?for($i = 0; $i < $cnt; $i++):?>
				BX_GMapAddPlacemark_(markers, bounds, <?echo CUtil::PhpToJsObject($arResult['POSITION']['PLACEMARKS'][$i])?>, '<?echo $arParams['MAP_ID']?>', '<?=$arParams["CLICKABLE"];?>');
			<?endfor;?>
			<?if( $arParams["ORDER"] != "Y" ){?>
				/*cluster icon*/
				map = window.GLOBAL_arMapObjects['<?echo $arParams['MAP_ID']?>'];

				var template = ['<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'56\' height=\'56\' viewBox=\'0 0 56 56\'>',
							"<defs><style>.cls-cluster, .cls-cluster3 {fill: %23fff;}.cls-cluster {opacity: 0.5;} .cls-cluster2 {fill:{{ color }};}</style></defs>",
							"<circle class='cls-cluster' cx='28' cy='28' r='28'/>",
							"<circle data-name='Ellipse 275 copy 2' class='cls-cluster2' cx='28' cy='28' r='25'/>",
							"<circle data-name='Ellipse 276 copy' class='cls-cluster3' cx='28' cy='28' r='18'/>",
						'</svg>'].join('');

				var markerSVG = template.replace('{{ color }}', '<?=$baseColor?>').replace('#', '%23');

				var clusterOptions = {
					zoomOnClick: true,
					averageCenter: true,
					clusterClass: 'cluster',
					styles: [{
						url: 'data:image/svg+xml;utf-8,' + markerSVG,
						height: 56, 
						width: 56,
						textColor: '#333',
						textSize: 13,
						lineHeight: '56px'
						// fontFamily: 'Ubuntu'
					}]
				}
				var markerCluster = new MarkerClusterer(map, markers, clusterOptions);
			
				center = bounds.getCenter();
				<?if ( $cnt > 1 ){?>
					map.fitBounds(bounds);
				<?} else {?>
					map.setCenter({lat: +<?=$arResult['POSITION']['PLACEMARKS'][0]['LAT'];?>, lng: +<?=$arResult['POSITION']['PLACEMARKS'][0]['LON'];?>});
					map.setZoom(17);
				<?}?>
			<?}?>
			
			/*reinit map*/
			//google.maps.event.trigger(map,'resize');
		}

		function BXShowMap_<?echo $arParams['MAP_ID']?>() {
			BXWaitForMap_view('<?echo $arParams['MAP_ID']?>');
		}

		BX.ready(BXShowMap_<?echo $arParams['MAP_ID']?>);
	</script>
<?endif;?>