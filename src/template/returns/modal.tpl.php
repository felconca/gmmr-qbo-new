<div class="modal-header">
    <h5 class="modal-title fw-bold d-flex align-items-center">Sales Return No. {{returnsInfo.cmid}} <span class="ms-3 badge-dark" ng-if="returnsInfo.sent_status == 1">Booked</span></h5>
    <button type="button" class="btn-close" aria-label="Close" ng-click="closeCreditModal()"></button>
</div>
<div class="modal-body p-0">
    <div class="row mx-0 py-3 px-2 bg-light border-bottom">
        <div class="col-lg-2">
            <div class="modal-info-text">
                <span>Date</span>
                <div>{{returnsInfo.trandate | date: 'MMM dd, yyyy'}}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="modal-info-text">
                <span>Patient Name</span>
                <div ng-if="returnsInfo.pxid > 0">
                    <strong>{{returnsInfo.pxid}}</strong>: {{returnsInfo.pxfname}} {{returnsInfo.pxmname}}
                    {{returnsInfo.pxlname}} {{returnsInfo.suffix}}
                </div>
                <div ng-if="returnsInfo.pxid == 0">
                    Walk-In Patient
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Created By</span>
                <div>
                    {{returnsInfo.ufname}} {{returnsInfo.ulname}}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Invoice Status</span>
                <div>
                    <!-- {{returnsInfo.transtatus}} -->
                    Sales Return
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
                    <tr ng-repeat="list in returnsDetails">
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
                        <th class="text-end">{{getTotal(returnsDetails, 'cost') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'price') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'qty') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'gross') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'ldiscount') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'discount') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'vat') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'netofvat') | number:2}}</th>
                        <th class="text-end">{{getTotal(returnsDetails, 'netamount') | number:2}}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" ng-click="closeCreditModal()">Close</button>
</div>