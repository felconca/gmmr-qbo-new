<div class="modal-header">
    <h5 class="modal-title fw-bold d-flex align-items-center">Credit Memo No. {{creditMemoInfo.cmid}} <span class="ms-3 badge-dark" ng-if="creditMemoInfo.sent_status == 1">Booked</span></h5>
    <button type="button" class="btn-close" aria-label="Close" ng-click="closeCreditModal()"></button>
</div>
<div class="modal-body p-0">
    <div class="row mx-0 py-3 px-2 bg-light border-bottom">
        <div class="col-lg-2">
            <div class="modal-info-text">
                <span>Date</span>
                <div>{{creditMemoInfo.trandate | date: 'MMM dd, yyyy'}}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="modal-info-text">
                <span>Patient Name</span>
                <div>
                    <strong>{{creditMemoInfo.pxid}}</strong>: {{creditMemoInfo.pxfname}} {{creditMemoInfo.pxmname}}
                    {{creditMemoInfo.pxlname}} {{creditMemoInfo.suffix}}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Created By</span>
                <div>
                    {{creditMemoInfo.ufname}} {{creditMemoInfo.ulname}}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Invoice Status</span>
                <div>
                    {{creditMemoInfo.transtatus}}
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
                        <th class="text-end" width="8%" nowrap>Cost</th>
                        <th class="text-end" width="8%" nowrap>Price</th>
                        <th class="text-end" width="8%" nowrap>Qty</th>
                        <th class="text-end" width="8%" nowrap>Gross Amt</th>
                        <th class="text-end" width="8%" nowrap>Line</th>
                        <th class="text-end" width="8%" nowrap>SR/PWD.</th>
                        <th class="text-end" width="8%" nowrap>Vat Amt</th>
                        <th class="text-end" width="8%" nowrap>Net Of Vat</th>
                        <th class="text-end" width="8%" nowrap>Net Amt</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="list in creditMemoDetails">
                        <td class="text-muted">{{list.descriptions}}</td>
                        <td class="text-end text-muted">{{list.cost | number:2}}</td>
                        <td class="text-end text-muted">{{list.price | number:2}}</td>
                        <td class="text-end text-muted">{{list.qty | number:2}}</td>
                        <td class="text-end text-muted">{{list.gross | number:2}}</td>
                        <td class="text-end text-muted">{{list.ldiscount | number:2}}</td>
                        <td class="text-end text-muted">{{list.discount | number:2}}</td>
                        <td class="text-end text-muted">{{list.vat | number:2}}</td>
                        <td class="text-end text-muted">{{list.netofvat | number:2}}</td>
                        <td class="text-end text-muted">{{list.netamount | number:2}}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'cost') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'price') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'qty') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'gross') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'ldiscount') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'discount') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'vat') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'netofvat') | number:2}}</th>
                        <th class="text-end">{{getTotal(creditMemoDetails, 'netamount') | number:2}}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" ng-click="closeCreditModal()">Close</button>

</div>