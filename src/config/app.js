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

      IdleProvider.idle(60 * 60); // 1hr inactive = sync in backend
      IdleProvider.timeout(45); // 45 sec warning before logout
      IdleProvider.autoResume("notIdle");
      KeepaliveProvider.interval(60); // ping every 1 minute

      KeepaliveProvider.http("api/verify");

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

      // set to url to html5
      $locationProvider.html5Mode(true);
    },
  ])
  .run(function (
    $transitions,
    $state,
    AuthService,
    Idle,
    Keepalive, // still injected, but revert to normal keepalive interval behavior
    $rootScope,
    SweetAlert2
  ) {
    // 🧱 Set page title on route change
    $transitions.onSuccess({}, function (trans) {
      const title = trans.to().data && trans.to().data.pageTitle;
      document.title = title || "GMMR-QBO";
    });

    // Route Guard: Protect all except login
    $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
      if (!AuthService.isLoggedIn()) {
        Idle.unwatch();
        Keepalive.stop();
        return $state.target("login");
      }
      return AuthService.verify()
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
        return AuthService.verify()
          .then(() => $state.target("app.home"))
          .catch(() => true);
      }
      return true;
    });

    // ng-idle event listeners
    let idleWarningAlert = null;
    let idleTimeoutTriggered = false;

    $rootScope.$on("IdleStart", function () {
      console.warn("⏳ User is idle");
      idleTimeoutTriggered = false;
      idleWarningAlert = SweetAlert2.fire({
        title: "Inactive Warning",
        text: "You've been inactive for 1 hour. You will be automatically logged out in 45 seconds unless you continue.",
        icon: "warning",
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: "Continue",
        confirmButtonColor: "#848CB1",
        cancelButtonText: "Logout",
        timer: 45000,
        timerProgressBar: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          // Optionally focus the alert or add a timer description
        },
        willClose: () => {
          idleWarningAlert = null;
        },
      }).then((result) => {
        if (idleTimeoutTriggered) return; // Timeout event already handled logout
        if (result.isConfirmed) {
          // User chose to continue. Reset idle watcher
          Idle.watch();
        } else if (result.isDismissed || result.isDenied || result.isCanceled) {
          // User chose to logout or dismissed
          Idle.unwatch();
          AuthService.logout().finally(() => {
            $state.go("login");
          });
        }
      });
    });

    $rootScope.$on("IdleEnd", function () {
      console.log("🟢 User active again");
      // Close SweetAlert2 warning if still open
      if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
        idleWarningAlert.close();
        idleWarningAlert = null;
      } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
        window.Swal.close(); // fallback in case
      }
    });

    $rootScope.$on("IdleTimeout", function () {
      console.warn("⛔ Idle timeout reached — logging out");
      idleTimeoutTriggered = true;
      // Close warning if still present
      if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
        idleWarningAlert.close();
        idleWarningAlert = null;
      } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
        window.Swal.close(); // fallback in case
      }
      Idle.unwatch();
      AuthService.logout().finally(() => {
        $state.go("login");
      });
    });

    $rootScope.$on("Keepalive", () => {
      // Keepalive pings will now fire every interval (by default, every 1 min; verifySession endpoint)
      // console.log("Keepalive ping");
    });

    // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
    if (AuthService.isLoggedIn()) {
      AuthService.verify()
        .then(() => {
          Idle.watch();
          Keepalive.start();
        })
        .catch(() => {
          Idle.unwatch();
          Keepalive.stop();
        });
    } else {
      Idle.unwatch();
      Keepalive.stop();
    }
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
