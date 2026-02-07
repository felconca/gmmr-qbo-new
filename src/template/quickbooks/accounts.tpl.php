<div class="d-flex justify-content-between align-items-center justify-content-between">
    <div>
        <h4 class="fw-semibold text-dark">Chart of Accounts</h4>
        <div class="input-form-grp bg-white" ng-disabled="isFiltering || isSending" style="width: 300px;">
            <input type="text" placeholder="Search" ng-model="search" ng-model-options="{ debounce: 500 }" ng-disabled="isFiltering || isSending">
            <i class='bx bx-search-alt-2'></i>
        </div>
    </div>
    <div class="d-flex align-items-end justify-content-end" style="gap: 6px;">
        <div class="filter-input w-50">
            <span>Status</span>
            <select class="input-form ng-valid ng-not-empty ng-dirty ng-valid-parse ng-touched" ng-model="accountFilter.isActive" ng-disabled="isFiltering == true || isSending == true" style="">
                <option ng-value="true" selected="selected">Active</option>
                <option ng-value="false">In Active</option>
            </select>
        </div>
        <div class="filter-input w-50">
            <span>Account Type</span>
            <select class="input-form ng-pristine ng-valid ng-empty ng-touched" ng-model="accountFilter.isSub" ng-disabled="isFiltering == true || isSending == true" style="">
                <option value="" selected="selected">All Accounts</option>
                <option ng-value="true">Sub Accounts</option>
                <option ng-value="false">Major Accounts</option>
            </select>
        </div>
        <button class="btn btn-theme-dark" ng-click="handleAccountsList(accountFilter.isSub, accountFilter.isActive)" ng-disabled="isFiltering == true || isSending == true">
            <img ng-if="isFiltering == true" style="width:20px; height:20px" src="src/assets/img/loader_24x.svg" alt="">
            <span ng-if="isFiltering == false" class="text-white">GO</span>
        </button>
    </div>
</div>


<div class="table-cms mt-2" style="height: calc(100vh - 280px);">
    <table class="table align-middle mb-0 table-striped">
        <thead class="position-sticky top-0" id="table-print">
            <tr>
                <th width="1%" class="text-center" style="font-size: 13px;">
                    <input ng-disabled="isFiltering || isSending" type="checkbox" class="form-check-input" ng-model="selectAll" ng-click="handleSelectAllItems(searched)">
                </th>
                <th width="1%" nowrap>QBO ID</th>
                <th nowrap>Name</th>
                <th nowrap>FullyQualifiedName</th>
                <th class="text-start" width="15%" nowrap>Account Type</th>
                <th class="text-center" width="10%" nowrap>Is Sub Accnt</th>
                <th class="text-center" width="5%" nowrap>Status</th>
                <th class="text-center" width="5%" nowrap>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr ng-repeat="items in searched = (accountsList | filter: search) | limitTo:itemsPerPage:itemsPerPage*(currentPage-1) track by $index">
                <td>
                    <input type="checkbox" ng-disabled="isFiltering == true || isSending == true" class="form-check-input"
                        ng-model="items.selected" ng-click="handleSelectItem(items)">
                </td>
                <td nowrap>{{items.Id}}</td>
                <td nowrap>
                    <span class="fw-bold">{{items.Name}}</span>
                </td>
                <td>{{items.FullyQualifiedName}}</td>
                <td class="text-start" nowrap>
                    <span class="fw-semibold">{{items.AccountType}}</span>
                    <div class="small text-muted" ng-if="items.AccountSubType">Subtype: {{items.AccountSubType}}</div>
                </td>
                <td class="text-center" nowrap>
                    <span ng-if="items.SubAccount" class="text-success" title="Is sub account">
                        <i class="ph-fill ph-check-circle text-success"></i>
                    </span>
                    <span ng-if="!items.SubAccount" class="text-danger" title="Not a sub account">
                        <i class="ph-fill ph-x-circle text-danger"></i>
                    </span>
                </td>
                <td class="text-center" nowrap>
                    <span class="status" ng-class="items.Active ? 'sent' : 'not-sent'">{{ items.Active ? 'Active' : 'Inactive' }}</span>
                </td>
                <!-- <td class="text-center" nowrap>
                    <span ng-if="items.SalesTaxCodeRef && items.SalesTaxCodeRef.name == 'VAT-S'">
                        <i class="ph-fill ph-seal-check text-success"></i>
                    </span>
                    <span ng-if="!items.SalesTaxCodeRef || items.SalesTaxCodeRef && items.SalesTaxCodeRef.name == 'VAT-Ex'">
                        <i class="ph-fill ph-minus-circle text-danger"></i>
                    </span>
                    // medyo budlay kay sabad qbo
                </td> -->
                <td class="text-center">
                    <div class="d-flex align-center justify-content-center">
                        <button class="btn-table" ng-click="editItem(items)">
                            <i class="ph-bold ph-pencil-simple-line"></i>
                        </button>
                        <button class="btn-table" ng-click="deleteItem(items)">
                            <i class="ph-bold ph-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<!-- pagination -->
<div class="d-flex align-items-center justify-content-between pt-3">
    <span>
        Showing {{
        searched.length > 0 ? formatNumber((currentPage - 1) * itemsPerPage + 1) : 0
        }} to {{
            searched.length > 0 ? formatNumber(Math.min(currentPage * itemsPerPage, searched.length)) : 0
        }} of {{formatNumber(searched.length)}} entries
    </span>
    <ul style="margin-bottom: 0 !important;" uib-pagination total-items="searched.length" num-pages="numPages" items-per-page="itemsPerPage" ng-model="currentPage" max-size="5" boundary-link-numbers="true" ng-change="changePage(accountsList)"></ul>
</div>