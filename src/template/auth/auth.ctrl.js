angular.module("app").controller("authCtrl", function ($scope, AuthService) {
  let vm = $scope;
  Object.assign(vm, {
    username: "",
    password: "",
    isloading: false,
  });
  vm.login = () => {
    if (!vm.username || !vm.password) {
      Toasty.showToast(
        "Authentication Error",
        `Invalid username or password!`,
        `<i class="ph-fill ph-x-circle text-danger"></i>`,
        3000
      );
      return;
    }

    vm.isloading = true;
    let users = {
      username: vm.username,
      password: vm.password,
    };

    AuthService.login(users)
      .then(function () {
        location.reload();
        vm.isloading = false;
      })
      .catch(function () {
        vm.isloading = false;
        Toasty.showToast(
          "Authentication Error",
          `Invalid email or password!`,
          `<i class="ph-fill ph-x-circle text-danger"></i>`,
          3000
        );
      });
  };
});
