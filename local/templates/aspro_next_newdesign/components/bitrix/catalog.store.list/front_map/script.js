$(document).ready(function(){
	$(document).on('click', '.block_container .items .item.initied', function(){
		var _this = $(this),
			itemID = _this.data('id'),
			animationTime = 200;

		_this.closest('.items').fadeOut(animationTime, function(){
			_this.closest('.block_container').find('.detail_items').fadeIn(animationTime);
			_this.closest('.block_container').find('.detail_items .item[data-id='+itemID+']').fadeIn(animationTime);

			var arCoordinates = _this.data('coordinates').split(',');

			if (typeof map === "object" && map !== null && "setCenter" in map) {
				if ($(".bx-google-map").length) {
				  map.setCenter({ lat: +arCoordinates[0], lng: +arCoordinates[1] });
				  map.setZoom(17);
				} else {
				 map.setCenter([arCoordinates[0], arCoordinates[1]], 15);
				  
				}
			}
		});
	});

	$(document).on('click', '.block_container .top-close', function(){
		var _this = $(this).closest('.block_container').find('.detail_items .item:visible'),
			animationTime = 200;
		_this.fadeOut(animationTime);
		_this.closest('.block_container').find('.detail_items').fadeOut(animationTime, function(){
			_this.closest('.block_container').find('.items').fadeIn(animationTime);

			if (typeof clusterer === "object" && clusterer !== null && "setBounds" in map && "getBounds" in clusterer) 
			{
				map.setBounds(clusterer.getBounds(), {
					zoomMargin: 40,
					// checkZoomRange: true
				});
			} else if (typeof bounds === "object" && bounds !== null && "fitBounds" in map && "getCenter" in bounds) {
				map.fitBounds(bounds);
			}
		});
	});
})