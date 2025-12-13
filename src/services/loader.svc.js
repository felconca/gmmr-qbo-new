angular.module("app").factory("loadingInterceptor", [
  "$q",
  "$rootScope",
  function ($q, $rootScope) {
    let activeRequests = 0;
    const excludedPatterns = ["login", "404"];

    function shouldExclude(url) {
      return excludedPatterns.some((ex) => url.toLowerCase().includes(ex));
    }

    function show() {
      $rootScope.globalLoading = true;
    }

    function hide() {
      if (--activeRequests <= 0) {
        activeRequests = 0;
        $rootScope.globalLoading = false;
      }
    }

    return {
      request(config) {
        if (!shouldExclude(config.url)) {
          activeRequests++;
          show();
        }
        return config;
      },
      response(response) {
        if (!shouldExclude(response.config.url)) hide();
        return response;
      },
      responseError(rejection) {
        if (rejection.config && !shouldExclude(rejection.config.url)) hide();
        return $q.reject(rejection);
      },
    };
  },
]);
