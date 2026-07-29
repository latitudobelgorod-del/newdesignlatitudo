/* Общие скрипты для блоков */
//аккордеон
$(document).ready(function() {
  //прикрепляем клик по заголовкам acc-head
  $('.accordeon_lat .acc-head').on('click', f_acc);
});

function f_acc(){
//скрываем все кроме того, что должны открыть
  $('.accordeon_lat .acc-body').not($(this).next()).slideUp();
// открываем или скрываем блок под заголовоком, по которому кликнули
 $(this).next().slideToggle();
}
//аккордеон
/*accordion*/


document.addEventListener("DOMContentLoaded", function (e) {
    var acc = document.getElementsByClassName("sp-accordion");
    for (var accIndex = 0; accIndex < acc.length; accIndex++) {
        if (!acc[accIndex].classList.contains('sp-accordion__initialized')) {
            acc[accIndex].classList.add('sp-accordion__initialized');
            var titles = acc[accIndex].getElementsByClassName("sp-accordion-title");
            for (var titleIndex = 0; titleIndex < titles.length; titleIndex++) {
                titles[titleIndex].addEventListener("click", function () {
                    this.classList.toggle("sp-accordion-title__active");
                    var panel = this.nextElementSibling;
                    if (panel.style.display === "block") {
                        panel.style.display = "none";
                    } else {
                        panel.style.display = "block";
                    }
                });
            }
        }
    }
});

function checkNavColor(slider){
	var nav_color_flex = slider.find('.flex-active-slide').data('nav_color');
	if(nav_color_flex == 'dark')
		slider.find('.flex-control-nav').addClass('flex-dark');
	else
		slider.find('.flex-control-nav').removeClass('flex-dark');
}
$(document).ready(function(){
	if($('.top_slider_wrapp .flexslider').length){
		var config = {"controlNav": true, "animationLoop": true, "slideshow" : false, "pauseOnHover" : true};
		if(typeof(arNextOptions['THEME']) != 'undefined'){
			
		
			config.start = function(slider){
				checkNavColor(slider);
				
				if(slider.count <= 1){
					slider.find('.flex-direction-nav li').addClass('flex-disabled');
				}
				$(slider).find('.flex-control-nav').css('opacity',1);
			}
			config.after = function(slider){
				checkNavColor(slider);
			}
		}

		$(".top_slider_wrapp .flexslider").flexslider(config);
	}
});