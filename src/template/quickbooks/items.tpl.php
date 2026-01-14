<div class="d-flex justify-content-between align-items-center">
    <div>
        <h4 class="fw-semibold text-dark">Items List</h4>
        <div class="input-form-grp bg-white" ng-disabled="isFiltering || isSending" style="width: 300px;">
            <input type="text" placeholder="Search" ng-model="search" ng-model-options="{ debounce: 500 }" ng-disabled="isFiltering || isSending">
            <i class='bx bx-search-alt-2'></i>
        </div>
    </div>

    <div class="d-flex align-items-center" style="gap:10px">
        <div class="text-white d-flex align-items-center">
            <span class="text-dark" ng-class="{'text-muted':selectedItems.length <= 0}">{{selectedItems.length}} selected</span>
        </div>
        <hr class="vr">
        <div class="d-flex align-items-center" style="gap:6px">
            <button class="btn btn-theme-dark" ng-click="handleBookItems(selectedItems)" ng-disabled="isFiltering || isSending">
                <i class="ph-bold ph-plus-circle me-1"></i>
                Add Items
            </button>
            <button class="btn btn-danger text-white" ng-click="handleUnBookedItems(selectedItems)" ng-disabled="selectedItems.length <= 0 || isSending || isFiltering">
                <i class="ph-bold ph-trash text-white me-1"></i>Delete
            </button>
        </div>
    </div>
</div>
<div class="table-cms mt-2" style="height: calc(100vh - 280px);">
    <table class="table align-middle mb-0 table-striped">
        <thead class="position-sticky top-0" id="table-print">
            <tr>
                <th width="1%" class="text-center" style="font-size: 13px;">
                    <input ng-disabled="isFiltering || isSending" type="checkbox" class="form-check-input" ng-model="selectAll" ng-click="handleSelectAllItems(searched)">
                </th>
                <th width="1%" nowrap>QBO Id</th>
                <th nowrap>Descriptions</th>
                <th class="text-start" width="20%" nowrap>Income Account Ref</th>
                <th class="text-center" width="10%">Type</th>
                <th class="text-center" width="5%" nowrap>Status</th>
                <!-- <th class="text-center" width="5%" nowrap>Taxable</th> -->
                <th class="text-center" width="5%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr ng-repeat="items in searched = (itemsList | filter: search) | limitTo:itemsPerPage:itemsPerPage*(currentPage-1) track by $index">
                <td>
                    <input type="checkbox" ng-disabled="isLoading == true || isSending == true" class="form-check-input"
                        ng-model="items.selected" ng-click="handleSelectItem(items)">
                </td>
                <td nowrap>{{items.Id}}</td>
                <td nowrap>
                    <div>
                        <span class="fw-bold">{{items.Name}}</span>
                        <div class="small text-muted">{{items.FullyQualifiedName}}</div>
                    </div>
                </td>
                <td class="text-start" nowrap>
                    <span class="fw-semibold">{{items.IncomeAccountRef.name}}</span>
                    <div class="small text-muted">ID: {{items.IncomeAccountRef.value}}</div>
                </td>
                <td class="text-center" nowrap>{{items.Type}}</td>
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
    <ul style="margin-bottom: 0 !important;" uib-pagination total-items="searched.length" num-pages="numPages" items-per-page="itemsPerPage" ng-model="currentPage" max-size="5" boundary-link-numbers="true" ng-change="changePage(invoicesList)"></ul>
</div>