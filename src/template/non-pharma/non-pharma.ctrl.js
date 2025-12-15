angular
  .module("app")
  .controller("nonpharmaCtrl", function ($scope, $state, $http, AuthService, SweetAlert2, $uibModal, $filter) {
    const vm = $scope;
    const vs = $state;

    // --- Initialization and State ---
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    const NONPHARMA_FILTER = JSON.parse(localStorage.getItem("nonpharma-filter"));
    const FILTER = (FILTERED) => ({
      startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
      endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
      status: FILTERED && FILTERED.status ? FILTERED.status : 0,
      isBooked: FILTERED && FILTERED.isbooked ? FILTERED.isbooked : -1,
    });

    Object.assign(vm, {
      invoicesList: [],
      invoiceDetails: [],
      invoiceInfo: {},
      selectedItems: [],
      currentPage: 1,
      itemsPerPage: 50,
      isLoadingData: false,
      isFiltering: false,
      isSending: false,
      filtered: FILTER(NONPHARMA_FILTER),
      Math: window.Math,
      invoiceId: 0,
    });

    vm.handleInvoiceList = (filter) => {
      vm.isFiltering = true;
      let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
        end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

      $http
        .get(
          `api/nonpharmacy/list?start_dt=${start_dt}&end_dt=${end_dt}&status=${filter.status}&isbooked=${filter.isBooked}`
        )
        .then((res) => {
          vm.invoicesList = res.data;
        })
        .catch((err) => {
          console.error(err);
        })
        .finally(() => {
          vm.isLoadingData = false;
          vm.isFiltering = false;
        });
    };
    vm.handleInvoiceList(vm.filtered);
    vm.handleFilter = (filtered) => {
      vm.invoicesList = [];
      vm.handleInvoiceList(filtered);
      localStorage.setItem("nonpharma-filter", JSON.stringify(filtered));
    };

    vm.showInvoiceModal = (id) => {
      if (id > 0) {
        vm.handleInvoiceEdit(id);
        vm.invoiceId = id;
        const $uibModalInstance = $uibModal.open({
          animation: true,
          templateUrl: "src/template/non-pharma/modal.tpl.php",
          size: "xl",
          scope: vm,
          backdrop: "static",
        });
        vm.closeInvoiceModal = () => $uibModalInstance.close();
      }
    };
    vm.handleInvoiceEdit = (id) => {
      $http
        .get(`api/nonpharmacy/edit?id=${id}`)
        .then((res) => {
          vm.invoiceInfo = res.data.invoice;
          vm.invoiceDetails = res.data.details;
        })
        .catch((err) => {
          console.error(err);
        });
    };
    // helpers
    vm.abs = Math.abs;
    vm.formatNumber = (n) => n.toLocaleString();
    vm.getTotal = (list, key) => (list || []).reduce((total, el) => total + vm.abs(el[key]) * 1, 0);
    vm.getTotalInv = (list, key, qty) => (list || []).reduce((total, el) => total + vm.abs(el[key] * el[qty]) * 1, 0);
  });
