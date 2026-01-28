angular
    .module("app")
    .controller("advancesCtrl", function ($scope, $state, $filter, $qbo, $http, $uibModal, AuthService, SweetAlert2) {
        let vm = $scope;
        let vs = $state;

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const CM_FILTER = JSON.parse(localStorage.getItem("credit-filter"));

        const FILTER = (FILTERED) => ({
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
            filtered: FILTER(FILTER),
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
    })