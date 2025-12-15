angular
  .module("app")
  .directive("dateInput", function () {
    return {
      require: "ngModel",
      link: function (scope, elem, attr, modelCtrl) {
        modelCtrl.$formatters.push(function (modelValue) {
          if (modelValue) {
            return new Date(modelValue);
          } else {
            return null;
          }
        });
      },
    };
  })
  .directive("numbersOnly", function () {
    return {
      require: "ngModel",
      link: function (scope, element, attrs, modelCtrl) {
        modelCtrl.$parsers.push(function (inputValue) {
          if (inputValue === undefined) return "";
          var transformedInput = inputValue.replace(/[^-0-9.]/g, "");
          if (transformedInput != inputValue) {
            modelCtrl.$setViewValue(transformedInput);
            modelCtrl.$render();
          }
          return transformedInput;
        });
      },
    };
  })
  .directive("numbersPositive", function () {
    return {
      require: "ngModel",
      link: function (scope, element, attrs, modelCtrl) {
        modelCtrl.$parsers.push(function (inputValue) {
          if (inputValue === undefined) return "";
          var transformedInput = inputValue.replace(/[^0-9.]/g, "");
          if (transformedInput != inputValue) {
            modelCtrl.$setViewValue(transformedInput);
            modelCtrl.$render();
          }
          return transformedInput;
        });
      },
    };
  })
  .filter("abs", function () {
    return function (val) {
      return Math.abs(val);
    };
  })
  .filter("hasPercentage", function () {
    return function (inputString) {
      if (typeof inputString === "string" && inputString.indexOf("%") !== -1) {
        return true;
      }
      return false;
    };
  })
  .filter("removeHTMLTags", [
    "$sce",
    function ($sce) {
      return function (input) {
        var decodedHtml = input;
        return $sce.trustAsHtml(decodedHtml);
      };
    },
  ])
  .directive("stringToNumber", function () {
    return {
      require: "ngModel",
      link: function (scope, element, attrs, ngModel) {
        ngModel.$parsers.push(function (value) {
          return "" + value;
        });
        ngModel.$formatters.push(function (value) {
          return parseFloat(value);
        });
      },
    };
  })
  .directive("fileInput", [
    "$parse",
    function ($parse) {
      return {
        restrict: "A",
        link: function (scope, element, attrs) {
          element.on("change", function (event) {
            var files = event.target.files;
            $parse(attrs.fileInput).assign(scope, { files: files });
            scope.$apply(function () {
              scope.handleFileSelected({ files: files });
            });
          });
        },
      };
    },
  ]);
