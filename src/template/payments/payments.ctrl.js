angular
    .module("app")
    .controller("paymentsCtrl", function ($scope, $state, $filter, $http, $uibModal, $qbo, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

        const INPATIENTS_FILTER = JSON.parse(localStorage.getItem("inpatients-filter"));
        const WALKIN_FILTER = JSON.parse(localStorage.getItem("walkin-filter"));

        const FILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            types: FILTERED && FILTERED.types ? FILTERED.types : -1,
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });
        const WFILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            types: FILTERED && FILTERED.types ? FILTERED.types : -1,
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });


        Object.assign(vm, {
            walkinList: [],
            inpatientList: [],
            selectedItems: [],
            refNumber: "",
            paymentsInfo: {},
            paymentsDetails: [],
            currentPage: 1,
            itemsPerPage: 50,
            selectAll: false,
            isLoadingData: false,
            isFiltering: false,
            isSending: false,
            filtered: FILTER(INPATIENTS_FILTER),
            wfiltered: WFILTER(WALKIN_FILTER),
            Math: window.Math,
            paymentId: 0,
        });
        // data
        vm.getInpatients = (filter) => {
            vm.isFiltering = true;
            let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
                end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

            $http
                .get(
                    `api/payments/inpatients?start_dt=${start_dt}&end_dt=${end_dt}&type=${filter.types}&isbooked=${filter.isBooked}`
                )
                .then((res) => {
                    vm.inpatientList = res.data;
                })
                .catch((err) => {
                    console.error(err);
                })
                .finally(() => {
                    vm.isLoadingData = false;
                    vm.isFiltering = false;
                });
        }
        vm.getInpatients(vm.filtered);
        vm.getWalkIn = (filter) => {
            vm.isFiltering = true;
            let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
                end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

            $http
                .get(
                    `api/payments/walkin?start_dt=${start_dt}&end_dt=${end_dt}&type=${filter.types}&isbooked=${filter.isBooked}`
                )
                .then((res) => {
                    vm.walkinList = res.data;
                })
                .catch((err) => {
                    console.error(err);
                })
                .finally(() => {
                    vm.isLoadingData = false;
                    vm.isFiltering = false;
                });
        }
        vm.getWalkIn(vm.wfiltered);
        vm.handleFilter = (filtered, type) => {
            // console.log(filtered);
            if (type === "walkin") {
                vm.walkinList = [];
                vm.getWalkIn(filtered);
                localStorage.setItem("walkin-filter", JSON.stringify(filtered));
            } else {
                vm.inpatientList = [];
                vm.getInpatients(filtered);
                localStorage.setItem("inpatients-filter", JSON.stringify(filtered));
            }
        };
        vm.bookInpatients = async (items) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let payments = items.map((i) => ({
                        tranid: i.tranid,
                        pxid: i.pxid > 0 ? i.pxid : "0",
                        gstatus: 'Payment',
                        refnum: $qbo.status(5) + i.refnum,
                        txndate: i.trandate,
                        amount: i.netamount,
                        methodref: $qbo.methodref(i.ptypeid),
                        depositref: $qbo.depositref(i.ptypeid),
                        qbostatus: i.sent_status,
                        qboid: i.sent_id,
                        customerref: i.qbopx,
                        fname: i.fname,
                        mname: i.mname,
                        lname: i.lname,
                        suffix: i.suffix,
                        memo: `Payment For: ${i.payfor}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
                            }\nCreated By: ${i.ufname} ${i.ulname}\n${i.remarks}`,
                    }));
                    // console.log(payments);
                    $http
                        .post("api/payments/book-inpatient", { token: token, data: payments })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `payment(s) booked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                            vm.getInpatients(vm.filtered);
                            vm.getWalkIn(vm.wfiltered);
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} payments were booked.
                        ${failed} payment(s) failed to processed`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.getInpatients(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getInpatients(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book payment(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        }
        vm.bookWalkin = async (items) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let payments = items.map((i) => ({
                        tranid: i.tranid,
                        pxid: i.pxid > 0 ? i.pxid : "0",
                        gstatus: 'Payment',
                        refnum: $qbo.status(5) + i.refnum,
                        txndate: i.trandate,
                        amount: i.netamount,
                        methodref: $qbo.walkMethod(i.ptypeid),
                        depositref: $qbo.depositref(i.ptypeid),
                        qbostatus: i.sent_status,
                        qboid: i.sent_id,
                        customerref: i.pxid > 0 ? i.qbopx : 530,
                        fname: i.fname,
                        mname: i.mname,
                        lname: i.lname,
                        suffix: i.suffix,
                        line: [{ Amount: i.amount_due, LinkedTxn: [{ TxnId: i.sent_link_id, TxnType: "Invoice" }] }],
                        memo: `Payment For: ${i.payfor}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
                            }\nCreated By: ${i.ufname} ${i.ulname}\n${i.remarks}`,
                    }));
                    // console.log(payments);
                    $http
                        .post("api/payments/book-walkin", { token: token, data: payments })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `payment(s) booked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                            vm.getInpatients(vm.filtered);
                            vm.getWalkIn(vm.wfiltered);
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} payments were booked.
                        ${failed} payment(s) failed to processed`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.getInpatients(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getInpatients(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book payment(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        }
        vm.unbookInpatients = async (items) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let inventory = items.map((i) => ({
                        tranid: i.tranid,
                        qboid: i.sent_id,
                    }));
                    $http
                        .post("api/payments/unbook-payments", { token: token, data: inventory, database: 'wgcentralsupply' })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Payment(s) unbooked successfully`,
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
                        ${failed} payment(s) failed to processed`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.getInpatients(vm.filtered);
                            vm.getWalkIn(vm.wfiltered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getInpatients(vm.filtered);
                    vm.getWalkIn(vm.wfiltered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book payment(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        };
        vm.unbookWalkin = async (items) => {
            if (items.length > 0) {
                vm.isSending = true;
                let token = await AuthService.token("accesstoken");
                if (token) {
                    let inventory = items.map((i) => ({
                        tranid: i.tranid,
                        qboid: i.sent_id,
                    }));
                    $http
                        .post("api/payments/unbook-payments", { token: token, data: inventory, database: 'wgfinance' })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Payment(s) unbooked successfully`,
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
                        ${failed} payment(s) failed to processed`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.getInpatients(vm.filtered);
                            vm.getWalkIn(vm.wfiltered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.getInpatients(vm.filtered);
                    vm.getWalkIn(vm.wfiltered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book payment(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        };

        // helpers
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

        vm.sentStatus = (status) => vm.statusLabelMap[status]?.label || "";
        vm.sentStatusClass = (status) => vm.statusLabelMap[status]?.class || "";
    })