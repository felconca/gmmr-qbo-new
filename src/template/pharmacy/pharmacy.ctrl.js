angular
  .module("app")
  .controller("pharmacyCtrl", function ($scope, $state, $http, AuthService, SweetAlert2, $qbo, $uibModal, $filter) {
    const vm = $scope;
    const vs = $state;

    // --- Initialization and State ---
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    const PHARMA_FILTER = JSON.parse(localStorage.getItem("pharma-filter"));
    const FILTER = (FILTERED) => ({
      startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
      endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
      status: FILTERED && typeof FILTERED.status !== "undefined" ? FILTERED.status : 0,
      isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1, // if the variable is 0  it return invalid so better check or undefined type
    });

    Object.assign(vm, {
      invoicesList: [],
      invoiceDetails: [],
      invoiceInfo: {},
      selectedItems: [],
      currentPage: 1,
      itemsPerPage: 50,
      selectAll: false,
      isLoadingData: false,
      isFiltering: false,
      isSending: false,
      filtered: FILTER(PHARMA_FILTER),
      Math: window.Math,
      invoiceId: 0,
    });

    vm.handleInvoiceList = (filter) => {
      vm.isFiltering = true;
      let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
        end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

      $http
        .get(
          `api/pharmacy/invoices?start_dt=${start_dt}&end_dt=${end_dt}&status=${filter.status}&isbooked=${filter.isBooked}`
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
      // console.log(filtered);
      vm.invoicesList = [];
      vm.handleInvoiceList(filtered);
      localStorage.setItem("pharma-filter", JSON.stringify(filtered));
    };
    vm.handleInvoiceEdit = (id) => {
      $http
        .get(`api/pharmacy/edit?id=${id}`)
        .then((res) => {
          vm.invoiceInfo = res.data.invoice;
          vm.invoiceDetails = res.data.details;
        })
        .catch((err) => {
          console.error(err);
        });
    };

    vm.handleBookItems = async (items) => {
      if (items.length > 0) {
        vm.isSending = true;
        let token = await AuthService.token("accesstoken");
        if (token) {
          let invoices = items.map((i) => ({
            tranid: i.tranid,
            pxid: i.pxid > 0 ? i.pxid : "0",
            gstatus: i.tstatus == 12 ? "PHARMA/OPD(CHARGE TO ACCOUNT)" : i.transtatus,
            docnumber: $qbo.status(2) + i.tranid,
            txndate: i.trandate,
            amount: i.netamount,
            discounts: i.discount + i.ldiscount,
            subtotal: i.gross,
            qbostatus: i.sent_status,
            qboid: i.sent_id,
            customerref: i.pxid > 0 ? i.qbopx : 530,
            fname: i.fname,
            mname: i.mname,
            lname: i.lname,
            suffix: i.suffix,
            gtaxcalc: $qbo.included(),
            memo: `${i.transtatus} SI - ${i.tranid}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
              }\nCreated By: ${i.ufname} ${i.ulname}`,
          }));
          // console.log(invoices);
          $http
            .post("api/pharmacy/book_invoice", { token: token, data: invoices })
            .then((res) => {
              Toasty.showToast(
                "Success",
                `Invoice(s) booked successfully`,
                `<i class="ph-fill ph-check-circle"></i>`,
                5000
              );
            })
            .catch((err) => {
              const success = err.data.results.filter((r) => r.status === "success").length;
              const failed = err.data.results.filter((r) => r.status === "failed").length;
              Toasty.showToast(
                `Attention`,
                `${success} of ${items.length} invoices were booked.
                ${failed} invoice(s) failed to processed`,
                `<i class="ph-fill ph-warning text-warning"></i>`,
                5000
              );
              console.error(`failed:${failed}`, `success:${success}`);
            })
            .finally(() => {
              vm.isSending = false;
              vm.selectAll = false;
              vm.selectedItems = [];
              vm.handleInvoiceList(vm.filtered);
            });
        } else {
          vm.isSending = false;
          vm.selectAll = false;
          vm.selectedItems = [];
          vm.handleInvoiceList(vm.filtered);
          Toasty.showToast(
            "Token Error",
            `Cannot book invoice(s), token not found`,
            `<i class="ph-fill ph-x-circle text-danger"></i>`,
            3000
          );
        }
      }
    };
    vm.handleUnBookedItems = async (items) => {
      if (items.length > 0) {
        vm.isSending = true;
        let token = await AuthService.token("accesstoken");
        if (token) {
          let invoices = items.map((i) => ({
            tranid: i.tranid,
            qboid: i.sent_id,
          }));
          $http
            .post("api/pharmacy/delete_invoice", { token: token, data: invoices })
            .then((res) => {
              Toasty.showToast(
                "Success",
                `Invoice(s) unbooked successfully`,
                `<i class="ph-fill ph-check-circle"></i>`,
                5000
              );
            })
            .catch((err) => {
              const success = err.data.results.filter((r) => r.status === "success").length;
              const failed = err.data.results.filter((r) => r.status === "failed").length;
              Toasty.showToast(
                `Attention`,
                `${success} of ${items.length} invoices were booked.
                ${failed} invoice(s) failed to processed`,
                `<i class="ph-fill ph-warning text-warning"></i>`,
                5000
              );
              console.error(`failed:${failed}`, `success:${success}`);
            })
            .finally(() => {
              vm.isSending = false;
              vm.selectAll = false;
              vm.selectedItems = [];
              vm.handleInvoiceList(vm.filtered);
            });
        } else {
          vm.isSending = false;
          vm.selectAll = false;
          vm.selectedItems = [];
          vm.handleInvoiceList(vm.filtered);
          Toasty.showToast(
            "Token Error",
            `Cannot book invoice(s), token not found`,
            `<i class="ph-fill ph-x-circle text-danger"></i>`,
            3000
          );
        }
      }
    };

    vm.showInvoiceModal = (id) => {
      if (id > 0) {
        vm.handleInvoiceEdit(id);
        vm.invoiceId = id;
        const $uibModalInstance = $uibModal.open({
          animation: true,
          templateUrl: "src/template/pharmacy/modal.tpl.php",
          size: "xl",
          scope: vm,
          backdrop: "static",
        });
        vm.closeInvoiceModal = () => $uibModalInstance.close();
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
    vm.findInvoice = async function (id) {
      let token = await AuthService.token("accesstoken");
      $http
        .post("api/pharmacy/findInvoice", { token: token, id: id })
        .then((res) => console.log(res.data))
        .catch((err) => console.error(err));
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

    vm.linkInvoiceToPayment = async (items) => {
      if (items.length > 0) {
        vm.isSending = true;
        let token = await AuthService.token("accesstoken");
        let data = items.map(i => ({ id: i.sent_id }));
        if (token) {
          $http.post('api/pharmacy/linked', { token, data })
            .then(res => {
              console.log(res.data);
              Toasty.showToast(
                "Success",
                "Invoice payment linked to gmmr successfully",
                `<i class="ph-fill ph-check-circle"></i>`,
                5000
              );
            })
            .catch(err => {
              const success = err.data.results.filter((r) => r.status === "success").length;
              const failed = err.data.results.filter((r) => r.status === "failed").length;
              Toasty.showToast(
                `Attention`,
                `${success} of ${items.length} invoices were linked.
                ${failed} invoice(s) failed to processed`,
                `<i class="ph-fill ph-warning text-warning"></i>`,
                5000
              );
              console.error(`failed:${failed}`, `success:${success}`);

            })
            .finally(() => {
              vm.isSending = false;
              vm.selectAll = false;
              vm.selectedItems = [];
              vm.handleInvoiceList(vm.filtered);
            });
        } else {
          vm.isSending = false;
          vm.selectAll = false;
          Toasty.showToast(
            "Token Error",
            "Cannot link invoice(s), token not found",
            `<i class="ph-fill ph-x-circle text-danger"></i>`,
            3000
          );
        }
      }
    }
  });
