$(document).ready(function(){
	$('.landings__show-all').on('click', function(){
		var $this = $(this),
			dataOpened = $this.data('opened'),
			dataText = $this.data('text'),
			thisText = $this.text(),
			item = $this.closest('.landings__section').find('.hidden_items'),
			animationTime = 400;
		
		if(dataOpened == 'N'){
			item.slideDown(animationTime);
			$this.addClass('opened').data('opened', 'Y');
		}
		else{
			item.slideUp(animationTime);
			$this.removeClass('opened').data('opened', 'N');
		}
		
		$this.data('text', thisText).text(dataText);
	});
});