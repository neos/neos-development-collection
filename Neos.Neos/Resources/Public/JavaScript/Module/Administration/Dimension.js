(function () {
  function setStyleForSelector(selector, display) {
    Array.prototype.forEach.call(
      document.querySelectorAll(selector),
      function (el) {
        el.style.display = display;
      }
    );
  }

  Array.prototype.forEach.call(
    document.querySelectorAll("circle"),
    function (el) {
      el.addEventListener("mouseenter", function () {
        setStyleForSelector(
          'line[data-from="' + el.getAttribute("id") + '"]',
          ""
        );
        setStyleForSelector(
          'line[data-to="' + el.getAttribute("id") + '"]',
          ""
        );
      });

      el.addEventListener("mouseout", function () {
        setStyleForSelector(
          'line[data-from="' + el.getAttribute("id") + '"]',
          "none"
        );
        setStyleForSelector(
          'line[data-to="' + el.getAttribute("id") + '"]',
          "none"
        );
      });
    }
  );
})();
