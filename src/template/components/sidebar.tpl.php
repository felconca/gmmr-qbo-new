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
            <li ui-sref-active="active"><a ui-sref="app.home" ui-sref-opts="{reload: true}"><i class="ph-fill ph-squares-four"></i><span>Dashboard</span></a></li>
        </ul>
        <div class="text-light small my-2 px-2">
            Hospital Sales
        </div>
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.non-pharma" ui-sref-opts="{reload: true}"><i class="ph-fill ph-hospital"></i><span>NonPharma Sales</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.pharmacy" ui-sref-opts="{reload: true}"><i class="ph-fill ph-pill"></i><span>Pharmacy Sales</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.professional-fee" ui-sref-opts="{reload: true}"><i class="ph-fill ph-stethoscope"></i><span>Professional Fees</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.nonpharma-rn" ui-sref-opts="{reload: true}"><i class="ph-fill ph-signpost"></i><span>NonPharma Returns</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.pharmacy-rn" ui-sref-opts="{reload: true}"><i class="ph-fill ph-signpost"></i><span>Pharmacy Returns</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.credit-memo" ui-sref-opts="{reload: true}"><i class="ph-fill ph-note"></i><span>Credit Memo</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.debit-memo" ui-sref-opts="{reload: true}"><i class="ph-fill ph-list-plus"></i><span>Debit Memo</span></a></li>
        </ul>
        <div class="text-light small my-2 px-2">
            Advances To
        </div>
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.advancesto" ui-sref-opts="{reload: true}"><i class="ph-fill ph-person"></i><span>Employees</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.advancesto" ui-sref-opts="{reload: true}"><i class="ph-fill ph-building"></i><span>Affiliated</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.claims" ui-sref-opts="{reload: true}"><i class="ph-fill ph-address-book"></i><span>Claims</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.assistant" ui-sref-opts="{reload: true}"><i class="ph-fill ph-hand-heart"></i><span>Assistant</span></a></li>
        </ul>

        <div class="text-light small my-2 px-2">
            Payments
        </div>
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.payments" ui-sref-opts="{reload: true}"><i class="ph-fill ph-wallet"></i><span>Payments</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.pos-payments" ui-sref-opts="{reload: true}"><i class="ph-fill ph-cash-register"></i><span>POS Payments</span></a></li>
        </ul>

        <div class="text-light small my-2 px-2">
            Inventory
        </div>
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.nonpharma-inventory" ui-sref-opts="{reload: true}"><i class="ph-fill ph-package"></i><span>NonPharma Inventory</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.pharmacy-inventory" ui-sref-opts="{reload: true}"><i class="ph-fill ph-package"></i><span>Pharamcy Inventory</span></a></li>
        </ul>

        <div class="text-light small my-2 px-2">
            Links & Connections
        </div>
        <ul>
            <li ui-sref-active="active"><a ui-sref="app.departments" ui-sref-opts="{reload: true}"><i class="ph-bold ph-link-simple-horizontal"></i><span>Departments</span></a></li>
            <li ui-sref-active="active"><a ui-sref="app.advances-link" ui-sref-opts="{reload: true}"><i class="ph-bold ph-link-simple-horizontal"></i><span>Advances Linking</span></a></li>
        </ul>
        <div class="text-light small my-2 px-2">
            QuickBooks Helpers
        </div>

        <ul>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.items">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Items</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Invoices</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Inventory</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Advances</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Claims & Assistant</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Payments</span>
                </a>
            </li>
            <li ui-sref-active="active" ui-sref-opts="{reload: true}">
                <a ui-sref="app.quickbooks.invoices">
                    <img src="src/assets/img/quickbooks-icon.svg" class="me-2">
                    <span>Credit & Debit</span>
                </a>
            </li>
        </ul>
    </div>
</div>