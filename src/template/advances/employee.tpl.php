<div class="d-flex justify-content-between align-items-center">
    <div>
        <breadcrumbs></breadcrumbs>
        <h4 class="fw-semibold text-dark">Advances To Employee</h4>

    </div>

    <div class="d-flex align-items-center" style="gap:10px">
        <div class="text-white d-flex align-items-center">
            <span class="text-dark" ng-class="{'text-muted':selectedItems.length <= 0}">{{selectedItems.length}} selected</span>
        </div>
        <hr class="vr">
        <div class="d-flex align-items-center justify-content-end" style="gap:6px">
            <button class="btn btn-theme-dark" ng-click="handleBookItems(selectedItems)" ng-disabled="selectedItems.length <= 0 || isSending">
                <i class="ph-bold ph-share-fat me-1"></i>
                Book/Re-Book
            </button>
            <button class="btn btn-danger text-white" ng-click="handleUnBookedItems(selectedItems)" ng-disabled="selectedItems.length <= 0 || isSending">
                <i class="ph-bold ph-trash text-white me-1"></i>Unbooked
            </button>
        </div>
    </div>


</div>
<div class="d-flex justify-content-between align-items-center">
    <div class="input-form-grp bg-white" ng-disabled="isFiltering || isSending" style="width: 300px;">
        <input type="text" placeholder="Search" ng-model="search" ng-model-options="{ debounce: 500 }" ng-disabled="isFiltering || isSending">
        <i class='bx bx-search-alt-2'></i>
    </div>
</div>