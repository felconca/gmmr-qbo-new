angular.module("app").service("ToastService", [
  "$compile",
  "$rootScope",
  "$timeout",
  function ($compile, $rootScope, $timeout) {
    let containerEl, scope;

    const POSITION_MAP = {
      "top-start": "top-0 start-0",
      "top-center": "top-0 start-50 translate-middle-x",
      "top-end": "top-0 end-0",
      "middle-center": "top-50 start-50 translate-middle",
      "bottom-start": "bottom-0 start-0",
      "bottom-center": "bottom-0 start-50 translate-middle-x",
      "bottom-end": "bottom-0 end-0",
    };

    const ICON_MAP = {
      primary: "bi-info-circle-fill",
      success: "bi-check-circle-fill",
      danger: "bi-x-circle-fill",
      warning: "bi-exclamation-triangle-fill",
      info: "bi-info-circle-fill",
      dark: "bi-bell-fill",
    };

    function init(position) {
      if (containerEl) return;

      scope = $rootScope.$new(true);
      scope.toasts = [];

      const posClass = POSITION_MAP[position] || POSITION_MAP["top-end"];

      const template = `
        <div class="toast-container position-fixed p-3 ${posClass}" style="z-index:1200">
          <div ng-repeat="t in toasts track by $index"
               class="toast align-items-center border-0"
               ng-class="t.bgClass"
               role="alert"
               aria-live="assertive"
               aria-atomic="true">

            <!-- NO TITLE / SIMPLE TOAST -->
            <div class="d-flex" ng-if="!t.title">
              <div class="toast-body d-flex align-items-center gap-2">
                <i ng-if="t.icon" class="bi" ng-class="t.icon"></i>
                <span ng-bind-html="t.message"></span>
              </div>
              <button type="button"
                      class="btn-close"
                      ng-class="t.closeClass"
                      data-bs-dismiss="toast"></button>
            </div>

            <!-- TITLE TOAST -->
            <div ng-if="t.title">
              <div class="toast-header">
                <i ng-if="t.icon" class="bi me-2" ng-class="t.icon"></i>
                <strong class="me-auto">{{t.title}}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
              </div>
              <div class="toast-body" ng-bind-html="t.message"></div>
            </div>

          </div>
        </div>
      `;

      containerEl = $compile(template)(scope);
      document.body.appendChild(containerEl[0]);
    }

    this.show = function ({
      message,
      title = null,
      type = "primary", // primary, success, danger, warning, info, dark
      delay = 3000,
      autohide = true,
      position = "top-end",
    }) {
      init(position);

      const toast = {
        title,
        message,
        bgClass: `text-bg-${type}`,
        icon: ICON_MAP[type],
        closeClass: type !== "light" ? "btn-close-white me-2 m-auto" : "me-2 m-auto",
      };

      scope.toasts.push(toast);

      $timeout(() => {
        const els = containerEl[0].querySelectorAll(".toast");
        const el = els[els.length - 1];

        const instance = new bootstrap.Toast(el, { delay, autohide });
        instance.show();

        el.addEventListener("hidden.bs.toast", () => {
          const idx = scope.toasts.indexOf(toast);
          if (idx > -1) {
            scope.toasts.splice(idx, 1);
            scope.$applyAsync();
          }
        });
      });
    };
  },
]);
