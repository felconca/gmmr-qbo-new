<div class="sidebar" ng-controller="ctrl" id="sidebar">
    <div class="sidebar-header">
        <img src="src/assets/img/logo_40x.png" alt="logo">
        <div class="header-txt">
            <div class="title">QBO BOOKING</div>
            <div class="sub">GMMR To QB Online Booking</div>
        </div>
    </div>
    <div class="sidebar-menu">
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.non-pharma" ui-sref-opts="{reload: true}"><i class='bx bxs-buildings'></i><span>NonPharma Sales</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.pharmacy" ui-sref-opts="{reload: true}"><i class='bx bxs-capsule'></i><span>Pharmacy Sales</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.professional-fee" ui-sref-opts="{reload: true}"><i class='bx bxs-user-pin'></i><span>Professional Fees</span></a></li>
            <li ui-sref-active="active"><a ui-sref="csreturns" ui-sref-opts="{reload: true}"><i class='bx bxs-direction-right'></i><span>NonPharma Returns</span></a></li>
            <li ui-sref-active="active"><a ui-sref="fsreturns" ui-sref-opts="{reload: true}"><i class='bx bxs-directions'></i><span>Pharmacy Returns</span></a></li>
            <li ui-sref-active="active"><a ui-sref="cm" ui-sref-opts="{reload: true}"><i class='bx bxs-detail'></i><span>Credit Memo</span></a></li>
            <li ui-sref-active="active"><a ui-sref="dr" ui-sref-opts="{reload: true}"><i class='bx bxs-detail'></i><span>Debit Memo</span></a></li>
            <li ui-sref-active="active"><a ui-sref="advances" ui-sref-opts="{reload: true}"><i class='bx bxs-donate-heart'></i><span>Advances/Benefits</span></a></li>
            <li ui-sref-active="active"><a ui-sref="payments" ui-sref-opts="{reload: true}"><i class='bx bxs-dollar-circle'></i><span>Payments</span></a></li>
            <li ui-sref-active="active"><a ui-sref="deposits" ui-sref-opts="{reload: true}"><i class='bx bxs-bank'></i><span>For Deposits</span></a></li>
            <li ui-sref-active="active"><a ui-sref="departments" ui-sref-opts="{reload: true}"><i class='bx bxs-category-alt'></i><span>Departments</span></a></li>
            <li ui-sref-active="active"><a ui-sref="advances-link" ui-sref-opts="{reload: true}"><i class='bx bxs-buildings'></i><span>Advances Linking</span></a></li>

        </ul>

        <div class="divider-text my-3">
            <div class="divider"></div>
            <span>QuickBooks Online</span>
            <div class="divider"></div>
        </div>

        <ul>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbocustomer"><i class='bx bxs-user-pin'></i><span>Patients</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbovendor"><i class='bx bxs-user-pin'></i><span>Vendors</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qboinvoice"><i class='bx bx-globe'></i></i><span>Invoices</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbojrnl"><i class='bx bx-globe'></i></i><span>Journals</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbopayment"><i class='bx bx-globe'></i></i><span>Payments</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbocm"><i class='bx bx-globe'></i></i><span>Credit Memo</span></a></li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}"><a ui-sref="qbocoa"><i class='bx bx-globe'></i></i><span>COA Managment</span></a></li>
        </ul>
    </div>
</div>