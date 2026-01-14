angular
  .module("app")
  .controller("quickbooksCtrl", function ($scope, $state, $http, AuthService, SweetAlert2, $qbo, $uibModal, $filter) {
    let vm = $scope;
    let vs = $state;

    Object.assign(vm, {
      itemsList: [],
      invoicesList: [],
      inventoryList: [],
      advancesList: [],
      selectedItems: [],
      isFiltering: false,
      isSending: false,
      accessToken: AuthService.token("accesstoken"),
    });

    vm.handleItemsList = async (accessToken) => {
      let token = await accessToken;
      if (token) {
        vm.isFiltering = true;
        $http
          .get(`api/quickbooks/items/list?token=${token}`)
          .then((res) => {
            vm.itemsList = res.data.items;
          })
          .catch((err) => {
            console.error(err);
          })
          .finally(() => {
            vm.isFiltering = false;
          });
      } else {
        Toasty.showToast("Error", `No token found, cannot retrieve list`, `<i class="ph-fill ph-x-circle"></i>`, 5000);
      }
    };
    vm.handleItemsList(vm.accessToken);
  });
