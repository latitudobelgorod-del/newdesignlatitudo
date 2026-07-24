$(document).on('click', '#headerfixed .buy.btn', function(){
	if ($('.catalog_detail .buy_block .offer_buy_block .to-cart').length) {
		if ($('.catalog_detail .buy_block .offer_buy_block .to-cart').is(':visible')) {
			$('.catalog_detail .buy_block .offer_buy_block .to-cart').trigger('click');
		} else {
			location.href = arNextOptions['PAGES']['BASKET_PAGE_URL'];
		}
	} else if ($('.catalog_detail .buy_block .offer_buy_block .btn').length) {
		$('.catalog_detail .buy_block .offer_buy_block .btn').trigger('click')
	}
})

$(document).on('click', '#headerfixed .bx_catalog_item_scu', function(){
	var offset = 0;
	offset = $('.catalog_detail .sku_props .bx_catalog_item_scu').offset().top;
		
	$('body, html').animate({scrollTop: offset-150}, 500);
})

$(document).on('click', ".stores-title .stores-title__list", function(){
	var _this = $(this);
	_this.siblings().removeClass('stores-title--active');
	_this.addClass('stores-title--active');

	$('.stores_block_wrap .stores-amount-list').hide(100).removeClass('stores-amount-list--active');
	$('.stores_block_wrap .stores-amount-list:eq('+_this.index()+')').show(100, function(){
		if(_this.hasClass('stores-title--map'))
		{
			if(typeof map !== 'undefined')
			{
				if (typeof map.container !== 'undefined'){
					map.container.fitToViewport();
				} else if(typeof window.google !== 'undefined' && typeof bounds !== 'undefined') {
					map.fitBounds(bounds);
				}

				if(typeof clusterer !== 'undefined' && !$(this).find('.detail_items').is(':visible'))
				{
					map.setBounds(clusterer.getBounds(), {
						zoomMargin: 40,
						// checkZoomRange: true
					});
				}
			}
		}
	}).addClass('stores-amount-list--active');

})

$(document).ready(function(){
    lazyLoadPagenBlock();
});