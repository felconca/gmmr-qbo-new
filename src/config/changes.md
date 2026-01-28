  // .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //     // Configuration constants
  //     const IDLE_CONFIG = {
  //       WARNING_DURATION: 45000, // 45 seconds
  //       IDLE_MESSAGE:
  //         "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //       LOGOUT_TIMEOUT: 5000,
  //       DEFAULT_TITLE: "GMMR-QBO",
  //     };

  //     // Debug logging helper
  //     const DEBUG_IDLE = false;
  //     function logIdle(message, ...args) {
  //       if (DEBUG_IDLE) {
  //         console.log(`[IDLE] ${message}`, ...args);
  //       }
  //     }

  //     // 🧱 Set page title on route change
  //     $transitions.onSuccess({}, function (trans) {
  //       const title = trans.to().data && trans.to().data.pageTitle;
  //       document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //     });

  //     // Route Guard: Protect all except login
  //     $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //       if (!AuthService.isLoggedIn()) {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       }
  //       return AuthService.verify()
  //         .then(() => {
  //           Idle.watch();
  //           Keepalive.start();
  //           return true;
  //         })
  //         .catch(() => {
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           return $state.target("login");
  //         });
  //     });

  //     // Prevent logged-in users from accessing login page
  //     $transitions.onBefore({ to: "login" }, function () {
  //       if (AuthService.isLoggedIn()) {
  //         return AuthService.verify()
  //           .then(() => $state.target("app.home"))
  //           .catch(() => true);
  //       }
  //       return true;
  //     });

  //     // ng-idle event listeners
  //     let idleWarningAlert = null;
  //     let idleTimeoutTriggered = false;

  //     $rootScope.$on("IdleStart", function () {
  //       logIdle("IdleStart triggered");

  //       // Prevent multiple alerts
  //       if (idleWarningAlert) {
  //         console.warn("⚠️ Idle alert already showing");
  //         return;
  //       }

  //       Keepalive.stop();
  //       console.warn("⏳ User is idle");
  //       idleTimeoutTriggered = false;

  //       idleWarningAlert = SweetAlert2.fire({
  //         title: "Inactive Warning",
  //         text: IDLE_CONFIG.IDLE_MESSAGE,
  //         icon: "warning",
  //         showConfirmButton: true,
  //         showCancelButton: true,
  //         confirmButtonText: "Continue",
  //         confirmButtonColor: "#02AA53",
  //         cancelButtonText: "Logout",
  //         timer: IDLE_CONFIG.WARNING_DURATION,
  //         timerProgressBar: true,
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //         willClose: () => {
  //           logIdle("Alert willClose callback");
  //           idleWarningAlert = null;
  //         },
  //       }).then((result) => {
  //         logIdle("Alert resolved", result);

  //         // If alert was closed by IdleEnd or IdleTimeout, ignore
  //         if (!idleWarningAlert && !result.isConfirmed) {
  //           logIdle("Alert already handled by another event");
  //           return;
  //         }

  //         // Timeout event already handled logout
  //         if (idleTimeoutTriggered) {
  //           logIdle("Timeout already triggered, ignoring");
  //           return;
  //         }

  //         if (result.isConfirmed) {
  //           logIdle("User chose to continue");
  //           // User chose to continue - reset idle watcher
  //           Idle.watch();
  //           Keepalive.start();
  //         } else {
  //           // User chose to logout, dismissed, or timer expired
  //           logIdle("User chose to logout or dismissed");
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           AuthService.logout()
  //             .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //             .catch((err) => {
  //               console.error("Logout failed or timed out:", err);
  //             })
  //             .finally(() => {
  //               $state.go("login");
  //             });
  //         }
  //       });
  //     });

  //     $rootScope.$on("IdleEnd", function () {
  //       console.log("🟢 User active again");
  //       logIdle("IdleEnd triggered");

  //       if (idleWarningAlert) {
  //         // Store reference and clear immediately to prevent race conditions
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       }

  //       // Restart keepalive when user becomes active again
  //       if (AuthService.isLoggedIn()) {
  //         Keepalive.start();
  //       }
  //     });

  //     $rootScope.$on("IdleTimeout", function () {
  //       console.warn("⛔ Idle timeout reached — logging out");
  //       logIdle("IdleTimeout triggered");

  //       idleTimeoutTriggered = true;

  //       // Close warning if still present
  //       if (idleWarningAlert) {
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       }

  //       Idle.unwatch();
  //       Keepalive.stop();

  //       AuthService.logout()
  //         .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //         .catch((err) => {
  //           console.error("Logout failed or timed out:", err);
  //         })
  //         .finally(() => {
  //           $state.go("login");
  //         });
  //     });

  //     // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //     // is configured here — on each Keepalive event, the session is verified.
  //     $rootScope.$on("Keepalive", () => {
  //       logIdle("Keepalive ping - verifying session");

  //       AuthService.verify().catch((error) => {
  //         console.error("❌ Session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();

  //         // Show session expired notification
  //         SweetAlert2.fire({
  //           title: "Session Expired",
  //           text: "Your session has expired. Please log in again.",
  //           icon: "info",
  //           confirmButtonText: "OK",
  //           confirmButtonColor: "#02AA53",
  //           allowOutsideClick: false,
  //           allowEscapeKey: false,
  //         }).then(() => {
  //           $state.go("login");
  //         });
  //       });
  //     });

  //     // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //     if (AuthService.isLoggedIn()) {
  //       logIdle("App bootstrap - user is logged in, verifying session");
  //       AuthService.verify()
  //         .then(() => {
  //           logIdle("Session verified - starting idle watch and keepalive");
  //           Idle.watch();
  //           Keepalive.start();
  //         })
  //         .catch((error) => {
  //           console.error("Initial session verification failed:", error);
  //           Idle.unwatch();
  //           Keepalive.stop();
  //         });
  //     } else {
  //       logIdle("App bootstrap - user not logged in");
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     }

  //     // Cleanup on scope destruction to prevent memory leaks
  //     $rootScope.$on("$destroy", function () {
  //       logIdle("Root scope destroying - cleanup");
  //       if (idleWarningAlert) {
  //         idleWarningAlert.close();
  //         idleWarningAlert = null;
  //       }
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     });
  //   })
  //   .run(function (
  //     $transitions,
  //     $state,
  //     AuthService,
  //     Idle,
  //     Keepalive, // still injected, but revert to normal keepalive interval behavior
  //     $rootScope,
  //     SweetAlert2
  //   ) {
  //     // 🧱 Set page title on route change
  //     $transitions.onSuccess({}, function (trans) {
  //       const title = trans.to().data && trans.to().data.pageTitle;
  //       document.title = title || "GMMR-QBO";
  //     });

  //     // Route Guard: Protect all except login
  //     $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //       if (!AuthService.isLoggedIn()) {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       }
  //       return AuthService.verify()
  //         .then(() => {
  //           Idle.watch();
  //           Keepalive.start();
  //           return true;
  //         })
  //         .catch(() => {
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           return $state.target("login");
  //         });
  //     });

  //     // Prevent logged-in users from accessing login page
  //     $transitions.onBefore({ to: "login" }, function () {
  //       if (AuthService.isLoggedIn()) {
  //         return AuthService.verify()
  //           .then(() => $state.target("app.home"))
  //           .catch(() => true);
  //       }
  //       return true;
  //     });

  //     // ng-idle event listeners
  //     let idleWarningAlert = null;
  //     let idleTimeoutTriggered = false;

  //     $rootScope.$on("IdleStart", function () {
  //       Keepalive.stop();
  //       console.warn("⏳ User is idle");
  //       idleTimeoutTriggered = false;
  //       idleWarningAlert = SweetAlert2.fire({
  //         title: "Inactive Warning",
  //         text: "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //         icon: "warning",
  //         showConfirmButton: true,
  //         showCancelButton: true,
  //         confirmButtonText: "Continue",
  //         confirmButtonColor: "#02AA53",
  //         cancelButtonText: "Logout",
  //         timer: 45000,
  //         timerProgressBar: true,
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //         didOpen: () => {
  //           // Optionally focus the alert or add a timer description
  //         },
  //         willClose: () => {
  //           idleWarningAlert = null;
  //         },
  //       }).then((result) => {
  //         if (idleTimeoutTriggered) return; // Timeout event already handled logout
  //         if (result.isConfirmed) {
  //           // User chose to continue. Reset idle watcher
  //           Idle.watch();
  //         } else if (result.isDismissed || result.isDenied || result.isCanceled) {
  //           // User chose to logout or dismissed
  //           Idle.unwatch();
  //           AuthService.logout().finally(() => {
  //             $state.go("login");
  //           });
  //         }
  //       });
  //     });

  //     $rootScope.$on("IdleEnd", function () {
  //       console.log("🟢 User active again");
  //       // Close SweetAlert2 warning if still open
  //       if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
  //         idleWarningAlert.close();
  //         idleWarningAlert = null;
  //       } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //         window.Swal.close(); // fallback in case
  //       }
  //     });

  //     $rootScope.$on("IdleTimeout", function () {
  //       console.warn("⛔ Idle timeout reached — logging out");
  //       idleTimeoutTriggered = true;
  //       // Close warning if still present
  //       if (idleWarningAlert && typeof idleWarningAlert.close === "function") {
  //         idleWarningAlert.close();
  //         idleWarningAlert = null;
  //       } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //         window.Swal.close(); // fallback in case
  //       }
  //       Idle.unwatch();
  //       AuthService.logout().finally(() => {
  //         $state.go("login");
  //       });
  //     });

  //     // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //     // is configured here — on each Keepalive event, the session is verified.
  //     $rootScope.$on("Keepalive", () => {
  //       AuthService.verify().catch(() => {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         $state.go("login");
  //       });
  //     });

  //     // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //     if (AuthService.isLoggedIn()) {
  //       AuthService.verify()
  //         .then(() => {
  //           Idle.watch();
  //           Keepalive.start();
  //         })
  //         .catch(() => {
  //           Idle.unwatch();
  //           Keepalive.stop();
  //         });
  //     } else {
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     }
  //   })
  //   .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //     // Configuration constants
  //     const IDLE_CONFIG = {
  //       WARNING_DURATION: 45000, // 45 seconds
  //       IDLE_MESSAGE:
  //         "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //       LOGOUT_TIMEOUT: 5000,
  //       DEFAULT_TITLE: "GMMR-QBO",
  //       VERIFY_DEBOUNCE: 5000, // 5 seconds - prevent duplicate verify calls
  //     };

  //     // Debug logging helper
  //     const DEBUG_IDLE = false;
  //     function logIdle(message, ...args) {
  //       if (DEBUG_IDLE) {
  //         console.log(`[IDLE] ${message}`, ...args);
  //       }
  //     }

  //     // Debounced verification to prevent duplicate calls
  //     let lastVerifyTime = 0;
  //     let lastVerifyPromise = null;

  //     function debouncedVerify() {
  //       const now = Date.now();

  //       // If we verified recently, reuse the same promise
  //       if (now - lastVerifyTime < IDLE_CONFIG.VERIFY_DEBOUNCE && lastVerifyPromise) {
  //         logIdle("Reusing recent verify promise (debounced)");
  //         return lastVerifyPromise;
  //       }

  //       logIdle("Making new verify call");
  //       lastVerifyTime = now;
  //       lastVerifyPromise = AuthService.verify()
  //         .then((result) => {
  //           logIdle("Session verified successfully");
  //           return result;
  //         })
  //         .catch((error) => {
  //           logIdle("Session verification failed");
  //           lastVerifyPromise = null; // Clear on error so next call tries again
  //           throw error;
  //         });

  //       return lastVerifyPromise;
  //     }

  //     // 🧱 Set page title on route change
  //     $transitions.onSuccess({}, function (trans) {
  //       const title = trans.to().data && trans.to().data.pageTitle;
  //       document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //     });

  //     // Route Guard: Protect all except login
  //     $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //       if (!AuthService.isLoggedIn()) {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       }
  //       return debouncedVerify()
  //         .then(() => {
  //           Idle.watch();
  //           Keepalive.start();
  //           return true;
  //         })
  //         .catch(() => {
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           return $state.target("login");
  //         });
  //     });

  //     // Prevent logged-in users from accessing login page
  //     $transitions.onBefore({ to: "login" }, function () {
  //       if (AuthService.isLoggedIn()) {
  //         return debouncedVerify()
  //           .then(() => $state.target("app.home"))
  //           .catch(() => true);
  //       }
  //       return true;
  //     });

  //     // ng-idle event listeners
  //     let idleWarningAlert = null;
  //     let idleTimeoutTriggered = false;

  //     $rootScope.$on("IdleStart", function () {
  //       logIdle("IdleStart triggered");

  //       // Prevent multiple alerts
  //       if (idleWarningAlert) {
  //         console.warn("⚠️ Idle alert already showing");
  //         return;
  //       }

  //       Keepalive.stop();
  //       console.warn("⏳ User is idle");
  //       idleTimeoutTriggered = false;

  //       idleWarningAlert = SweetAlert2.fire({
  //         title: "Inactive Warning",
  //         text: IDLE_CONFIG.IDLE_MESSAGE,
  //         icon: "warning",
  //         showConfirmButton: true,
  //         showCancelButton: true,
  //         confirmButtonText: "Continue",
  //         confirmButtonColor: "#02AA53",
  //         cancelButtonText: "Logout",
  //         timer: IDLE_CONFIG.WARNING_DURATION,
  //         timerProgressBar: true,
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //         willClose: () => {
  //           logIdle("Alert willClose callback");
  //           idleWarningAlert = null;
  //         },
  //       }).then((result) => {
  //         logIdle("Alert resolved", result);

  //         // If alert was closed by IdleEnd or IdleTimeout, ignore
  //         if (!idleWarningAlert && !result.isConfirmed) {
  //           logIdle("Alert already handled by another event");
  //           return;
  //         }

  //         // Timeout event already handled logout
  //         if (idleTimeoutTriggered) {
  //           logIdle("Timeout already triggered, ignoring");
  //           return;
  //         }

  //         if (result.isConfirmed) {
  //           logIdle("User chose to continue");
  //           // User chose to continue - reset idle watcher
  //           Idle.watch();
  //           Keepalive.start();
  //         } else {
  //           // User chose to logout, dismissed, or timer expired
  //           logIdle("User chose to logout or dismissed");
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           AuthService.logout()
  //             .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //             .catch((err) => {
  //               console.error("Logout failed or timed out:", err);
  //             })
  //             .finally(() => {
  //               $state.go("login");
  //             });
  //         }
  //       });
  //     });

  //     $rootScope.$on("IdleEnd", function () {
  //       console.log("🟢 User active again");
  //       logIdle("IdleEnd triggered");

  //       if (idleWarningAlert) {
  //         // Store reference and clear immediately to prevent race conditions
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       }

  //       // Restart keepalive when user becomes active again
  //       if (AuthService.isLoggedIn()) {
  //         Keepalive.start();
  //       }
  //     });

  //     $rootScope.$on("IdleTimeout", function () {
  //       console.warn("⛔ Idle timeout reached — logging out");
  //       logIdle("IdleTimeout triggered");

  //       idleTimeoutTriggered = true;

  //       // Close warning if still present
  //       if (idleWarningAlert) {
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       }

  //       Idle.unwatch();
  //       Keepalive.stop();

  //       AuthService.logout()
  //         .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //         .catch((err) => {
  //           console.error("Logout failed or timed out:", err);
  //         })
  //         .finally(() => {
  //           $state.go("login");
  //         });
  //     });

  //     // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //     // is configured here — on each Keepalive event, the session is verified.
  //     $rootScope.$on("Keepalive", () => {
  //       logIdle("Keepalive ping - verifying session");

  //       debouncedVerify().catch((error) => {
  //         console.error("❌ Session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();

  //         // Show session expired notification
  //         SweetAlert2.fire({
  //           title: "Session Expired",
  //           text: "Your session has expired. Please log in again.",
  //           icon: "info",
  //           confirmButtonText: "OK",
  //           confirmButtonColor: "#02AA53",
  //           allowOutsideClick: false,
  //           allowEscapeKey: false,
  //         }).then(() => {
  //           $state.go("login");
  //         });
  //       });
  //     });

  //     // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //     if (AuthService.isLoggedIn()) {
  //       logIdle("App bootstrap - user is logged in, verifying session");
  //       debouncedVerify()
  //         .then(() => {
  //           logIdle("Session verified - starting idle watch and keepalive");
  //           Idle.watch();
  //           Keepalive.start();
  //         })
  //         .catch((error) => {
  //           console.error("Initial session verification failed:", error);
  //           Idle.unwatch();
  //           Keepalive.stop();
  //         });
  //     } else {
  //       logIdle("App bootstrap - user not logged in");
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     }

  //     // Cleanup on scope destruction to prevent memory leaks
  //     $rootScope.$on("$destroy", function () {
  //       logIdle("Root scope destroying - cleanup");
  //       if (idleWarningAlert) {
  //         idleWarningAlert.close();
  //         idleWarningAlert = null;
  //       }
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     });
  //   })
  //   .run(function ($transitions, $state, AuthService, Idle, Keepalive, $rootScope, SweetAlert2) {
  //     // Configuration constants
  //     const IDLE_CONFIG = {
  //       WARNING_DURATION: 45000, // 45 seconds
  //       IDLE_MESSAGE:
  //         "You've been inactive for 25 minutes. You will be automatically logged out after 45 seconds unless you continue.",
  //       LOGOUT_TIMEOUT: 5000,
  //       DEFAULT_TITLE: "GMMR-QBO",
  //       VERIFY_DEBOUNCE: 5000, // 5 seconds - prevent duplicate verify calls
  //     };

  //     // Debug logging helper
  //     const DEBUG_IDLE = false;
  //     function logIdle(message, ...args) {
  //       if (DEBUG_IDLE) {
  //         console.log(`[IDLE] ${message}`, ...args);
  //       }
  //     }

  //     // Debounced verification to prevent duplicate calls
  //     let lastVerifyTime = 0;
  //     let lastVerifyPromise = null;

  //     function debouncedVerify() {
  //       const now = Date.now();

  //       // If we verified recently, reuse the same promise
  //       if (now - lastVerifyTime < IDLE_CONFIG.VERIFY_DEBOUNCE && lastVerifyPromise) {
  //         logIdle("Reusing recent verify promise (debounced)");
  //         return lastVerifyPromise;
  //       }

  //       logIdle("Making new verify call");
  //       lastVerifyTime = now;
  //       lastVerifyPromise = AuthService.verify()
  //         .then((result) => {
  //           logIdle("Session verified successfully");
  //           return result;
  //         })
  //         .catch((error) => {
  //           logIdle("Session verification failed");
  //           lastVerifyPromise = null; // Clear on error so next call tries again
  //           throw error;
  //         });

  //       return lastVerifyPromise;
  //     }

  //     // 🧱 Set page title on route change
  //     $transitions.onSuccess({}, function (trans) {
  //       const title = trans.to().data && trans.to().data.pageTitle;
  //       document.title = title || IDLE_CONFIG.DEFAULT_TITLE;
  //     });

  //     // Route Guard: Protect all except login
  //     $transitions.onBefore({ to: (s) => s.name !== "login" }, function () {
  //       if (!AuthService.isLoggedIn()) {
  //         Idle.unwatch();
  //         Keepalive.stop();
  //         return $state.target("login");
  //       }
  //       return debouncedVerify()
  //         .then(() => {
  //           Idle.watch();
  //           Keepalive.start();
  //           return true;
  //         })
  //         .catch(() => {
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           return $state.target("login");
  //         });
  //     });

  //     // Prevent logged-in users from accessing login page
  //     $transitions.onBefore({ to: "login" }, function () {
  //       if (AuthService.isLoggedIn()) {
  //         return debouncedVerify()
  //           .then(() => $state.target("app.home"))
  //           .catch(() => true);
  //       }
  //       return true;
  //     });

  //     // ng-idle event listeners
  //     let idleWarningAlert = null;
  //     let idleTimeoutTriggered = false;

  //     $rootScope.$on("IdleStart", function () {
  //       logIdle("IdleStart triggered");

  //       // Prevent multiple alerts
  //       if (idleWarningAlert) {
  //         console.warn("⚠️ Idle alert already showing");
  //         return;
  //       }

  //       Keepalive.stop();
  //       console.warn("⏳ User is idle");
  //       idleTimeoutTriggered = false;

  //       idleWarningAlert = SweetAlert2.fire({
  //         title: "Inactive Warning",
  //         text: IDLE_CONFIG.IDLE_MESSAGE,
  //         icon: "warning",
  //         showConfirmButton: true,
  //         showCancelButton: true,
  //         confirmButtonText: "Continue",
  //         confirmButtonColor: "#02AA53",
  //         cancelButtonText: "Logout",
  //         timer: IDLE_CONFIG.WARNING_DURATION,
  //         timerProgressBar: true,
  //         allowOutsideClick: false,
  //         allowEscapeKey: false,
  //         didOpen: () => {
  //           // Optionally focus the alert or add a timer description
  //         },
  //         willClose: () => {
  //           logIdle("Alert willClose callback");
  //           idleWarningAlert = null;
  //         },
  //       }).then((result) => {
  //         logIdle("Alert resolved", result);

  //         // If alert was closed by IdleEnd or IdleTimeout, ignore
  //         if (!idleWarningAlert && !result.isConfirmed) {
  //           logIdle("Alert already handled by another event");
  //           return;
  //         }

  //         // Timeout event already handled logout
  //         if (idleTimeoutTriggered) {
  //           logIdle("Timeout already triggered, ignoring");
  //           return;
  //         }

  //         if (result.isConfirmed) {
  //           logIdle("User chose to continue");
  //           // User chose to continue - reset idle watcher and restart keepalive
  //           Idle.watch();
  //           Keepalive.start();
  //         } else if (result.isDismissed || result.isDenied || result.dismiss === "cancel") {
  //           // User chose to logout or dismissed
  //           logIdle("User chose to logout or dismissed");
  //           Idle.unwatch();
  //           Keepalive.stop();
  //           AuthService.logout()
  //             .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //             .catch((err) => {
  //               console.error("Logout failed or timed out:", err);
  //             })
  //             .finally(() => {
  //               $state.go("login");
  //             });
  //         }
  //       });
  //     });

  //     $rootScope.$on("IdleEnd", function () {
  //       console.log("🟢 User active again");
  //       logIdle("IdleEnd triggered");

  //       // Close SweetAlert2 warning if still open
  //       if (idleWarningAlert) {
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //         window.Swal.close(); // fallback in case
  //       }

  //       // Restart keepalive when user becomes active again
  //       if (AuthService.isLoggedIn()) {
  //         Keepalive.start();
  //       }
  //     });

  //     $rootScope.$on("IdleTimeout", function () {
  //       console.warn("⛔ Idle timeout reached — logging out");
  //       logIdle("IdleTimeout triggered");

  //       idleTimeoutTriggered = true;

  //       // Close warning if still present
  //       if (idleWarningAlert) {
  //         const alertToClose = idleWarningAlert;
  //         idleWarningAlert = null;

  //         if (typeof alertToClose.close === "function") {
  //           alertToClose.close();
  //         }
  //       } else if (window.Swal && window.Swal.isVisible && window.Swal.isVisible()) {
  //         window.Swal.close(); // fallback in case
  //       }

  //       Idle.unwatch();
  //       Keepalive.stop();

  //       AuthService.logout()
  //         .timeout(IDLE_CONFIG.LOGOUT_TIMEOUT)
  //         .catch((err) => {
  //           console.error("Logout failed or timed out:", err);
  //         })
  //         .finally(() => {
  //           $state.go("login");
  //         });
  //     });

  //     // The AuthService.verify call that happens every minute (based on KeepaliveProvider.interval)
  //     // is configured here — on each Keepalive event, the session is verified.
  //     $rootScope.$on("Keepalive", () => {
  //       logIdle("Keepalive ping - verifying session");

  //       debouncedVerify().catch((error) => {
  //         console.error("❌ Session verification failed:", error);
  //         Idle.unwatch();
  //         Keepalive.stop();

  //         // Show session expired notification
  //         SweetAlert2.fire({
  //           title: "Session Expired",
  //           text: "Your session has expired. Please log in again.",
  //           icon: "info",
  //           confirmButtonText: "OK",
  //           confirmButtonColor: "#02AA53",
  //           allowOutsideClick: false,
  //           allowEscapeKey: false,
  //         }).then(() => {
  //           $state.go("login");
  //         });
  //       });
  //     });

  //     // On app bootstrap: verify session and appropriately begin/stop ng-idle & keepalive
  //     if (AuthService.isLoggedIn()) {
  //       logIdle("App bootstrap - user is logged in, verifying session");
  //       debouncedVerify()
  //         .then(() => {
  //           logIdle("Session verified - starting idle watch and keepalive");
  //           Idle.watch();
  //           Keepalive.start();
  //         })
  //         .catch((error) => {
  //           console.error("Initial session verification failed:", error);
  //           Idle.unwatch();
  //           Keepalive.stop();
  //         });
  //     } else {
  //       logIdle("App bootstrap - user not logged in");
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     }

  //     // Cleanup on scope destruction to prevent memory leaks
  //     $rootScope.$on("$destroy", function () {
  //       logIdle("Root scope destroying - cleanup");
  //       if (idleWarningAlert) {
  //         idleWarningAlert.close();
  //         idleWarningAlert = null;
  //       }
  //       Idle.unwatch();
  //       Keepalive.stop();
  //     });
  // })