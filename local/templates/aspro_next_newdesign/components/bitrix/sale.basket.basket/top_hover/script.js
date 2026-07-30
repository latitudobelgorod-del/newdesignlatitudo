var basketTimeout;
var totalSum;

function delete_all_items(type, item_section, correctSpeed){
	var index=(type=="delay" ? "2" : "1");
	if(type == "na")
		index = 4;
	 $.post( arNextOptions['SITE_DIR']+'ajax/showBasketHover.php', 'PARAMS='+$("#basket_form").find("input#fly_basket_params").val()+'&TYPE='+index+'&CLEAR_ALL=Y', $.proxy(function( data ) {
		basketTop('reload');
		$('.in-cart').hide();
		$('.in-cart').closest('.button_block').removeClass('wide');
		$('.to-cart').show();
		$('.to-cart').removeClass("clicked");
		$('.counter_block').show();
		$('.wish_item').removeClass("added");
		$('.wish_item').find('.value').show();
		$('.wish_item').find('.value.added').hide();

		// var eventdata = {action:'loadBasket'};
		// BX.onCustomEvent('onCompleteAction', [eventdata]);
	}));
}

function deleteProduct(basketId, itemSection, item, th){
	function _deleteProduct(basketId, itemSection, product_id){
		arStatusBasketAspro = {};

		$.post( arNextOptions['SITE_DIR']+'ajax/item.php', 'delete_item=Y&item='+product_id, $.proxy(function( data ){
			basketTop('reload');
			$('.to-cart[data-item='+product_id+']').removeClass("clicked");
		}));
	}

	var product_id=th.attr("product-id");
	if(checkCounters()){

		delFromBasketCounter(item);
		setTimeout(function(){
			_deleteProduct(basketId, itemSection, product_id);
		}, 100);
	}
	else{
		_deleteProduct(basketId, itemSection, product_id);
	}
}

function updateTopBasket($arBasketInfo) {

	$(".basket-link.basket .count, .wraps_icon_block.basket .count .items > span").text($arBasketInfo.COUNT);
	$(".basket-link.basket .prices").html($arBasketInfo.ALL_SUM);
	$(".basket-link.basket").attr("title", htmlEncode($arBasketInfo.TITLE));
	
	if ($arBasketInfo.COUNT <= 0) {
		$(".basket-link.basket .prices").text($arBasketInfo.EMPTY_BASKET);
		$(".basket-link.basket").removeClass("basket-count");
		$(".basket-link.basket .count").addClass("empted");
	} 
}

