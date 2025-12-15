<div class="modal-header">
    <h5 class="modal-title fw-bold d-flex align-items-center">Invoice No. {{invoiceId}} <span class="ms-3 badge-dark" ng-if="invoiceInfo.sent_status == 1">Booked</span></h5>
    <button type="button" class="btn-close" aria-label="Close" ng-click="closeInvoiceModal()"></button>
</div>
<div class="modal-body p-0">
    <div class="row mx-0 py-3 px-2 bg-light border-bottom">
        <div class="col-lg-2">
            <div class="modal-info-text">
                <span>Date</span>
                <div>{{invoiceInfo.trandate | date: 'MMM dd, yyyy'}}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="modal-info-text">
                <span>Patient Name</span>
                <div>
                    <strong>{{invoiceInfo.pxid}}</strong>: {{invoiceInfo.pxfname}} {{invoiceInfo.pxmname}}
                    {{invoiceInfo.pxlname}} {{invoiceInfo.suffix}}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Created By</span>
                <div>
                    {{invoiceInfo.ufname}} {{invoiceInfo.ulname}}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Invoice Status</span>
                <div>
                    {{invoiceInfo.transtatus}}
                </div>
            </div>
        </div>
    </div>
    <div class="row my-3 mx-3">
        <div class="table-cms px-0">
            <table class="table mb-0 align-middle table-striped">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end" width="10%">Cost</th>
                        <th class="text-end" width="10%">Price</th>
                        <th class="text-end" width="10%">Quantity</th>
                        <th class="text-end" width="10%">Gross Amt</th>
                        <th class="text-end" width="10%">Line Disc.</th>
                        <th class="text-end" width="10%">SR/PWD Disc.</th>
                        <th class="text-end" width="10%">Vat Amt</th>
                        <th class="text-end" width="10%">Net Of Vat</th>
                        <th class="text-end" width="10%">Net Amt</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="list in invoiceDetails">
                        <td>{{list.descriptions}}</td>
                        <td class="text-end">{{list.cost | number:2}}</td>
                        <td class="text-end">{{list.price | number:2}}</td>
                        <td class="text-end">{{list.qty | number:2}}</td>
                        <td class="text-end">{{list.gross | number:2}}</td>
                        <td class="text-end">{{list.ldiscount | number:2}}</td>
                        <td class="text-end">{{list.discount | number:2}}</td>
                        <td class="text-end">{{list.vat | number:2}}</td>
                        <td class="text-end">{{list.netofvat | number:2}}</td>
                        <td class="text-end">{{list.netamount | number:2}}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="fw-bold">Total</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'cost') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'price') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'qty') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'gross') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'ldiscount') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'discount') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'vat') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'netofvat') | number:2}}</td>
                        <td class="fw-bold text-end">{{getTotal(invoiceDetails, 'netamount') | number:2}}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" ng-click="closeInvoiceModal()">Close</button>
    <button type="button" ng-if="invoiceInfo.sent_status == 0" class="btn btn-theme-dark" ng-click="handleBookInvoice(invoiceInfo)">
        Book Invoice
    </button>
</div>