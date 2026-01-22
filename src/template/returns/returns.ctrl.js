angular
    .module("app")
    .controller("returnsCtrl", function ($scope, $state, $filter, $qbo, $http, $uibModal, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

        const RETURNS_FILTER = JSON.parse(localStorage.getItem("pharma-returns"));
        const RETURNS_NFILTER = JSON.parse(localStorage.getItem("nonpharma-returns"));
        const FILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            status: FILTERED && typeof FILTERED.status !== "undefined" ? FILTERED.status : 0,
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });
        const NFILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });


        Object.assign(vm, {
            pharmacyList: [],
            nonpharmaList: [],
            selectedItems: [],
            returnsInfo: {},
            returnsDetails: [],
            currentPage: 1,
            itemsPerPage: 50,
            selectAll: false,
            isLoadingData: false,
            isFiltering: false,
            isSending: false,
            filtered: FILTER(RETURNS_FILTER),
            nfiltered: NFILTER(RETURNS_NFILTER),
            Math: window.Math,
            invoiceId: 0,
        });

        vm.getPharmacy = (filter) => {
            vm.isFiltering = true;
            let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
                end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

            $http
                .get(
                    `api/returns/pharmacy?start_dt=${start_dt}&end_dt=${end_dt}&status=${filter.status}&isbooked=${filter.isBooked}`
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
                    `api/returns/nonpharma?start_dt=${start_dt}&end_dt=${end_dt}&isbooked=${filter.isBooked}`
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
        vm.handleFilter = (filtered, type) => {
            // console.log(filtered);
            if (type === "pharma") {
                vm.pharmacyList = [];
                vm.getPharmacy(filtered);
                localStorage.setItem("pharma-returns", JSON.stringify(filtered));
            } else {
                vm.nonpharmaList = [];
                vm.getNonPharma(filtered);
                localStorage.setItem("nonpharma-returns", JSON.stringify(filtered));
            }
        };
        vm.handleCreditMemoEdit = (id, db) => {
            $http
                .post(`api/returns/edit`, { id: id, database: db })
                .then((res) => {
                    vm.returnsInfo = res.data.cm;
                    vm.returnsDetails = res.data.details;
                })
                .catch((err) => {
                    console.error(err);
                });
        };

        vm.handleBookItems = async (items, db) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let y = db == 'wgfinance' ? 14 : 15
                    let credit = items.map((i) => ({
                        tranid: i.tranid,
                        cmid: i.cmid,
                        pxid: i.pxid > 0 ? i.pxid : "0",
                        gstatus: db == 'wgfinance' ? "PHARMA " : '' + "RETURNED SALES",
                        docnumber: $qbo.status(y) + i.cmid,
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
                        memo: `${db == 'wgfinance' ? "PHARMA " : '' + "RETURNED SALES CM"} - ${i.cmid}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
                            }\nCreated By: ${i.ufname} ${i.ulname}`,
                    }));
                    // console.log(credit);
                    $http
                        .post("api/returns/book_returns", { token: token, data: credit, database: db })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Return(s) booked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                            // Refresh the correct data list after booking
                            vm.getNonPharma(vm.nfiltered);
                            vm.getPharmacy(vm.filtered);
                        })
                        .catch((err) => {
                            let success = 0, failed = 0;
                            if (err && err.data && Array.isArray(err.data.results)) {
                                success = err.data.results.filter((r) => r.status === "success").length;
                                failed = err.data.results.filter((r) => r.status === "failed").length;
                            }
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} return(s) were booked.
                          ${failed} return(s) failed to process`,
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
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getNonPharma(vm.nfiltered);
                    vm.getPharmacy(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book credit memo(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        };
        vm.handleUnBookedItems = async (items, db) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let returns = items.map((i) => ({
                        tranid: i.tranid,
                        qboid: i.sent_id,
                    }));
                    $http
                        .post("api/returns/delete_returns", { token: token, data: returns, database: db })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Return(s) unbooked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                            // Refresh after deletion
                            vm.getNonPharma(vm.nfiltered);
                            vm.getPharmacy(vm.filtered);
                        })
                        .catch((err) => {
                            let success = 0, failed = 0;
                            if (err && err.data && Array.isArray(err.data.results)) {
                                success = err.data.results.filter((r) => r.status === "success").length;
                                failed = err.data.results.filter((r) => r.status === "failed").length;
                            }
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} return(s) were unbooked.
                  ${failed} return(s) failed to process`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                            // Always refresh after action
                            vm.getNonPharma(vm.nfiltered);
                            vm.getPharmacy(vm.filtered);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getNonPharma(vm.nfiltered);
                    vm.getPharmacy(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot unbook return(s), token not found`,
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
                    templateUrl: "src/template/returns/qbo.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeModal = () => $uibModalInstance.close();
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
        vm.showCreditModal = (id, db) => {
            if (id > 0) {
                vm.handleCreditMemoEdit(id, db);
                const $uibModalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: "src/template/returns/modal.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeCreditModal = () => $uibModalInstance.close();
            }
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

        vm.sentStatus = (status) => vm.statusLabelMap[status]?.label || "";
        vm.sentStatusClass = (status) => vm.statusLabelMap[status]?.class || "";
    })