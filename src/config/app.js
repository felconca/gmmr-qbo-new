angular
  .module("app", [
    "ui.router",
    "ngAnimate",
    "ngSanitize",
    "ngIdle",
    "ui.bootstrap",
    "oc.lazyLoad",
    "angular.filter",
    "recepuncu.ngSweetAlert2",
  ])
  .config([
    "$compileProvider",
    "$stateProvider",
    "$urlRouterProvider",
    "$locationProvider",
    "$httpProvider",
    "IdleProvider",
    "KeepaliveProvider",
    function (
      $compileProvider,
      $stateProvider,
      $urlRouterProvider,
      $locationProvider,
      $httpProvider,
      IdleProvider,
      KeepaliveProvider
    ) {
      $locationProvider.hashPrefix("");
      $urlRouterProvider.otherwise("/404");
      $httpProvider.interceptors.push("loadingInterceptor");

      $compileProvider.debugInfoEnabled(false);
      // $httpProvider.interceptors.push("nprogressInterceptor");
      // Always include cookies with every request (for PHP sessions)
      $httpProvider.defaults.withCredentials = true;

      IdleProvider.idle(1500); // 25 inactive = sync in backend
      IdleProvider.timeout(45); // 45 sec warning before logout
      IdleProvider.autoResume("notIdle");
      KeepaliveProvider.interval(60); // ping every 1 minute

      // KeepaliveProvider.http("api/verify");

      $stateProvider.state("login", {
        url: "/login",
        templateUrl: "src/template/auth/login.tpl.php",
        controller: "authCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/auth/auth.ctrl.js");
          },
        },
        data: { breadcrumb: "Login", pageTitle: "GMMR-QBO | Login" },
      });
      $stateProvider.state("notfound", {
        url: "/404",
        templateUrl: "src/template/errors/404.tpl.php",
        data: { breadcrumb: "NotFound", pageTitle: "GMMR-QBO | 404 NotFound" },
      });

      // main app
      $stateProvider.state("app", {
        abstract: true,
        views: {
          "": { templateUrl: "src/template/layout.tpl.php" },
          "header@app": { templateUrl: "src/template/components/header.tpl.php" },
          "sidebar@app": { templateUrl: "src/template/components/sidebar.tpl.php" },
        },
      });
      $stateProvider.state("app.home", {
        url: "/dashboard",
        templateUrl: "src/template/home/home.tpl.php",
        data: {
          breadcrumb: "Home",
          pageTitle: "GMMR-QBO | Home",
          home: true,
        },
      });

      // invoices
      $stateProvider.state("app.non-pharma", {
        url: "/non-pharma",
        templateUrl: "src/template/non-pharma/list.tpl.php",
        controller: "nonpharmaCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/non-pharma/non-pharma.ctrl.js");
          },
        },
        data: { breadcrumb: "NonPharma Sales", pageTitle: "GMMR-QBO | Non-Pharma" },
      });
      $stateProvider.state("app.pharmacy", {
        url: "/pharmacy",
        templateUrl: "src/template/pharmacy/list.tpl.php",
        controller: "pharmacyCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/pharmacy/pharmacy.ctrl.js");
          },
        },
        data: { breadcrumb: "Pharmacy Sales", pageTitle: "GMMR-QBO | Pharmacy" },
      });
      $stateProvider.state("app.professional-fee", {
        url: "/professional-fee",
        templateUrl: "src/template/professional-fee/list.tpl.php",
        controller: "professionalFeeCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/professional-fee/professional-fee.ctrl.js");
          },
        },
        data: { breadcrumb: "Professional Fee Sales", pageTitle: "GMMR-QBO | Professional Fee" },
      });

      // sales return
      $stateProvider.state("app.pharmacy-rn", {
        url: "/pharmacy-returns",
        templateUrl: "src/template/returns/pharmacy.tpl.php",
        controller: "returnsCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/returns/returns.ctrl.js");
          },
        },
        data: { breadcrumb: "Pharmacy Returns", pageTitle: "GMMR-QBO | Pharmacy Returns" },
      });
      $stateProvider.state("app.nonpharma-rn", {
        url: "/nonpharma-returns",
        templateUrl: "src/template/returns/nonpharma.tpl.php",
        controller: "returnsCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/returns/returns.ctrl.js");
          },
        },
        data: { breadcrumb: "NonPharma Returns", pageTitle: "GMMR-QBO | NonPharma Returns" },
      });

      // inventory
      $stateProvider.state("app.pharmacy-inventory", {
        url: "/pharmacy-inventory",
        templateUrl: "src/template/inventory/pharmacy.tpl.php",
        controller: "inventoryCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/inventory/inventory.ctrl.js");
          },
        },
        data: { breadcrumb: "Pharmacy Inventory", pageTitle: "GMMR-QBO | Inventory-Pharmacy" },
      });
      $stateProvider.state("app.nonpharma-inventory", {
        url: "/nonpharma-inventory",
        templateUrl: "src/template/inventory/nonpharma.tpl.php",
        controller: "inventoryCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/inventory/inventory.ctrl.js");
          },
        },
        data: { breadcrumb: "NonPharma Inventory", pageTitle: "GMMR-QBO | Inventory-NonPharma" },
      });
      $stateProvider.state("app.pharmacy-return-inventory", {
        url: "/pharmacy-return-inventory",
        templateUrl: "src/template/inventory/pharmacy-return.tpl.php",
        controller: "inventoryCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/inventory/inventory.ctrl.js");
          },
        },
        data: { breadcrumb: "Pharmacy Return Inventory", pageTitle: "GMMR-QBO | Inventory-Return-Pharmacy" },
      });
      $stateProvider.state("app.nonpharma-return-inventory", {
        url: "/nonpharma-return-inventory",
        templateUrl: "src/template/inventory/nonpharma-return.tpl.php",
        controller: "inventoryCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/inventory/inventory.ctrl.js");
          },
        },
        data: { breadcrumb: "NonPharma Inventory", pageTitle: "GMMR-QBO | Inventory Return-NonPharma" },
      });

      // credit & debit memo
      $stateProvider.state("app.credit-memo", {
        url: "/credit-memo",
        templateUrl: "src/template/credit-memo/list.tpl.php",
        controller: "creditMemoCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/credit-memo/credit-memo.ctrl.js");
          },
        },
        data: { breadcrumb: "Credit Memo", pageTitle: "GMMR-QBO | Credit Memo" },
      });
      $stateProvider.state("app.debit-memo", {
        url: "/debit-memo",
        templateUrl: "src/template/debit-memo/list.tpl.php",
        controller: "debitMemoCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/debit-memo/debit-memo.ctrl.js");
          },
        },
        data: { breadcrumb: "Debit Memo", pageTitle: "GMMR-QBO | Debit Memo" },
      });

      // payments
      $stateProvider.state("app.payments", {
        url: "/payments",
        templateUrl: "src/template/payments/inpatients.tpl.php",
        controller: "paymentsCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/payments/payments.ctrl.js");
          },
        },
        data: { breadcrumb: "InPatient Payments", pageTitle: "GMMR-QBO | InPatient Payments" },
      });
      $stateProvider.state("app.pos-payments", {
        url: "/walkin-payments",
        templateUrl: "src/template/payments/walkin.tpl.php",
        controller: "paymentsCtrl",
        resolve: {
          loadCtrl: function ($ocLazyLoad) {
            return $ocLazyLoad.load("src/template/payments/payments.ctrl.js");
          },
        },
        data: { breadcrumb: "Walkin Payments", pageTitle: "GMMR-QBO | Walkin Payments" },
      });

      // quikcbooks
      $stateProvider
        .state("app.quickbooks", {
          abstract: true,
          templateUrl: "src/template/quickbooks/layout.tpl.php",
          controller: "quickbooksCtrl",
          resolve: {
            loadCtrl: function ($ocLazyLoad) {
              return $ocLazyLoad.load("src/template/quickbooks/quickbooks.ctrl.js");
            },
          },
          data: { breadcrumb: "QuickBooks", pageTitle: "GMMR-QBO | QuickBooks" },
        })
        .state("app.quickbooks.items", {
          url: "/items",
          templateUrl: "src/template/quickbooks/items.tpl.php",
          data: { breadcrumb: "Items", pageTitle: "GMMR-QBO | QuickBooks - Items" },
        });

      // set to url to html5
      $locationProvider.html5Mode(true);
    },
  ])
  // .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //   // Configuration constants
  //   const IDLE_CONFIG = {
  //     WARNING_DURATION: 45000, // 45 seconds
  //     IDLE_MESSAGE:
  //       "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //     LOGOUT_TIMEOUT: 5000,
  //     DEFAULT_TITLE: "GMMR-QBO",
  //   };

  //   // Debug logging helper
  //   const DEBUG_IDLE = false;
  //   function logIdle(message, ...args) {
  //     if (DEBUG_IDLE) {
  //       console.log(`[IDLE] ${message}`, ...args);
  //     }
  //   }

  //   // 🧱 Set page title on route change
  //   $transitions.onSuccess({}, function (trans) {
  //     const title = trans.to().data && trans.to().data.pageTitle;
  //     document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //   });

  //   // Route Guard: Protect all except login
  //   $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //     if (!AuthService.isLoggedIn()) {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //       return $state.target("login");
  //     }
  //     return AuthService.verify()
  //       .then(() => {
  //         Idle.watch();
  //         Keepalive.start();
  //         return true;
  //       })
  //       .catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       });
  //   });

  //   // Prevent logged-in users from accessing login page
  //   $transitions.onBefore({ to: "login" }, function () {
  //     if (AuthService.isLoggedIn()) {
  //       return AuthService.verify()
  //         .then(() => $state.target("app.home"))
  //         .catch(() => true);
  //     }
  //     return true;
  //   });

  //   // ng-idle event listeners
  //   let idleWarningAlert = null;
  //   let idleTimeoutTriggered = false;

  //   $rootScope.$on("IdleStart", function () {
  //     logIdle("IdleStart triggered");

  //     // Prevent multiple alerts
  //     if (idleWarningAlert) {
  //       console.warn("⚠️ Idle alert already showing");
  //       return;
  //     }

  //     Keepalive.stop();
  //     console.warn("⏳ User is idle");
  //     idleTimeoutTriggered = false;

  //     idleWarningAlert = SweetAlert2.fire({
  //       title: "Inactive Warning",
  //       text: IDLE_CONFIG.IDLE_MESSAGE,
  //       icon: "warning",
  //       showConfirmButton: true,
  //       showCancelButton: true,
  //       confirmButtonText: "Continue",
  //       confirmButtonColor: "#02AA53",
  //       cancelButtonText: "Logout",
  //       timer: IDLE_CONFIG.WARNING_DURATION,
  //       timerProgressBar: true,
  //       allowOutsideClick: false,
  //       allowEscapeKey: false,
  //       willClose: () => {
  //         logIdle("Alert willClose callback");
  //         idleWarningAlert = null;
  //       },
  //     }).then((result) => {
  //       logIdle("Alert resolved", result);

  //       // If alert was closed by IdleEnd or IdleTimeout, ignore
  //       if (!idleWarningAlert && !result.isConfirmed) {
  //         logIdle("Alert already handled by another event");
  //         return;
  //       }

  //       // Timeout event already handled logout
  //       if (idleTimeoutTriggered) {
  //         logIdle("Timeout already triggered, ignoring");
  //         return;
  //       }

  //       if (result.isConfirmed) {
  //         logIdle("User chose to continue");
  //         // User chose to continue - reset idle watcher
  //         Idle.watch();
  //         Keepalive.start();
  //       } else {
  //         // User chose to logout, dismissed, or timer expired
  //         logIdle("User chose to logout or dismissed");
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         AuthService.logout()
  //           .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //           .catch((err) => {
  //             console.error("Logout failed or timed out:", err);
  //           })
  //           .finally(() => {
  //             $state.go("login");
  //           });
  //       }
  //     });
  //   });

  //   $rootScope.$on("IdleEnd", function () {
  //     console.log("🟢 User active again");
  //     logIdle("IdleEnd triggered");

  //     if (idleWarningAlert) {
  //       // Store reference and clear immediately to prevent race conditions
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     }

  //     // Restart keepalive when user becomes active again
  //     if (AuthService.isLoggedIn()) {
  //       Keepalive.start();
  //     }
  //   });

  //   $rootScope.$on("IdleTimeout", function () {
  //     console.warn("⛔ Idle timeout reached — logging out");
  //     logIdle("IdleTimeout triggered");

  //     idleTimeoutTriggered = true;

  //     // Close warning if still present
  //     if (idleWarningAlert) {
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     }

  //     Idle.unwatch();
  //     Keepalive.stop();

  //     AuthService.logout()
  //       .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //       .catch((err) => {
  //         console.error("Logout failed or timed out:", err);
  //       })
  //       .finally(() => {
  //         $state.go("login");
  //       });
  //   });

  //   // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //   // is configured here — on each Keepalive event, the session is verified.
  //   $rootScope.$on("Keepalive", () => {
  //     logIdle("Keepalive ping - verifying session");

  //     AuthService.verify().catch((error) => {
  //       console.error("❌ Session verification failed:", error);
  //       Idle.unwatch();
  //       Keepalive.stop();

  //       // Show session expired notification
  //       SweetAlert2.fire({
  //         title: "Session Expired",
  //         text: "Your session has expired. Please log in again.",
  //         icon: "info",
  //         confirmButtonText: "OK",
  //         confirmButtonColor: "#02AA53",
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //       }).then(() => {
  //         $state.go("login");
  //       });
  //     });
  //   });

  //   // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //   if (AuthService.isLoggedIn()) {
  //     logIdle("App bootstrap - user is logged in, verifying session");
  //     AuthService.verify()
  //       .then(() => {
  //         logIdle("Session verified - starting idle watch and keepalive");
  //         Idle.watch();
  //         Keepalive.start();
  //       })
  //       .catch((error) => {
  //         console.error("Initial session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();
  //       });
  //   } else {
  //     logIdle("App bootstrap - user not logged in");
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   }

  //   // Cleanup on scope destruction to prevent memory leaks
  //   $rootScope.$on("$destroy", function () {
  //     logIdle("Root scope destroying - cleanup");
  //     if (idleWarningAlert) {
  //       idleWarningAlert.close();
  //       idleWarningAlert = null;
  //     }
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   });
  // })
  // .run(function (
  //   $transitions,
  //   $state,
  //   AuthService,
  //   Idle,
  //   Keepalive, // still injected, but revert to normal keepalive interval behavior
  //   $rootScope,
  //   SweetAlert2
  // ) {
  //   // 🧱 Set page title on route change
  //   $transitions.onSuccess({}, function (trans) {
  //     const title = trans.to().data && trans.to().data.pageTitle;
  //     document.title = title || "GMMR-QBO";
  //   });

  //   // Route Guard: Protect all except login
  //   $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //     if (!AuthService.isLoggedIn()) {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //       return $state.target("login");
  //     }
  //     return AuthService.verify()
  //       .then(() => {
  //         Idle.watch();
  //         Keepalive.start();
  //         return true;
  //       })
  //       .catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       });
  //   });

  //   // Prevent logged-in users from accessing login page
  //   $transitions.onBefore({ to: "login" }, function () {
  //     if (AuthService.isLoggedIn()) {
  //       return AuthService.verify()
  //         .then(() => $state.target("app.home"))
  //         .catch(() => true);
  //     }
  //     return true;
  //   });

  //   // ng-idle event listeners
  //   let idleWarningAlert = null;
  //   let idleTimeoutTriggered = false;

  //   $rootScope.$on("IdleStart", function () {
  //     Keepalive.stop();
  //     console.warn("⏳ User is idle");
  //     idleTimeoutTriggered = false;
  //     idleWarningAlert = SweetAlert2.fire({
  //       title: "Inactive Warning",
  //       text: "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //       icon: "warning",
  //       showConfirmButton: true,
  //       showCancelButton: true,
  //       confirmButtonText: "Continue",
  //       confirmButtonColor: "#02AA53",
  //       cancelButtonText: "Logout",
  //       timer: 45000,
  //       timerProgressBar: true,
  //       allowOutsideClick: false,
  //       allowEscapeKey: false,
  //       didOpen: () => {
  //         // Optionally focus the alert or add a timer description
  //       },
  //       willClose: () => {
  //         idleWarningAlert = null;
  //       },
  //     }).then((result) => {
  //       if (idleTimeoutTriggered) return; // Timeout event already handled logout
  //       if (result.isConfirmed) {
  //         // User chose to continue. Reset idle watcher
  //         Idle.watch();
  //       } else if (result.isDismissed || result.isDenied || result.isCanceled) {
  //         // User chose to logout or dismissed
  //         Idle.unwatch();
  //         AuthService.logout().finally(() => {
  //           $state.go("login");
  //         });
  //       }
  //     });
  //   });

  //   $rootScope.$on("IdleEnd", function () {
  //     console.log("🟢 User active again");
  //     // Close SweetAlert2 warning if still open
  //     if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
  //       idleWarningAlert.close();
  //       idleWarningAlert = null;
  //     } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //       window.Swal.close(); // fallback in case
  //     }
  //   });

  //   $rootScope.$on("IdleTimeout", function () {
  //     console.warn("⛔ Idle timeout reached — logging out");
  //     idleTimeoutTriggered = true;
  //     // Close warning if still present
  //     if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
  //       idleWarningAlert.close();
  //       idleWarningAlert = null;
  //     } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //       window.Swal.close(); // fallback in case
  //     }
  //     Idle.unwatch();
  //     AuthService.logout().finally(() => {
  //       $state.go("login");
  //     });
  //   });

  //   // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //   // is configured here — on each Keepalive event, the session is verified.
  //   $rootScope.$on("Keepalive", () => {
  //     AuthService.verify().catch(() => {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //       $state.go("login");
  //     });
  //   });

  //   // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //   if (AuthService.isLoggedIn()) {
  //     AuthService.verify()
  //       .then(() => {
  //         Idle.watch();
  //         Keepalive.start();
  //       })
  //       .catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //       });
  //   } else {
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   }
  // })
  // .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //   // Configuration constants
  //   const IDLE_CONFIG = {
  //     WARNING_DURATION: 45000, // 45 seconds
  //     IDLE_MESSAGE:
  //       "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //     LOGOUT_TIMEOUT: 5000,
  //     DEFAULT_TITLE: "GMMR-QBO",
  //     VERIFY_DEBOUNCE: 5000, // 5 seconds - prevent duplicate verify calls
  //   };

  //   // Debug logging helper
  //   const DEBUG_IDLE = false;
  //   function logIdle(message, ...args) {
  //     if (DEBUG_IDLE) {
  //       console.log(`[IDLE] ${message}`, ...args);
  //     }
  //   }

  //   // Debounced verification to prevent duplicate calls
  //   let lastVerifyTime = 0;
  //   let lastVerifyPromise = null;

  //   function debouncedVerify() {
  //     const now = Date.now();

  //     // If we verified recently, reuse the same promise
  //     if (now - lastVerifyTime < IDLE_CONFIG.VERIFY_DEBOUNCE && lastVerifyPromise) {
  //       logIdle("Reusing recent verify promise (debounced)");
  //       return lastVerifyPromise;
  //     }

  //     logIdle("Making new verify call");
  //     lastVerifyTime = now;
  //     lastVerifyPromise = AuthService.verify()
  //       .then((result) => {
  //         logIdle("Session verified successfully");
  //         return result;
  //       })
  //       .catch((error) => {
  //         logIdle("Session verification failed");
  //         lastVerifyPromise = null; // Clear on error so next call tries again
  //         throw error;
  //       });

  //     return lastVerifyPromise;
  //   }

  //   // 🧱 Set page title on route change
  //   $transitions.onSuccess({}, function (trans) {
  //     const title = trans.to().data && trans.to().data.pageTitle;
  //     document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //   });

  //   // Route Guard: Protect all except login
  //   $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //     if (!AuthService.isLoggedIn()) {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //       return $state.target("login");
  //     }
  //     return debouncedVerify()
  //       .then(() => {
  //         Idle.watch();
  //         Keepalive.start();
  //         return true;
  //       })
  //       .catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       });
  //   });

  //   // Prevent logged-in users from accessing login page
  //   $transitions.onBefore({ to: "login" }, function () {
  //     if (AuthService.isLoggedIn()) {
  //       return debouncedVerify()
  //         .then(() => $state.target("app.home"))
  //         .catch(() => true);
  //     }
  //     return true;
  //   });

  //   // ng-idle event listeners
  //   let idleWarningAlert = null;
  //   let idleTimeoutTriggered = false;

  //   $rootScope.$on("IdleStart", function () {
  //     logIdle("IdleStart triggered");

  //     // Prevent multiple alerts
  //     if (idleWarningAlert) {
  //       console.warn("⚠️ Idle alert already showing");
  //       return;
  //     }

  //     Keepalive.stop();
  //     console.warn("⏳ User is idle");
  //     idleTimeoutTriggered = false;

  //     idleWarningAlert = SweetAlert2.fire({
  //       title: "Inactive Warning",
  //       text: IDLE_CONFIG.IDLE_MESSAGE,
  //       icon: "warning",
  //       showConfirmButton: true,
  //       showCancelButton: true,
  //       confirmButtonText: "Continue",
  //       confirmButtonColor: "#02AA53",
  //       cancelButtonText: "Logout",
  //       timer: IDLE_CONFIG.WARNING_DURATION,
  //       timerProgressBar: true,
  //       allowOutsideClick: false,
  //       allowEscapeKey: false,
  //       willClose: () => {
  //         logIdle("Alert willClose callback");
  //         idleWarningAlert = null;
  //       },
  //     }).then((result) => {
  //       logIdle("Alert resolved", result);

  //       // If alert was closed by IdleEnd or IdleTimeout, ignore
  //       if (!idleWarningAlert && !result.isConfirmed) {
  //         logIdle("Alert already handled by another event");
  //         return;
  //       }

  //       // Timeout event already handled logout
  //       if (idleTimeoutTriggered) {
  //         logIdle("Timeout already triggered, ignoring");
  //         return;
  //       }

  //       if (result.isConfirmed) {
  //         logIdle("User chose to continue");
  //         // User chose to continue - reset idle watcher
  //         Idle.watch();
  //         Keepalive.start();
  //       } else {
  //         // User chose to logout, dismissed, or timer expired
  //         logIdle("User chose to logout or dismissed");
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         AuthService.logout()
  //           .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //           .catch((err) => {
  //             console.error("Logout failed or timed out:", err);
  //           })
  //           .finally(() => {
  //             $state.go("login");
  //           });
  //       }
  //     });
  //   });

  //   $rootScope.$on("IdleEnd", function () {
  //     console.log("🟢 User active again");
  //     logIdle("IdleEnd triggered");

  //     if (idleWarningAlert) {
  //       // Store reference and clear immediately to prevent race conditions
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     }

  //     // Restart keepalive when user becomes active again
  //     if (AuthService.isLoggedIn()) {
  //       Keepalive.start();
  //     }
  //   });

  //   $rootScope.$on("IdleTimeout", function () {
  //     console.warn("⛔ Idle timeout reached — logging out");
  //     logIdle("IdleTimeout triggered");

  //     idleTimeoutTriggered = true;

  //     // Close warning if still present
  //     if (idleWarningAlert) {
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     }

  //     Idle.unwatch();
  //     Keepalive.stop();

  //     AuthService.logout()
  //       .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //       .catch((err) => {
  //         console.error("Logout failed or timed out:", err);
  //       })
  //       .finally(() => {
  //         $state.go("login");
  //       });
  //   });

  //   // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //   // is configured here — on each Keepalive event, the session is verified.
  //   $rootScope.$on("Keepalive", () => {
  //     logIdle("Keepalive ping - verifying session");

  //     debouncedVerify().catch((error) => {
  //       console.error("❌ Session verification failed:", error);
  //       Idle.unwatch();
  //       Keepalive.stop();

  //       // Show session expired notification
  //       SweetAlert2.fire({
  //         title: "Session Expired",
  //         text: "Your session has expired. Please log in again.",
  //         icon: "info",
  //         confirmButtonText: "OK",
  //         confirmButtonColor: "#02AA53",
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //       }).then(() => {
  //         $state.go("login");
  //       });
  //     });
  //   });

  //   // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //   if (AuthService.isLoggedIn()) {
  //     logIdle("App bootstrap - user is logged in, verifying session");
  //     debouncedVerify()
  //       .then(() => {
  //         logIdle("Session verified - starting idle watch and keepalive");
  //         Idle.watch();
  //         Keepalive.start();
  //       })
  //       .catch((error) => {
  //         console.error("Initial session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();
  //       });
  //   } else {
  //     logIdle("App bootstrap - user not logged in");
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   }

  //   // Cleanup on scope destruction to prevent memory leaks
  //   $rootScope.$on("$destroy", function () {
  //     logIdle("Root scope destroying - cleanup");
  //     if (idleWarningAlert) {
  //       idleWarningAlert.close();
  //       idleWarningAlert = null;
  //     }
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   });
  // })
  // .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //   // Configuration constants
  //   const IDLE_CONFIG = {
  //     WARNING_DURATION: 45000, // 45 seconds
  //     IDLE_MESSAGE:
  //       "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //     LOGOUT_TIMEOUT: 5000,
  //     DEFAULT_TITLE: "GMMR-QBO",
  //     VERIFY_DEBOUNCE: 5000, // 5 seconds - prevent duplicate verify calls
  //   };

  //   // Debug logging helper
  //   const DEBUG_IDLE = false;
  //   function logIdle(message, ...args) {
  //     if (DEBUG_IDLE) {
  //       console.log(`[IDLE] ${message}`, ...args);
  //     }
  //   }

  //   // Debounced verification to prevent duplicate calls
  //   let lastVerifyTime = 0;
  //   let lastVerifyPromise = null;

  //   function debouncedVerify() {
  //     const now = Date.now();

  //     // If we verified recently, reuse the same promise
  //     if (now - lastVerifyTime < IDLE_CONFIG.VERIFY_DEBOUNCE && lastVerifyPromise) {
  //       logIdle("Reusing recent verify promise (debounced)");
  //       return lastVerifyPromise;
  //     }

  //     logIdle("Making new verify call");
  //     lastVerifyTime = now;
  //     lastVerifyPromise = AuthService.verify()
  //       .then((result) => {
  //         logIdle("Session verified successfully");
  //         return result;
  //       })
  //       .catch((error) => {
  //         logIdle("Session verification failed");
  //         lastVerifyPromise = null; // Clear on error so next call tries again
  //         throw error;
  //       });

  //     return lastVerifyPromise;
  //   }

  //   // 🧱 Set page title on route change
  //   $transitions.onSuccess({}, function (trans) {
  //     const title = trans.to().data && trans.to().data.pageTitle;
  //     document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //   });

  //   // Route Guard: Protect all except login
  //   $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //     if (!AuthService.isLoggedIn()) {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //       return $state.target("login");
  //     }
  //     return debouncedVerify()
  //       .then(() => {
  //         Idle.watch();
  //         Keepalive.start();
  //         return true;
  //       })
  //       .catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       });
  //   });

  //   // Prevent logged-in users from accessing login page
  //   $transitions.onBefore({ to: "login" }, function () {
  //     if (AuthService.isLoggedIn()) {
  //       return debouncedVerify()
  //         .then(() => $state.target("app.home"))
  //         .catch(() => true);
  //     }
  //     return true;
  //   });

  //   // ng-idle event listeners
  //   let idleWarningAlert = null;
  //   let idleTimeoutTriggered = false;

  //   $rootScope.$on("IdleStart", function () {
  //     logIdle("IdleStart triggered");

  //     // Prevent multiple alerts
  //     if (idleWarningAlert) {
  //       console.warn("⚠️ Idle alert already showing");
  //       return;
  //     }

  //     Keepalive.stop();
  //     console.warn("⏳ User is idle");
  //     idleTimeoutTriggered = false;

  //     idleWarningAlert = SweetAlert2.fire({
  //       title: "Inactive Warning",
  //       text: IDLE_CONFIG.IDLE_MESSAGE,
  //       icon: "warning",
  //       showConfirmButton: true,
  //       showCancelButton: true,
  //       confirmButtonText: "Continue",
  //       confirmButtonColor: "#02AA53",
  //       cancelButtonText: "Logout",
  //       timer: IDLE_CONFIG.WARNING_DURATION,
  //       timerProgressBar: true,
  //       allowOutsideClick: false,
  //       allowEscapeKey: false,
  //       didOpen: () => {
  //         // Optionally focus the alert or add a timer description
  //       },
  //       willClose: () => {
  //         logIdle("Alert willClose callback");
  //         idleWarningAlert = null;
  //       },
  //     }).then((result) => {
  //       logIdle("Alert resolved", result);

  //       // If alert was closed by IdleEnd or IdleTimeout, ignore
  //       if (!idleWarningAlert && !result.isConfirmed) {
  //         logIdle("Alert already handled by another event");
  //         return;
  //       }

  //       // Timeout event already handled logout
  //       if (idleTimeoutTriggered) {
  //         logIdle("Timeout already triggered, ignoring");
  //         return;
  //       }

  //       if (result.isConfirmed) {
  //         logIdle("User chose to continue");
  //         // User chose to continue - reset idle watcher and restart keepalive
  //         Idle.watch();
  //         Keepalive.start();
  //       } else if (result.isDismissed || result.isDenied || result.dismiss === "cancel") {
  //         // User chose to logout or dismissed
  //         logIdle("User chose to logout or dismissed");
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         AuthService.logout()
  //           .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //           .catch((err) => {
  //             console.error("Logout failed or timed out:", err);
  //           })
  //           .finally(() => {
  //             $state.go("login");
  //           });
  //       }
  //     });
  //   });

  //   $rootScope.$on("IdleEnd", function () {
  //     console.log("🟢 User active again");
  //     logIdle("IdleEnd triggered");

  //     // Close SweetAlert2 warning if still open
  //     if (idleWarningAlert) {
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //       window.Swal.close(); // fallback in case
  //     }

  //     // Restart keepalive when user becomes active again
  //     if (AuthService.isLoggedIn()) {
  //       Keepalive.start();
  //     }
  //   });

  //   $rootScope.$on("IdleTimeout", function () {
  //     console.warn("⛔ Idle timeout reached — logging out");
  //     logIdle("IdleTimeout triggered");

  //     idleTimeoutTriggered = true;

  //     // Close warning if still present
  //     if (idleWarningAlert) {
  //       const alertToClose = idleWarningAlert;
  //       idleWarningAlert = null;

  //       if (typeof alertToClose.close === "function") {
  //         alertToClose.close();
  //       }
  //     } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //       window.Swal.close(); // fallback in case
  //     }

  //     Idle.unwatch();
  //     Keepalive.stop();

  //     AuthService.logout()
  //       .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //       .catch((err) => {
  //         console.error("Logout failed or timed out:", err);
  //       })
  //       .finally(() => {
  //         $state.go("login");
  //       });
  //   });

  //   // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //   // is configured here — on each Keepalive event, the session is verified.
  //   $rootScope.$on("Keepalive", () => {
  //     logIdle("Keepalive ping - verifying session");

  //     debouncedVerify().catch((error) => {
  //       console.error("❌ Session verification failed:", error);
  //       Idle.unwatch();
  //       Keepalive.stop();

  //       // Show session expired notification
  //       SweetAlert2.fire({
  //         title: "Session Expired",
  //         text: "Your session has expired. Please log in again.",
  //         icon: "info",
  //         confirmButtonText: "OK",
  //         confirmButtonColor: "#02AA53",
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //       }).then(() => {
  //         $state.go("login");
  //       });
  //     });
  //   });

  //   // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //   if (AuthService.isLoggedIn()) {
  //     logIdle("App bootstrap - user is logged in, verifying session");
  //     debouncedVerify()
  //       .then(() => {
  //         logIdle("Session verified - starting idle watch and keepalive");
  //         Idle.watch();
  //         Keepalive.start();
  //       })
  //       .catch((error) => {
  //         console.error("Initial session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();
  //       });
  //   } else {
  //     logIdle("App bootstrap - user not logged in");
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   }

  //   // Cleanup on scope destruction to prevent memory leaks
  //   $rootScope.$on("$destroy", function () {
  //     logIdle("Root scope destroying - cleanup");
  //     if (idleWarningAlert) {
  //       idleWarningAlert.close();
  //       idleWarningAlert = null;
  //     }
  //     Idle.unwatch();
  //     Keepalive.stop();
  //   });
  // })
  .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
    // Configuration constants
    const IDLE_CONFIG = {
      WARNING_DURATION: 45000, // 45 seconds
      IDLE_MESSAGE:
        "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
      SESSION_ISSUE_MESSAGE:
        "We're having trouble verifying your session. You will be logged out in 45 seconds unless you choose to continue.",
      LOGOUT_TIMEOUT: 5000,
      DEFAULT_TITLE: "GMMR-QBO",
      VERIFY_DEBOUNCE: 5000, // 5 seconds
      MAX_VERIFY_FAILS: 3, // Allow 3 consecutive failures before showing alert
    };

    // Debug logging helper
    const DEBUG_IDLE = false;
    function logIdle(message, ...args) {
      if (DEBUG_IDLE) {
        console.log(`[IDLE] ${message}`, ...args);
      }
    }

    // Debounced verification to prevent duplicate calls
    let lastVerifyTime = 0;
    let lastVerifyPromise = null;
    let verifyFailCount = 0;

    function debouncedVerify() {
      const now = Date.now();

      if (now - lastVerifyTime < IDLE_CONFIG.VERIFY_DEBOUNCE && lastVerifyPromise) {
        logIdle("Reusing recent verify promise (debounced)");
        return lastVerifyPromise;
      }

      logIdle("Making new verify call");
      lastVerifyTime = now;
      lastVerifyPromise = AuthService.verify()
        .then((result) => {
          logIdle("Session verified successfully");
          verifyFailCount = 0; // Reset fail count on success
          return result;
        })
        .catch((error) => {
          logIdle("Session verification failed");
          lastVerifyPromise = null;
          throw error;
        });

      return lastVerifyPromise;
    }

    // 🧱 Set page title on route change
    $transitions.onSuccess({}, function (trans) {
      const title = trans.to().data && trans.to().data.pageTitle;
      document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
    });

    // Route Guard: Protect all except login
    $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
      if (!AuthService.isLoggedIn()) {
        Idle.unwatch();
        Keepalive.stop();
        return $state.target("login");
      }
      return debouncedVerify()
        .then(() => {
          Idle.watch();
          Keepalive.start();
          return true;
        })
        .catch(() => {
          Idle.unwatch();
          Keepalive.stop();
          return $state.target("login");
        });
    });

    // Prevent logged-in users from accessing login page
    $transitions.onBefore({ to: "login" }, function () {
      if (AuthService.isLoggedIn()) {
        return debouncedVerify()
          .then(() => $state.target("app.home"))
          .catch(() => true);
      }
      return true;
    });

    // ng-idle event listeners
    let idleWarningAlert = null;
    let sessionIssueAlert = null;
    let idleTimeoutTriggered = false;

    $rootScope.$on("IdleStart", function () {
      logIdle("IdleStart triggered");

      // Don't show idle warning if there's already a session issue alert
      if (idleWarningAlert || sessionIssueAlert) {
        console.warn("⚠️ Alert already showing");
        return;
      }

      Keepalive.stop();
      console.warn("⏳ User is idle");
      idleTimeoutTriggered = false;

      idleWarningAlert = SweetAlert2.fire({
        title: "Inactive Warning",
        text: IDLE_CONFIG.IDLE_MESSAGE,
        icon: "warning",
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: "Continue",
        confirmButtonColor: "#02AA53",
        cancelButtonText: "Logout",
        timer: IDLE_CONFIG.WARNING_DURATION,
        timerProgressBar: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        willClose: () => {
          logIdle("Idle alert willClose callback");
          idleWarningAlert = null;
        },
      }).then((result) => {
        logIdle("Idle alert resolved", result);

        if (idleTimeoutTriggered) {
          logIdle("Timeout already triggered, ignoring");
          return;
        }

        if (result.isConfirmed) {
          logIdle("User chose to continue");

          // Verify session before continuing
          debouncedVerify()
            .then(() => {
              logIdle("Session verified, restarting idle watch");
              Idle.watch();
              Keepalive.start();
            })
            .catch((error) => {
              console.error("Session verification failed after continue:", error);

              // Show session issue alert
              SweetAlert2.fire({
                title: "Session Issue",
                text: "We couldn't verify your session. Please log in again.",
                icon: "error",
                confirmButtonText: "OK",
                confirmButtonColor: "#02AA53",
                allowOutsideClick: false,
                allowEscapeKey: false,
              }).then(() => {
                AuthService.logout()
                  .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                  .catch((err) => console.error("Logout failed:", err))
                  .finally(() => $state.go("login"));
              });
            });
        } else if (result.dismiss === "timer") {
          // Timer expired - auto logout
          logIdle("Idle timer expired - auto logout");
          Idle.unwatch();
          Keepalive.stop();
          AuthService.logout()
            .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
            .catch((err) => console.error("Logout failed or timed out:", err))
            .finally(() => $state.go("login"));
        } else {
          // User chose to logout
          logIdle("User chose to logout");
          Idle.unwatch();
          Keepalive.stop();
          AuthService.logout()
            .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
            .catch((err) => console.error("Logout failed or timed out:", err))
            .finally(() => $state.go("login"));
        }
      });
    });

    $rootScope.$on("IdleEnd", function () {
      console.log("🟢 User active again");
      logIdle("IdleEnd triggered");

      if (idleWarningAlert) {
        const alertToClose = idleWarningAlert;
        idleWarningAlert = null;

        if (typeof alertToClose.close === "function") {
          alertToClose.close();
        }
      } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
        window.Swal.close();
      }

      // ✅ RESTART KEEPALIVE when user becomes active
      if (AuthService.isLoggedIn()) {
        Keepalive.start();
      }
    });

    $rootScope.$on("IdleTimeout", function () {
      console.warn("⛔ Idle timeout reached — logging out");
      logIdle("IdleTimeout triggered");

      idleTimeoutTriggered = true;

      if (idleWarningAlert) {
        const alertToClose = idleWarningAlert;
        idleWarningAlert = null;

        if (typeof alertToClose.close === "function") {
          alertToClose.close();
        }
      } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
        window.Swal.close();
      }

      Idle.unwatch();
      Keepalive.stop();

      AuthService.logout()
        .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
        .catch((err) => console.error("Logout failed or timed out:", err))
        .finally(() => $state.go("login"));
    });

    // ✅ KEEPALIVE: Silently verify, only alert if session truly expired or multiple failures
    $rootScope.$on("Keepalive", () => {
      logIdle("Keepalive ping - verifying session");

      debouncedVerify().catch((error) => {
        const isSessionExpired = error.status === 401 || (error.data && error.data.status === 401);

        if (isSessionExpired) {
          // Immediate logout on 401 - session definitely expired
          console.error("❌ Session expired (401)");
          verifyFailCount = 0;
          Idle.unwatch();
          Keepalive.stop();

          // Don't show alert if idle warning is already showing
          if (!idleWarningAlert && !sessionIssueAlert) {
            sessionIssueAlert = SweetAlert2.fire({
              title: "Session Expired",
              text: "Your session has expired. You will be logged out in 45 seconds unless you choose to continue.",
              icon: "warning",
              showConfirmButton: true,
              showCancelButton: true,
              confirmButtonText: "Continue",
              confirmButtonColor: "#02AA53",
              cancelButtonText: "Logout",
              timer: IDLE_CONFIG.WARNING_DURATION,
              timerProgressBar: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
              willClose: () => {
                logIdle("Session issue alert willClose callback");
                sessionIssueAlert = null;
              },
            }).then((result) => {
              if (result.isConfirmed) {
                logIdle("User chose to continue after session expiry");

                // Try to re-verify/re-login
                AuthService.verify()
                  .then(() => {
                    logIdle("Session re-verified successfully");
                    verifyFailCount = 0;
                    Idle.watch();
                    Keepalive.start();

                    SweetAlert2.fire({
                      title: "Session Restored",
                      text: "Your session has been restored successfully.",
                      icon: "success",
                      timer: 2000,
                      showConfirmButton: false,
                    });
                  })
                  .catch((verifyError) => {
                    console.error("Re-verification failed:", verifyError);

                    SweetAlert2.fire({
                      title: "Unable to Restore Session",
                      text: "We couldn't restore your session. Please log in again.",
                      icon: "error",
                      confirmButtonText: "OK",
                      confirmButtonColor: "#02AA53",
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                    }).then(() => {
                      AuthService.logout()
                        .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                        .catch((err) => console.error("Logout failed:", err))
                        .finally(() => $state.go("login"));
                    });
                  });
              } else if (result.dismiss === "timer") {
                // Timer expired - auto logout
                logIdle("Session issue timer expired - auto logout");
                AuthService.logout()
                  .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                  .catch((err) => console.error("Logout failed:", err))
                  .finally(() => $state.go("login"));
              } else {
                // User chose to logout
                logIdle("User chose to logout after session expiry");
                AuthService.logout()
                  .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                  .catch((err) => console.error("Logout failed:", err))
                  .finally(() => $state.go("login"));
              }
            });
          } else {
            // Alert already showing, just logout
            AuthService.logout()
              .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
              .catch((err) => console.error("Logout failed:", err))
              .finally(() => $state.go("login"));
          }
        } else {
          // Network error or other non-401 issue - increment fail count
          verifyFailCount++;
          console.warn(`⚠️ Session verification failed (${verifyFailCount}/${IDLE_CONFIG.MAX_VERIFY_FAILS}):`, error);

          // Only show alert after multiple consecutive failures
          if (verifyFailCount >= IDLE_CONFIG.MAX_VERIFY_FAILS) {
            console.error(`❌ Session verification failed ${IDLE_CONFIG.MAX_VERIFY_FAILS} times`);
            verifyFailCount = 0;
            Idle.unwatch();
            Keepalive.stop();

            // Don't show alert if idle warning is already showing
            if (!idleWarningAlert && !sessionIssueAlert) {
              sessionIssueAlert = SweetAlert2.fire({
                title: "Session Issue Detected",
                text: IDLE_CONFIG.SESSION_ISSUE_MESSAGE,
                icon: "warning",
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: "Continue",
                confirmButtonColor: "#02AA53",
                cancelButtonText: "Logout",
                timer: IDLE_CONFIG.WARNING_DURATION,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                willClose: () => {
                  sessionIssueAlert = null;
                },
              }).then((result) => {
                if (result.isConfirmed) {
                  logIdle("User chose to continue after session issues");

                  AuthService.verify()
                    .then(() => {
                      logIdle("Session verified successfully after retry");
                      verifyFailCount = 0;
                      Idle.watch();
                      Keepalive.start();

                      // SweetAlert2.fire({
                      //   title: "Connection Restored",
                      //   text: "Your session has been verified successfully.",
                      //   icon: "success",
                      //   timer: 2000,
                      //   showConfirmButton: false,
                      // });
                    })
                    .catch((verifyError) => {
                      console.error("Verification still failing:", verifyError);

                      SweetAlert2.fire({
                        title: "Unable to Verify Session",
                        text: "We couldn't verify your session. Please log in again.",
                        icon: "error",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#02AA53",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                      }).then(() => {
                        AuthService.logout()
                          .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                          .catch((err) => console.error("Logout failed:", err))
                          .finally(() => $state.go("login"));
                      });
                    });
                } else if (result.dismiss === "timer") {
                  logIdle("Session issue timer expired - auto logout");
                  AuthService.logout()
                    .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                    .catch((err) => console.error("Logout failed:", err))
                    .finally(() => $state.go("login"));
                } else {
                  logIdle("User chose to logout after session issues");
                  AuthService.logout()
                    .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                    .catch((err) => console.error("Logout failed:", err))
                    .finally(() => $state.go("login"));
                }
              });
            } else {
              // Alert already showing, just logout
              AuthService.logout()
                .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
                .catch((err) => console.error("Logout failed:", err))
                .finally(() => $state.go("login"));
            }
          }
        }
      });
    });

    // On app bootstrap
    if (AuthService.isLoggedIn()) {
      logIdle("App bootstrap - user is logged in, verifying session");
      debouncedVerify()
        .then(() => {
          logIdle("Session verified - starting idle watch and keepalive");
          Idle.watch();
          Keepalive.start();
        })
        .catch((error) => {
          console.error("Initial session verification failed:", error);
          Idle.unwatch();
          Keepalive.stop();
        });
    } else {
      logIdle("App bootstrap - user not logged in");
      Idle.unwatch();
      Keepalive.stop();
    }

    // Cleanup
    $rootScope.$on("$destroy", function () {
      logIdle("Root scope destroying - cleanup");
      if (idleWarningAlert) {
        idleWarningAlert.close();
        idleWarningAlert = null;
      }
      if (sessionIssueAlert) {
        sessionIssueAlert.close();
        sessionIssueAlert = null;
      }
      Idle.unwatch();
      Keepalive.stop();
    });
  })
  .controller("ctrl", function ($scope, $state, $http, AuthService, SweetAlert2, $uibModal) {
    let sc = $scope;
    let vs = $state;
    sc.isReportsState = vs.includes("reports");
    sc.showMenu = sc.isReportsState ? true : false;
    sc.generating = false;

    sc.decrypted = AuthService.getUser();
    //    console.log(sc.decrypted);
    sc.userInfo = {
      id: sc.decrypted.id,
      alias: sc.decrypted.short_name,
      name: sc.decrypted.name,
      typeid: sc.decrypted.user_type_id,
      type:
        sc.decrypted.user_type == "Admin" ||
          sc.decrypted.user_type == "ADMIN" ||
          sc.decrypted.user_type == "Administrator"
          ? "ADMINISTRATOR"
          : sc.decrypted.user_type,
      img: sc.decrypted.profile,
    };

    sc.statusText = "Idling";
    sc.refreshToken = "";
    sc.accessToken = "";
    sc.sysdoorkey = [];

    // $sysdoor
    //   .access(sc.userInfo.id)
    //   .then(function (cb) {
    //     sc.sysdoorkey = cb;
    //   })
    //   .catch(function error(err) {
    //     console.error(err);
    //   });

    sc.handleLogout = function () {
      SweetAlert2.fire({
        title: "Continue to logout",
        text: `Your about to logout from the system!`,
        icon: "question",
        allowOutsideClick: true,
        showCancelButton: true,
        confirmButtonColor: "#02AA53",
        cancelButtonColor: "#E4E4E4",
        cancelButtonClass: "text-dark",
        confirmButtonText: "Logout",
        position: "top",
      }).then((result) => {
        if (result.value) {
          AuthService.logout();
        }
      });
    };

    sc.handleToggleMenu = function () {
      let sidebar = document.getElementById("sidebar");
      let content = document.getElementById("content");
      sidebar.classList.toggle("sidebar-collapse");
      content.classList.toggle("content-collapse");
    };
    sc.handleToogleSubMenu = function () {
      sc.showMenu = !sc.showMenu;
      sc.isReportsState = !sc.isReportsState;
    };
    sc.handleShowTokenModal = function () {
      const modalInstance = $uibModal.open({
        animation: true,
        templateUrl: "src/template/modals/token.modal.php",
        scope: $scope,
        windowClass: "modal-dialog-centered",
      });

      sc.closeModal = function () {
        modalInstance.close();
      };
    };

    sc.handleGetToken = async () => {
      sc.handleShowTokenModal();
      sc.refreshToken = await AuthService.token("refreshtoken");
      sc.accessToken = await AuthService.token("accesstoken");
    };
    sc.handleNewToken = function (token) {
      sc.generating = true;
      AuthService.generate(token)
        .then((res) => {
          Toasty.showToast(
            "Generated!",
            `New token generated successfully`,
            `<i class="ph-fill ph-check-circle"></i>`,
            3000
          );
          sc.refreshToken = res.refresh_token;
          sc.accessToken = res.access_token;
        })
        .catch(function error(err) {
          console.error("Error tokens:", err);
        })
        .finally(function () {
          sc.generating = false;
        });
    };
    sc.handleCopyCode = function (codes) {
      navigator.clipboard.writeText(codes);
      Toasty.showToast("Copied", `Token has been copied`, `<i class='bx bxs-check-circle'></i>`, 3000);
    };
  });
