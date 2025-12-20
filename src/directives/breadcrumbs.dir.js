angular.module("app").directive("breadcrumbs", function (BreadcrumbService, $state) {
  return {
    template: `
      <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li ng-repeat="bc in breadcrumbs track by $index"
              class="breadcrumb-item"
              ng-class="{active: $last}"
              ng-attr-aria-current="{{$last ? 'page' : undefined}}">

            <!-- If not last and not abstract, make it a link -->
            <a ng-if="!$last && !isAbstract(bc.state)" ui-sref="{{bc.state}}">
              {{bc.home ? 'Home' : bc.name}}
            </a>

            <!-- If abstract or last, display as plain text -->
            <span ng-if="$last || isAbstract(bc.state)" class="breadcrumb-item-active">
              {{bc.home ? 'Home' : bc.name}}
            </span>
          </li>
        </ol>
      </nav>
    `,
    link: function (scope) {
      scope.breadcrumbs = BreadcrumbService.getBreadcrumbs();

      // Helper function: check if state is abstract
      scope.isAbstract = function (stateName) {
        var stateObj = $state.get(stateName);
        return stateObj && stateObj.abstract === true;
      };

      scope.$on("$stateChangeSuccess", function () {
        scope.breadcrumbs = BreadcrumbService.getBreadcrumbs();
      });
    },
  };
});
