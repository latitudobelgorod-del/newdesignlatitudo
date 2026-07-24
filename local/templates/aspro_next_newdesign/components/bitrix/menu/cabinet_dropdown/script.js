$(document).ready(function () {
    $(".wrapp-cabinet-menu").hover(function (e) {

        var target = $(this)[0];
        var target2 = $(this).find('.cabinet-dropdown');
        var targetPosition = target.getBoundingClientRect().left,
            windowPosition = document.documentElement.clientWidth;

        if ( (targetPosition + target2.width()) < windowPosition ) { 
            $(".header_wrap .cabinet-dropdown").addClass("cabinet-dropdown--toleft ");
        }
        else {
            $(".header_wrap .cabinet-dropdown").removeClass("cabinet-dropdown--toleft ");
        }
    });
}); 