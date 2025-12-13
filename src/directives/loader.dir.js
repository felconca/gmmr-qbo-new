angular.module("app").directive("loader", function () {
  return { restrict: "E", template: `<div class="custom-loader"></div>` };
});
