<div class="d-flex" ng-controller="ctrl">
    <div ui-view="sidebar"></div>
    <div class="content" id="content">
        <div ui-view="header"></div>
        <div class="container-fluid py-4 px-4">
            <div ui-view></div>
        </div>
    </div>
</div>