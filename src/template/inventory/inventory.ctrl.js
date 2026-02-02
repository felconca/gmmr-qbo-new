angular
  .module("app")
  .controller("inventoryCtrl", function ($scope, $state, $filter, AuthService, SweetAlert2, $qbo, $http) {
    let vm = $scope;
    let vs = $state;

    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    const INVENTORY_FILTER = JSON.parse(localStorage.getItem("pharma-inventory"));
    const INVENTORY_NFILTER = JSON.parse(localStorage.getItem("nonpharma-inventory"));
    const FILTER = (FILTERED) => ({
      startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
      endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
      status: FILTERED && typeof FILTERED.status !== "undefined" ? FILTERED.status : 0,
      isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1, // if the variable is 0  it return invalid so better check or undefined type
    });
    const NFILTER = (FILTERED) => ({
      startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
      endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
      status: FILTERED && typeof FILTERED.status !== "undefined" ? FILTERED.status : 0,
      isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1, // if the variable is 0  it return invalid so better check or undefined type
    });
    Object.assign(vm, {
      pharmacyList: [],
      nonpharmaList: [],
      selectedItems: [],
      currentPage: 1,
      itemsPerPage: 50,
      selectAll: false,
      isLoadingData: false,
      isFiltering: false,
      isSending: false,
      filtered: FILTER(INVENTORY_FILTER),
      nfiltered: NFILTER(INVENTORY_NFILTER),
      Math: window.Math,
      invoiceId: 0,
    });

    vm.getPharmacy = (filter) => {
      vm.isFiltering = true;
      let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
        end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

      $http
        .get(
          `api/inventory/pharmacy?start_dt=${start_dt}&end_dt=${end_dt}&status=${filter.status}&isbooked=${filter.isBooked}`
        )
        .then((res) => {
          vm.pharmacyList = res.data;
        })
        .catch((err) => {
          console.error(err);
        })
        .finally(() => {
          vm.isLoadingData = false;
          vm.isFiltering = false;
        });
    };
    vm.getPharmacy(vm.filtered);
    vm.getNonPharma = (filter) => {
      vm.isFiltering = true;
      let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
        end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

      $http
        .get(
          `api/inventory/nonpharma?start_dt=${start_dt}&end_dt=${end_dt}&status=${filter.status}&isbooked=${filter.isBooked}`
        )
        .then((res) => {
          vm.nonpharmaList = res.data;
        })
        .catch((err) => {
          console.error(err);
        })
        .finally(() => {
          vm.isLoadingData = false;
          vm.isFiltering = false;
        });
    };
    vm.getNonPharma(vm.nfiltered);
    vm.handleBookItems = async (items, st, db) => {
      if (items.length > 0) {
        let token = await AuthService.token("accesstoken");
        if (token) {
          vm.isSending = true;
          let inventory = items.map((i) => ({
            tranid: i.tranid,
            docnumber: "gmmr." + i.tranid,
            txndate: i.trandate,
            qbostatus: i.sent_status,
            qboid: i.cost_id,
            customerref: i.pxid > 0 ? i.qbopx : 530,
            pxid: i.pxid > 0 ? i.pxid : "0",
            fname: i.fname,
            mname: i.mname,
            lname: i.lname,
            suffix: i.suffix,
            amount: i.netcost,
            note: `${i.transtatus} SI - ${i.tranid}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
              }\nCreated By: ${i.ufname} ${i.ulname}`,
          }));
          //   console.log({ data: inventory, token: token, database: db });
          $http
            .post("api/inventory/book_inventory", { data: inventory, token: token, database: db })
            .then((res) => {
              Toasty.showToast(
                "Success",
                `Inventory booked successfully`,
                `<i class="ph-fill ph-check-circle"></i>`,
                5000
              );
              vm.getPharmacy(vm.filtered);
              vm.getNonPharma(vm.nfiltered);
            })
            .catch((err) => {
              const success = err.data.results.filter((r) => r.status === "success").length;
              const failed = err.data.results.filter((r) => r.status === "failed").length;
              Toasty.showToast(
                `Attention`,
                `${success} of ${items.length} inventory were booked.
                  ${failed} inventory failed to processed`,
                `<i class="ph-fill ph-warning text-warning"></i>`,
                5000
              );
              console.error(`failed:${failed}`, `success:${success}`);
            })
            .finally(() => {
              vm.isSending = false;
              vm.selectAll = false;
              vm.selectedItems = [];
            });
        }
      }
    };
    vm.handleUnBookedItems = async (items, db) => {
      if (items.length > 0) {
        vm.isSending = true;
        let token = await AuthService.token("accesstoken");
        if (token) {
          let inventory = items.map((i) => ({
            tranid: i.tranid,
            qboid: i.cost_id,
          }));
          $http
            .post("api/inventory/delete_inventory", { token: token, data: inventory, database: db })
            .then((res) => {
              Toasty.showToast(
                "Success",
                `Inventory(s) unbooked successfully`,
                `<i class="ph-fill ph-check-circle"></i>`,
                5000
              );
            })
            .catch((err) => {
              const success = err.data.results.filter((r) => r.status === "success").length;
              const failed = err.data.results.filter((r) => r.status === "failed").length;
              Toasty.showToast(
                `Attention`,
                `${success} of ${items.length} inventory were booked.
                  ${failed} inventory(s) failed to processed`,
                `<i class="ph-fill ph-warning text-warning"></i>`,
                5000
              );
              console.error(`failed:${failed}`, `success:${success}`);
            })
            .finally(() => {
              vm.isSending = false;
              vm.selectAll = false;
              vm.selectedItems = [];
              vm.getPharmacy(vm.filtered);
            });
        } else {
          vm.isSending = false;
          vm.selectAll = false;
          vm.selectedItems = [];
          vm.getPharmacy(vm.filtered);
          Toasty.showToast(
            "Token Error",
            `Cannot book inventory(s), token not found`,
            `<i class="ph-fill ph-x-circle text-danger"></i>`,
            3000
          );
        }
      }
    };
    vm.handleFilter = (filtered, type) => {
      // console.log(filtered);
      if (type === "pharma") {
        vm.pharmacyList = [];
        vm.getPharmacy(filtered);
        localStorage.setItem("pharma-inventory", JSON.stringify(filtered));
      } else {
        vm.nonpharmaList = [];
        vm.getNonPharma(filtered);
        localStorage.setItem("nonpharma-inventory", JSON.stringify(filtered));
      }
    };
    vm.handleSelectAllItems = (list) => {
      vm.selectAll = !vm.selectAll;
      const startIndex = (vm.currentPage - 1) * vm.itemsPerPage;
      const endIndex = Math.min(startIndex + vm.itemsPerPage, list.length);
      const itemsOnCurrentPage = list.slice(startIndex, endIndex);

      itemsOnCurrentPage.forEach((item) => {
        item.selected = vm.selectAll;
        vm.handleSelectItem(item);
      });
    };
    vm.handleSelectItem = (item) => {
      const index = vm.selectedItems.indexOf(item);
      if (index > -1) {
        vm.selectedItems.splice(index, 1);
      } else {
        vm.selectedItems.push(item);
      }
    };
    vm.changePage = (list) => {
      const startIndex = (vm.currentPage - 1) * vm.itemsPerPage;
      const endIndex = Math.min(startIndex + vm.itemsPerPage, list.length);
      vm.selectAll = list.slice(startIndex, endIndex).every((item) => item.selected);
    };

    // helpers
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
    // Maps status to label and CSS class for use in template rendering
    vm.statusLabelMap = {
      0: { label: "Not Booked", class: "not-sent" },
      1: { label: "Booked", class: "sent" },
      2: { label: "Modified", class: "modified" },
      4: { label: "Failed", class: "failed" },
      5: { label: "Unbooked", class: "unbooked" },
    };

    /**
     * Returns the status label (text only) for templates or logic
     */
    vm.sentStatus = (status) => vm.statusLabelMap[status]?.label || "";

    /**
     * Returns the status CSS class for ng-class binding in templates
     * Usage in template:
     * <span class="status {{ vm.sentStatusClass(items.sent_status) }}">{{ vm.sentStatus(items.sent_status) }}</span>
     */
    vm.sentStatusClass = (status) => vm.statusLabelMap[status]?.class || "";
  });
