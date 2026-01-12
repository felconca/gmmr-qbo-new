<div class="vh-100 d-flex align-items-center justify-content-center flex-column">
    <form class="login-form" ng-submit="login()">
        <div class="login-title pt-4 mt-2 pb-4">
            <div class="d-flex align-items-center justify-content-center">
                <img src="src/assets/img/logo_40x.png" alt="logo">
                <div class="ms-2">
                    <h5 class="mb-1" style="letter-spacing: 2px;">QBO BOOKING</h5>
                    <div class="sub">GMMR To QuickBooks Online Booking</div>
                </div>
            </div>
        </div>
        <div class="pt-3 pb-4 mb-3 px-4 mx-2">
            <h6 class="text-center fw-semibold py-3 text-dark">GMACT | QBO LOGIN</h6>
            <div class="input-form-grp mb-3" ng-disabled="isloading == true">
                <i class="ph-bold ph-user me-2"></i>
                <input type="text" placeholder="enter username" ng-model="username" ng-disabled="isloading == true">
            </div>
            <div class="input-form-grp mb-3" ng-disabled="isloading == true">
                <i class="ph-bold ph-lock-key-open" ng-if="password !== ''"></i>
                <i class="ph-bold ph-lock-key me-2" ng-if="password == ''"></i>
                <input type="password" placeholder="enter password" ng-model="password" ng-disabled="isloading == true">

            </div>
            <div class="d-flex aling-items-center justify-content-end">
                <button class="btn btn-theme-dark px-5" ng-disabled="isloading == true">
                    <img ng-if="isloading == true" style="width:20px; height:20px" src="src/assets/img/loader_24x.svg" alt="">
                    <span ng-if="isloading == false" class="text-white">LOGIN</span>
                </button>
            </div>
        </div>
    </form>
</div>