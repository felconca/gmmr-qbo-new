angular.module("app").factory("BreadcrumbService", function ($state) {
  function getBreadcrumbs() {
    let breadcrumbs = [];
    let currentState = $state.$current;

    while (currentState && currentState.self && currentState.self.data) {
      if (currentState.self.data.breadcrumb) {
        breadcrumbs.unshift({
          name: currentState.self.data.breadcrumb,
          state: currentState.self.name,
          home: currentState.self.data.home || false,
        });
      }
      currentState = currentState.parent;
    }

    // 👉 Always inject Home if missing
    if (!breadcrumbs.some((b) => b.home)) {
      breadcrumbs.unshift({
        name: "Home",
        state: "home",
        home: true,
      });
    }

    return breadcrumbs;
  }

  return {
    getBreadcrumbs: getBreadcrumbs,
  };
});
