angular
    .module("app")
    .controller("debitMemoCtrl", function ($scope, $state, $filter, $qbo, $http, $uibModal, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const DM_FILTER = JSON.parse(localStorage.getItem("debit-filter"));

        const FILTER = (FILTERED) => ({
            startDate: FILTERED && FILTERED.startDate ? FILTERED.startDate : thirtyDaysAgo,
            endDate: FILTERED && FILTERED.endDate ? FILTERED.endDate : new Date(),
            personType: FILTERED && FILTERED.personType ? FILTERED.personType : 'all',
            isBooked: FILTERED && typeof FILTERED.isBooked !== "undefined" ? FILTERED.isBooked : -1,
        });

        Object.assign(vm, {
            debitMemosList: [],
            debitMemoDetails: [],
            debitMemoInfo: {},
            selectedItems: [],
            qboInfo: [],
            currentPage: 1,
            itemsPerPage: 50,
            selectAll: false,
            isLoadingData: false,
            isFiltering: false,
            isSending: false,
            filtered: FILTER(DM_FILTER),
            Math: window.Math,
            debitMemoId: 0,
        });

        vm.handleDebitMemoList = (filter) => {
            vm.isFiltering = true;
            let start_dt = $filter("date")(filter.startDate, "yyyy-MM-dd"),
                end_dt = $filter("date")(filter.endDate, "yyyy-MM-dd");

            $http
                .get(
                    `api/debit/list?start_dt=${start_dt}&end_dt=${end_dt}&isbooked=${filter.isBooked}&personType=${filter.personType}`
                )
                .then((res) => {
                    vm.debitMemosList = res.data;
                })
                .catch((err) => {
                    console.error(err);
                })
                .finally(() => {
                    vm.isLoadingData = false;
                    vm.isFiltering = false;
                });
        };
        vm.handleDebitMemoList(vm.filtered);
        vm.handleFilter = (filtered) => {
            vm.debitMemosList = [];
            vm.handleDebitMemoList(filtered);
            localStorage.setItem("debit-filter", JSON.stringify(filtered));
        };
        vm.handleDebitMemoEdit = (id) => {
            $http
                .get(`api/debit/edit?id=${id}`)
                .then((res) => {
                    vm.debitMemoInfo = res.data.dr;
                    vm.debitMemoDetails = res.data.details;
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
                    let debit = items.map((i) => ({
                        tranid: i.tranid,
                        cmid: i.cmid,
                        pxid: i.pxid > 0 ? i.pxid : "0",
                        gstatus: i.transtatus,
                        docnumber: $qbo.status(17) + i.tranid,
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
                        memo: `${i.transtatus} SI - ${i.tranid}\nPatient: ${i.pxid > 0 ? i.completepx : "Walk-In Patient"
                            }\nCreated By: ${i.ufname} ${i.ulname}`,
                    }));
                    $http
                        .post("api/debit/book_debit", { token: token, data: debit })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Debit memo(s) booked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} debit memos were booked.
                          ${failed} debit memo(s) failed to process`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.handleDebitMemoList(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.handleDebitMemoList(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot book debit memo(s), token not found`,
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
                    let debit = items.map((i) => ({
                        tranid: i.tranid,
                        qboid: i.sent_id,
                    }));
                    $http
                        .post("api/debit/delete_debit", { token: token, data: debit })
                        .then((res) => {
                            Toasty.showToast(
                                "Success",
                                `Debit memo(s) unbooked successfully`,
                                `<i class="ph-fill ph-check-circle"></i>`,
                                5000
                            );
                        })
                        .catch((err) => {
                            const success = err.data.results.filter((r) => r.status === "success").length;
                            const failed = err.data.results.filter((r) => r.status === "failed").length;
                            Toasty.showToast(
                                `Attention`,
                                `${success} of ${items.length} debit memos were unbooked.
                  ${failed} debit memo(s) failed to process`,
                                `<i class="ph-fill ph-warning text-warning"></i>`,
                                5000
                            );
                            console.error(`failed:${failed}`, `success:${success}`);
                        })
                        .finally(() => {
                            vm.isSending = false;
                            vm.selectAll = false;
                            vm.selectedItems = [];
                            vm.handleDebitMemoList(vm.filtered);
                        });
                } else {
                    vm.isSending = false;
                    vm.selectAll = false;
                    vm.selectedItems = [];
                    vm.handleDebitMemoList(vm.filtered);
                    Toasty.showToast(
                        "Token Error",
                        `Cannot unbook debit memo(s), token not found`,
                        `<i class="ph-fill ph-x-circle text-danger"></i>`,
                        3000
                    );
                }
            }
        };
        vm.findDebit = async function (id) {
            if (id > 0) {
                let token = await AuthService.token("accesstoken");
                $http
                    .post("api/debit/find_debit", { token: token, id: id })
                    .then((res) => vm.qboInfo = res.data.details)
                    .catch((err) => console.error(err));
                const $uibModalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: "src/template/debit-memo/qbo.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeModal = () => $uibModalInstance.close();
            }
        };

        vm.showDebitModal = (id) => {
            if (id > 0) {
                vm.handleDebitMemoEdit(id);
                const $uibModalInstance = $uibModal.open({
                    animation: true,
                    templateUrl: "src/template/debit-memo/modal.tpl.php",
                    size: "xl",
                    scope: vm,
                    backdrop: "static",
                });
                vm.closeDebitModal = () => $uibModalInstance.close();
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