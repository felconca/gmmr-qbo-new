angular.module("app").factory("AuthService", function ($http, $q) {
  let currentUser = null;

  function setCurrentUser(user) {
    currentUser = user;
  }

  function clearCurrentUser() {
    currentUser = null;
  }

  function getCookies(name) {
    const nameEQ = name + "=";
    const cookiesArray = document.cookie.split(";");
    for (let i = 0; i < cookiesArray.length; i++) {
      let c = cookiesArray[i];
      while (c.charAt(0) === " ") c = c.substring(1);
      if (c.indexOf(nameEQ) === 0) {
        return c.substring(nameEQ.length);
      }
    }
    return null;
  }

  /**
   * Usage:
   * - AuthService.token()                     // returns {accesstoken, refreshtoken}
   * - AuthService.token('accesstoken')        // returns access token only
   * - AuthService.token('refreshtoken')       // returns refresh token only
   */
  /**
   * Explanation:
   * You are seeing {$$state: {...}} because you are returning the result of an async function
   * (the $http.get().then(...)) directly, which is a Promise, not a resolved value.
   * In AngularJS, Promises (specifically $q promises) have an internal $$state property.
   *
   * To get the resolved value synchronously, you can't. You must use .then() or await (if using async/await and modern JS).
   *
   * Example usage:
   * AuthService.token('accesstoken').then(token => { ... });
   * or with async/await:
   * let token = await AuthService.token('accesstoken');
   */

  function token(which) {
    // Always returns a promise!
    return $http
      .get("api/token")
      .then(function (res) {
        if (res && res.data && typeof res.data === "object") {
          if ("accesstoken" in res.data && "refreshtoken" in res.data) {
            if (which === "accesstoken") {
              return res.data.accesstoken;
            } else if (which === "refreshtoken") {
              return res.data.refreshtoken;
            } else {
              // Return both if no argument
              return {
                accesstoken: res.data.accesstoken,
                refreshtoken: res.data.refreshtoken,
              };
            }
          }
        }
        // Otherwise, just return what was in .data (could be error)
        return res.data;
      })
      .catch(function () {
        return null;
      });
  }
  function generate(token) {
    return $http
      .get(`api/token/generate?token=${token}`)
      .then(function (res) {
        return res.data.data;
      })
      .catch(function error(err) {
        console.error("Error tokens:", err);
      });
  }

  function login(credentials) {
    return $http.post("api/login", credentials, { withCredentials: true }).then(function (res) {
      setCurrentUser(res.data.user);
      return res.data;
    });
  }

  function logout() {
    return $http.post("api/logout").then(function (res) {
      clearCurrentUser();
      location.reload();
      return res.data;
    });
  }

  function verify() {
    return $http
      .get("api/verify", { withCredentials: true })
      .then(function (res) {
        if (res.data && res.data.user) {
          setCurrentUser(res.data.user);
          return res;
        } else {
          return $q.reject("Not logged in");
        }
      })
      .catch(function (err) {
        clearCurrentUser();
        return $q.reject(err);
      });
  }

  function getUser() {
    return currentUser;
  }

  function isLoggedIn() {
    return !!getCookies("GMMRQBO_SESSION");
  }

  return {
    login: login,
    logout: logout,
    verify: verify,
    getUser: getUser,
    isLoggedIn: isLoggedIn,
    getCookies: getCookies,
    token: token,
    generate: generate,
  };
});
