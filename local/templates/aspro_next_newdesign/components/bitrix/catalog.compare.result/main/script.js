BX.namespace("BX.Iblock.Catalog");

BX.Iblock.Catalog.CompareClass = (function () {
  var CompareClass = function (wrapObjId) {
    this.wrapObjId = wrapObjId;
  };

  CompareClass.prototype.MakeAjaxAction = function (url, refresh) {
    BX.showWait(BX(this.wrapObjId));
    BX.ajax.post(
      url,
      { ajax_action: "Y" },
      BX.proxy(function (result) {
        url = new URL(window.location.origin + url);
        const action = url.searchParams.get('action');
        const id = url.searchParams.get('ID');

        if (action === 'DELETE_FROM_COMPARE_RESULT' && typeof JNoticeSurface !== 'undefined' && id) {
          const $block = document.querySelector('[data-id="' + id + '"][data-item]');
          JNoticeSurface.get().onAdd2Compare([$block], false);
        }

        BX(this.wrapObjId).innerHTML = result;
        if (typeof refresh !== undefined) {
          const $compareItems = document.querySelector(".catalog-compare");

          if (!$compareItems) {
            arNextOptions["COMPARE_ITEMS"] = [];
            $(".js-compare-block .count").text(arNextOptions["COMPARE_ITEMS"].length);

            document.querySelectorAll(".js-compare-block.icon-block-with-counter--count").forEach(function ($el) {
              $el.classList.remove("icon-block-with-counter--count");
            });
          }

          getActualBasket('','Compare');
					if ($('#compare_fly').length){
						jsAjaxUtil.InsertDataToNode(arNextOptions['SITE_DIR'] + 'ajax/show_compare_preview_fly.php', 'compare_fly', false);
          }
        }
        BX.closeWait();
      }, this)
    );
  };

  return CompareClass;
})();

if (typeof typeofExt === 'undefined') {
  window.typeofExt = function (item) {
    const _toString = Object.prototype.toString;
    return _toString.call(item).slice(8, -1).toLowerCase();
  };  
}

if (typeof InitOwlSlider === 'undefined') {
  window.InitOwlSlider = function () {
    $(".owl-carousel:not(.owl-loaded):not(.appear-block)").each(function () {
      var slider = $(this);
      var options;
      var svg = '<svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 1L1 6L6 11" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      var defaults = {
        navText: [
          '<i class="owl-carousel__button owl-carousel__button--left colored_theme_bg_hover">' + svg + "</i>",
          '<i class="owl-carousel__button owl-carousel__button--right colored_theme_bg_hover">' + svg + "</i>",
        ],
        dotsContainer: slider.siblings(".owl-carousel__dots"),
      };
      var config = $.extend({}, defaults, options, slider.data("plugin-options"));
  
      if (slider.hasClass("destroyed")) {
        slider.owlCarousel(config);
        slider.removeClass("destroyed");
        return;
      }
  
      slider.on("initialized.owl.carousel", function (event) {
        var eventdata = { slider: $(event.target), data: event };
  
        BX.onCustomEvent("onSliderInitialized", [eventdata]);
        BX.onCustomEvent("onSlide", [eventdata]);
  
        $(event.target).removeClass("loading-state");
        $(event.target).find(".owl-item:first").addClass("current");
  
        if (typeof sliceItemBlockSlide === "function") {
          sliceItemBlockSlide();
        }
      });
  
      // if slider was inited on animated scrolling to block, than need rescroll
      if ($("body.scrolling-state").length) {
        slider.on("initialized.owl.carousel", function (event) {
          setTimeout(function () {
            scrollToBlock.rescroll();
          }, 100);
        });
      }
  
      slider.owlCarousel(config);
  
      slider.on("resized.owl.carousel", function (event) {
        if (typeof sliceItemBlockSlide === "function") {
          sliceItemBlockSlide({ resize: false });
        }
      });
  
      slider.on("changed.owl.carousel", function (event) {
        var $slider = $(event.target);
        var sliderData = $slider.data("owl.carousel");
        var pluginOptions = $slider.data("pluginOptions");
  
        var eventdata = { slider: $slider, data: event };
        BX.onCustomEvent("onSlide", [eventdata]);
  
        if (pluginOptions) {
          if (typeofExt(pluginOptions) === "object") {
            if ("relatedTo" in pluginOptions) {
              var relatedClass = pluginOptions.relatedTo,
                relatedBlock = $(relatedClass);
  
              if (relatedBlock.length && sliderData) {
                if (!sliderData.loop) {
                  var current = event.item.index;
                } else {
                  var count = event.item.count - 1;
                  var current = Math.round(event.item.index - event.item.count / 2 - 0.5);
  
                  if (current < 0) current = count;
  
                  if (current > count) current = 0;
                }
  
                relatedBlock.find(".owl-item").removeClass("current").eq(current).addClass("current");
  
                var onscreen = relatedBlock.find(".owl-item.active").length - 1;
                var start = relatedBlock.find(".owl-item.active").first().index();
                var end = relatedBlock.find(".owl-item.active").last().index();
  
                if (current > end) relatedBlock.data("owl.carousel").to(current, 100, true);
  
                if (current < start) relatedBlock.data("owl.carousel").to(current - onscreen, 100, true);
              }
            }
          }
        }
      });
  
      slider.on("translated.owl.carousel", function (event) {});
    });
  };
}

