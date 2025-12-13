<div class="header d-flex align-items-center justify-content-between px-2" id="header">
    <div class="w-50">
        <button class="menu-btn" ng-click="handleToggleMenu()">
            <i class='bx bx-menu'></i>
        </button>
    </div>

    <div class="d-flex align-items-center justify-content-end" style="gap:6px">
        <button class="btn-nav" ng-click="handleGetToken()">
            <i class='bx bx-cog mg-2'></i>
            QuickBooks Token
        </button>
        <div class="actions-profile dropdown">
            <button data-bs-toggle="dropdown" class="dropdown-toggle" aria-expanded="false">
                <img src="../../../../dump_px/{{::userInfo.img}}" alt="profile" onerror="this.onerror=null;this.src='src/assets/img/logo_40x.png'">
                <span class="ms-2">{{::userInfo.alias}}</span>
                <i class="fa-solid fa-angle-down ms-2"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end bg-white">
                <div class="user-box">
                    <img src="../../../../dump_px/{{::userInfo.img}}" alt="profile" onerror="this.onerror=null;this.src='src/assets/img/logo_40x.png'">
                    <div class="ms-2">
                        <div class="fw-bold mb-0">{{::userInfo.name}}</div>
                        <small>{{::userInfo.type}}</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <li>
                    <button class="dropdown-item" type="button" ng-click="handleLogout()">
                        <i class='bx bx-log-in-circle mx-3'></i>
                        Logout
                    </button>
                </li>

            </ul>
        </div>
    </div>
</div>