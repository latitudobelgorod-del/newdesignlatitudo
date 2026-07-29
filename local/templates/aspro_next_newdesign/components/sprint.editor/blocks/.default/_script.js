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

'use strict';
function r(f){/in/.test(document.readyState)?setTimeout('r('+f+')',9):f()}
r(function(){
    if (!document.getElementsByClassName) {
        // Поддержка IE8
        var getElementsByClassName = function(node, classname) {
            var a = [];
            var re = new RegExp('(^| )'+classname+'( |$)');
            var els = node.getElementsByTagName("*");
            for(var i=0,j=els.length; i < j; i++)
                if(re.test(els[i].className))a.push(els[i]);
            return a;
        }
        var videos = getElementsByClassName(document.body,"youtube");
    } else {
        var videos = document.getElementsByClassName("youtube");
    }
    var nb_videos = videos.length;
    for (var i=0; i < nb_videos; i++) {
        // Находим постер для видео, зная ID нашего видео
        videos[i].style.backgroundImage = 'url(http://i.ytimg.com/vi/' + videos[i].id + '/sddefault.jpg)';
        // Размещаем над постером кнопку Play, чтобы создать эффект плеера
        var play = document.createElement("div");
        play.setAttribute("class","play");
        videos[i].appendChild(play);
        videos[i].onclick = function() {
            // Создаем iFrame и сразу начинаем проигрывать видео, т.е. атрибут autoplay у видео в значении 1
            var iframe = document.createElement("iframe");
            var iframe_url = "https://www.youtube.com/embed/" + this.id + "?autoplay=1&autohide=1";
            if (this.getAttribute("data-params")) iframe_url+='&'+this.getAttribute("data-params");
            iframe.setAttribute("src",iframe_url);
            iframe.setAttribute("frameborder",'0');
            // Высота и ширина iFrame будет как у элемента-родителя
            iframe.style.width  = this.style.width;
            iframe.style.height = this.style.height;
            // Заменяем начальное изображение (постер) на iFrame
            this.parentNode.replaceChild(iframe, this);
        }
    }
});