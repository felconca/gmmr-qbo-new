angular
    .module("app")
    .controller("advancesCtrl", function ($scope, $state, $filter, $qbo, $http, $uibModal, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const EM_FILTER = JSON.parse(localStorage.getItem("employee-filter"));

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
            isFiltering: false,
            isSending: false,
            currentPage: 1,
            itemsPerPage: 50,
            selectAll: false,
            isLoadingData: false,
            accessToken: AuthService.token("accesstoken"),
            filtered: EFILTER(EM_FILTER),
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