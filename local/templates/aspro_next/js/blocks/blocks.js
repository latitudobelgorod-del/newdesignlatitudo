InitMenuNavigationAim = function(){
	var $block = $('.menu-navigation__sections-wrapper .menu-navigation__sections:not(.aim-init)');
	if($block.length){
		$block.addClass('aim-init');
		$block.menuAim({
	        firstActiveRow: true,
	        rowSelector: "> .menu-navigation__sections-item",
	        activate: function activate(a) {
	            var _this = $(a),
	                index = _this.index(),
	                items = _this.closest('.menu-navigation__sections-wrapper').next(),
	                link = _this.find('> a');

	            _this.siblings().find('> a').addClass('dark_link')
	            link.addClass('colored_theme_text').removeClass('dark_link');
	            items.find('.parent-items').siblings().hide();
	            items.find('.parent-items').eq(index).show();
	        },
	        deactivate: function deactivate(a) {
	            var _this = $(a),
	                index = _this.index(),
	                items = _this.closest('.menu-navigation__sections-wrapper').next(),
	                link = _this.find('> a');

	          link.removeClass('colored_theme_text').addClass('dark_link');
	          items.find('.parent-items').siblings().hide();
	        }
	    });
	}
}

$(document).ready(function(){
	/*many items menu*/
	InitMenuNavigationAim();
	/**/
})