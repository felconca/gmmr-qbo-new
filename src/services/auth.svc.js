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

  function login(credentials) {
    return $http.post("api/login", credentials, { withCredentials: true }).then(function (res) {
      setCurrentUser(res.data.user);
      return res.data;
    });
  }

  function logout() {
    return $http.post("api/logout", {}, { withCredentials: true }).then(function (res) {
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
  };
});
