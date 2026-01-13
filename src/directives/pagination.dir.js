angular.module("app").directive("tablePagination", function () {
  return {
    restrict: "E",
    scope: {
      items: "=",
      currentPage: "=",
      itemsPerPage: "=",
      onPageChange: "&?",
    },
    template: `
            <div class="d-flex align-items-center justify-content-between pt-3">
                <span class="page-table-info">
                    Showing {{
                        items.length > 0 ? formatNumber((currentPage - 1) * itemsPerPage + 1) : 0
                    }} to {{
                        items.length > 0 ? formatNumber(Math.min(currentPage * itemsPerPage, items.length)) : 0
                    }} of {{formatNumber(items.length)}} entries
                </span>

                <ul style="margin-bottom: 0 !important;" 
                    uib-pagination 
                    total-items="items.length" 
                    num-pages="numPages" 
                    items-per-page="itemsPerPage" 
                    ng-model="currentPage" 
                    max-size="5" 
                    boundary-link-numbers="true" 
                    ng-change="pageChanged()">
                </ul>
            </div>
        `,
    link: function (scope) {
      scope.Math = window.Math;

      scope.formatNumber = function (n) {
        return n.toLocaleString();
      };

      // scope.pageChanged = function () {
      //   if (scope.onPageChange) {
      //     scope.onPageChange();
      //   }
      // };
    },
  };
});

// Usage example:
// <custom-pagination
//     items="unitF"
//     current-page="currentPage"
//     items-per-page="itemsPerPage"
//     on-page-change="pageChanged()">
// </custom-pagination>
