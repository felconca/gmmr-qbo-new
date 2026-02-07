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
      accountsList: [],
      selectedItems: [],
      isFiltering: false,
      isSending: false,
      accessToken: AuthService.token("accesstoken"),
      Math: window.Math,
      currentPage: 1,
      itemsPerPage: 50,
      accountFilter: { isSub: "", isActive: true }
    });

    vm.handleItemsList = async () => {
      let token = await vm.accessToken;
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
    vm.handleAccountsList = async (isSub, isActive) => {
      let token = await vm.accessToken;
      if (token) {
        vm.isFiltering = true;
        $http
          .get(`api/quickbooks/accounts/list?token=${token}&sub=${isSub}&active=${isActive}`)
          .then((res) => {
            vm.accountsList = res.data.items;
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


    vm.abs = Math.abs;
    vm.formatNumber = (n) => n.toLocaleString();
    vm.getTotal = (list, key) => (list || []).reduce((total, el) => total + vm.abs(el[key]) * 1, 0);
    vm.getTotalInv = (list, key, qty) => (list || []).reduce((total, el) => total + vm.abs(el[key] * el[qty]) * 1, 0);
    vm.toISO = (dateStr) => {
      const d = new Date(dateStr);
      if (isNaN(d)) {
        console.warn("Invalid date:", dateStr);
        return null;
      }
      return d.toISOString();
    };

    if (vs.current.name == "app.quickbooks.items") {
      vm.handleItemsList();
    } else if (vs.current.name == "app.quickbooks.accounts") {
      vm.handleAccountsList(vm.accountFilter.isSub, vm.accountFilter.isActive);
    }
  });
