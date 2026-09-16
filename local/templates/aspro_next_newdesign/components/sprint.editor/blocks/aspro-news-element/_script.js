/* Общие скрипты для блоков */
//аккордеон
// Клик ловим на документе, а не на заголовках: на посадочных каталога блок
// стоит в #right_block_ajax, который тема перерисовывает после загрузки, и
// привязка к самим заголовкам при ready терялась — аккордеон не открывался.
//
// f_acc — общее имя: такую же функцию объявляют скрипты других шаблонов
// блоков (aspro-catalog-section — пустую, top-tag вешает её на клик сам).
// Поэтому свою держим под своим именем, глобальную f_acc подменяем на неё,
// а один клик обрабатываем один раз — метка на исходном событии.
function ndAccToggle(e){
  var orig = e && e.originalEvent;
  if (orig) {
    if (orig.ndAccDone) {
      return;
    }
    orig.ndAccDone = true;
  }
  var $item = $(this).closest('.accordion-item');
//скрываем все кроме того, что должны открыть
  $('.accordeon_lat .accordion-item.is-open').not($item).removeClass('is-open')
    .children('.acc-head').attr('aria-expanded', 'false');
  $('.accordeon_lat .acc-body').not($(this).next()).slideUp();
// открываем или скрываем блок под заголовоком, по которому кликнули;
// класс is-open красит метку и меняет плюс на минус
  $item.toggleClass('is-open');
  $(this).attr('aria-expanded', $item.hasClass('is-open') ? 'true' : 'false');
  $(this).next().slideToggle();
}
window.f_acc = ndAccToggle;
$(document).ready(function() {
  window.f_acc = ndAccToggle;
});
$(document).off('.ndAcc')
  .on('click.ndAcc', '.accordeon_lat .acc-head', ndAccToggle)
  .on('keydown.ndAcc', '.accordeon_lat .acc-head', function(e) {
    // заголовок — role="button": открывается и с клавиатуры
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      ndAccToggle.call(this, e);
    }
  });
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