$(document).on("change", ".catalog-compare__switch #compare_diff", function () {
  var linksDiff = $(this).closest(".catalog-compare__top").find(".tabs-head"),
    url = "";

  if ($(this).is(":checked")) {
    url = linksDiff.find("li:eq(1) a").data("href");
  } else {
    url = linksDiff.find("li:eq(0) a").data("href");
  }

  BX.showWait(BX("bx_catalog_compare_block"));
  $.ajax({
    url: url,
    data: { ajax_action: "Y" },
    success: function (html) {
      history.pushState(null, null, url);
      $("#bx_catalog_compare_block").html(html);
      BX.closeWait();
    },
  });
});

function tableEqualHeight($sliderProps, $sliderPropsItems) {
  var arHeights = [];

  $sliderProps.find(".catalog-compare__prop-line").removeAttr("style");

  for (var i = 0; i < $sliderProps.find(".owl-item:first-child .catalog-compare__prop-line").length; i++) {
    arHeights[i] = 0;
  }

  //get max height
  $sliderPropsItems.each(function (i, elementI) {
    $(this)
      .find(".catalog-compare__prop-line")
      .each(function (j, elementJ) {
        if ($(this).outerHeight() > arHeights[j]) arHeights[j] = $(this).outerHeight(true);
      });
  });

  // set height
  $sliderPropsItems.each(function (i, elementI) {
    $(this)
      .find(".catalog-compare__prop-line")
      .each(function (j, elementJ) {
        $(this).css("height", arHeights[j]);
      });
  });
}

BX.addCustomEvent("onSliderInitialized", function (eventdata) {
  if (eventdata) {
    var slider = eventdata.slider;
    if (slider) {
      $(".catalog-compare__inner").removeClass("loading");
    }
  }
});

function stickyCompareItems() {
  if (window.matchMedia("(min-width:768px)").matches) {
    let propSlider = $(".compare-sections__item.active .catalog-compare__props-slider:visible");
    let headerSelector = window.matchMedia("(min-width:992px)").matches
      ? "#headerfixed"
      : ".mfixed_y.mfixed_view_always #mobileheader, .mfixed_y.mfixed_view_scroll_top #mobileheader.fixed";
    let headerHeight = $(headerSelector).length ? $(headerSelector).height() : 0;
    let comparePosition = propSlider.length > 0 ? propSlider.offset().top : 0;
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    let stickyItems = propSlider.find(".catalog-small__item");
    let topPos = 0;
    if (stickyItems.length) {
      topPos = scrollTop - comparePosition + headerHeight;
      stickyItems.css("top", topPos - 1 + "px");
    }
    if (headerHeight + scrollTop > comparePosition) {
      propSlider.addClass("show-sticky-items");
    } else {
      propSlider.removeClass("show-sticky-items");
    }
  }
}

if (typeof debounce === 'undefined') {
  window.debounce = function(f, t) {
    return function (args) {
      var previousCall = this.lastCall;
      this.lastCall = Date.now();
      if (previousCall && this.lastCall - previousCall <= t) {
        clearTimeout(this.lastCallTimer);
      }
      this.lastCallTimer = setTimeout(function () {
        f(args);
      }, t);
    };
  }
}

if ($("html.bx-touch").length) {
  $(document).scroll(debounce(stickyCompareItems, 100));
} else {
  $(document).scroll(stickyCompareItems);
}
//$(document).scroll(throttle(stickyCompareItems, 50));
//$(document).scroll(debounce(stickyCompareItems, 200));

$(document).on("click", ".compare-sections__tab-item", function () {
  let th = $(this);
  if (!th.hasClass("active")) {
    let curSectionId = th.find("[data-section-id]").attr("data-section-id");
    $(".compare-sections__tab-item").removeClass("active");
    $(".compare-sections__item").removeClass("active");
    th.addClass("active");
    if (curSectionId) {
      $(".compare-sections__item" + "[data-section-id=" + curSectionId + "]").addClass("active");
      $.cookie("compare_section", curSectionId);
    }
  }
});
