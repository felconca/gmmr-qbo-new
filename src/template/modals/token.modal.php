<div class="modal-header">
    <h5 class="modal-title fw-bold d-flex align-items-center">QuickBooks Token</h5>
    <button type="button" class="btn-close" aria-label="Close" ng-click="closeModal()"></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12">
            <div class="dt-range mb-2">
                <small>RefreshToken:</small>
                <div class="input-form-grp justify-content-between">
                    <small class="truncate-single-line">{{refreshToken}}</small>
                    <div ng-click="handleCopyCode(refreshToken)" class="form-grp-btn-icon" tooltip="copy" flow="down">
                        <i class='bx bx-copy-alt'></i>
                    </div>
                </div>
            </div>
            <div class="dt-range">
                <small>AccessToken:</small>
                <div class="input-form-grp justify-content-between">
                    <small class="truncate-single-line">{{accessToken}}</small>
                    <div ng-click="handleCopyCode(accessToken)" class="form-grp-btn-icon" tooltip="copy" flow="down">
                        <i class='bx bx-copy-alt'></i>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-theme-dark w-100" ng-click="handleNewToken(refreshToken)" ng-disabled="generating">
                    {{generating?"Generating...":"Generate New Token"}}
                </button>
            </div>
        </div>
    </div>
</div>