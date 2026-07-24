$(document).ready(function () {
  $(document).on("click", ".dropdown-select .dropdown-select__title", function () {
    var _this = $(this),
      drawer = _this.parent().find("> .dropdown-select__drawer");

    if (!_this.hasClass("clicked")) {
      _this.addClass("clicked");
      _this.toggleClass("opened");

      drawer.animate(
        {
          height: "toggle",
        },
        100,
        function () {
          _this.removeClass("clicked");
        }
      );
    }
  });
  // close select
  $("html, body").on("mousedown", function (e) {
    if (typeof e.target.className == "string" && e.target.className.indexOf("adm") < 0) {
      e.stopPropagation();

      if (!$(e.target).closest(".dropdown-select").length) {
        $(".dropdown-select .dropdown-select__title.opened").click();
      }
    }
  });
});
