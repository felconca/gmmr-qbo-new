angular
    .module("app")
    .controller("advancesCtrl", function ($scope, $state, $filter, $qbo, $http, $uibModal, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const EM_FILTER = JSON.parse(localStorage.getItem("employee-filter"));
        const AF_FILTER = JSON.parse(localStorage.getItem("affiliated-filter"));
        const AS_FILTER = JSON.parse(localStorage.getItem("assistant-filter"));
        const C_FILTER = JSON.parse(localStorage.getItem("claims-filter"));

        const EFILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });
        Object.assign(vm, {
            employeeList: [],
            affiliatedList: [],
            claimsList: [],
            assitanceList: [],
            selectedItems: [],
            creditMemoDetails: [],
            creditMemoInfo: {},
            currentPage: 1,
            itemsPerPage: 50,
            selectAll: false,
            isLoadingData: false,
            isFiltering: false,
            isSending: false,
            accessToken: AuthService.token("accesstoken"),
            filtered: EFILTER(EM_FILTER),
            // affiltered: EFILTER(AF_FILTER),
            // asfiltered: EFILTER(AS_FILTER),
            // cfiltered: EFILTER(C_FILTER),
            Math: window.Math,
            creditMemoId: 0,
        })

        // employee
        vm.handleAdvancesEmployee = (filter) => {
            vm.isFiltering = true;
            let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
                end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

            $http
                .get(`api/advances/employee?start_dt=${start_dt}&end_dt=${end_dt}&isbooked=${filter.isBooked}`)
                .then((res) => {
                    vm.employeeList = res.data;
                })
                .catch((err) => {
                    console.error(err);
                })
                .finally(() => {
                    vm.isLoadingData = false;
                    vm.isFiltering = false;
                });
        }
        vm.handleAdvancesEmployee(vm.filtered)
        vm.handleFilter = (filtered, to) => {
            if (to == 'employee') {
                localStorage.setItem("employee-filter", JSON.stringify(filtered))
                vm.handleAdvancesEmployee(filtered)
            } else if (to == 'assistant') {
                localStorage.setItem("assistant-filter", JSON.stringify(filtered))
            } else if (to == 'affiliated') {
                localStorage.setItem("affiliated-filter", JSON.stringify(filtered))
            } else if (to == 'claims') {
                localStorage.setItem("claims-filter", JSON.stringify(filtered))
            } else {
                Toasty.showToast('Invalid Filter', 'Cannof find filter specified', '<i class="ph-fill ph-x-circle text-danger"></i>', 3000);
            }
        }

        vm.handleBookItems = async (items) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let credit = items.map((i) => ({
                        tranid: i.tranid,
                        cmid: i.cmid,
                        pxid: i.pxid > 0 ? i.pxid : "0",
                        gstatus: i.transtatus,
                        docnumber: $qbo.status(16) + i.cmid,
                        txndate: i.trandate,
                        amount: i.netamount,
                        discounts: i.discount + i.ldiscount,
                        subtotal: i.gross,
                        qbostatus: i.sent_status,
                        qboid: i.sent_id,
                        customerref: i.qbopx,
                        fname: i.fname,
                        mname: i.mname,
                        lname: i.lname,
                        suffix: i.suffix,
                        gtaxcalc: $qbo.included(),
                        memo: `${i.transtatus} - ${i.cmid}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
                            }\nCreated By: ${i.ufname} ${i.ulname}`,
                    }));
                    // console.log(credit);
                    $http
                        .post("api/advances/book_credit", { token: token, data: credit })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Credit memo(s) booked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} credit memos were booked.
                          ${failed} credit memo(s) failed to process`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.handleCreditMemoList(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.handleCreditMemoList(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book credit memo(s), token not found`,
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
                    let credit = items.map((i) => ({
                        tranid: i.tranid,
                        qboid: i.sent_id,
                    }));
                    $http
                        .post("api/credit/delete_credit", { token: token, data: credit })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Credit memo(s) unbooked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} credit memos were unbooked.
                  ${failed} credit memo(s) failed to process`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.handleCreditMemoList(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.handleCreditMemoList(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot unbook credit memo(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        };
        vm.findCredit = async function (id) {
            if (id > 0) {
                let token = await AuthService.token("accesstoken");
                $http
                    .post("api/credit/find_credit", { token: token, id: id })
                    .then((res) => vm.qboInfo = res.data.details)
                    .catch((err) => console.error(err));
                const $uibModalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: "src/template/credit-memo/qbo.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeModal = () => $uibModalInstance.close();
            }


        };
        vm.handleCreditMemoEdit = (id) => {
            $http
                .get(`api/advances/employee/edit?id=${id}`)
                .then((res) => {
                    vm.creditMemoInfo = res.data.cm;
                    vm.creditMemoDetails = res.data.details;
                })
                .catch((err) => {
                    console.error(err);
                });
        };


        vm.showCreditModal = (id) => {
            if (id > 0) {
                vm.handleCreditMemoEdit(id);
                const $uibModalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: "src/template/advances/modal.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeCreditModal = () => $uibModalInstance.close();
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
    })