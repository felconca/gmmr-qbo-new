<div class="modal-header">
    <h5 class="modal-title fw-bold d-flex align-items-center">
        Credit Memo No. {{qboInfo.DocNumber}}
    </h5>
    <button type="button" class="btn-close" aria-label="Close" ng-click="closeModal()"></button>
</div>
<div class="modal-body p-0">
    <div class="row mx-0 py-3 px-2 bg-light border-bottom">
        <div class="col-lg-2">
            <div class="modal-info-text">
                <span>Date</span>
                <div>{{qboInfo.TxnDate | date: 'MMM dd, yyyy'}}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="modal-info-text">
                <span>Patient Name</span>
                <div>
                    <strong>{{qboInfo.CustomField[0].StringValue}}</strong>: {{qboInfo.CustomerRef.name}}
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Created By</span>
                <div>
                    {{qboInfo.CustomerMemo.value.split('Created By:')[1] ? qboInfo.CustomerMemo.value.split('Created By:')[1].trim() : '&mdash;'}}
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="modal-info-text">
                <span>Status</span>
                <div>
                    {{qboInfo.CustomField[1].StringValue}}
                </div>
            </div>
        </div>
    </div>
    <div class="row my-3 mx-3">
        <div class="table-cms px-0">
            <table class="table mb-0 align-middle table-striped">
                <thead>
                    <tr>
                        <th>ItemRef</th>
                        <th>Description</th>
                        <th class="text-center" width="8%" nowrap>Tax Code</th>
                        <th class="" width="8%" nowrap>Account</th>
                        <th class="text-end" width="8%" nowrap>Qty</th>
                        <th class="text-end" width="8%" nowrap>Unit Price</th>
                        <th class="text-end" width="8%" nowrap>Amount</th>


                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="line in qboInfo.Line" ng-if="line.DetailType === 'SalesItemLineDetail'">
                        <td class="text-muted">
                            {{line.SalesItemLineDetail.ItemRef ? line.SalesItemLineDetail.ItemRef.name:''}}</span>
                        </td>
                        <td class="text-muted">{{line.Description}}</td>

                        <td class="text-center text-muted">
                            {{line.SalesItemLineDetail.TaxCodeRef.value == 16 ? 'Vat-Ex':line.SalesItemLineDetail.TaxCodeRef.value == 10 ? 'NoVat':'Vat-S'}}
                        </td>
                        <td class="text-muted">{{line.SalesItemLineDetail.ItemAccountRef.name}}</td>
                        <td class="text-end text-muted">{{line.SalesItemLineDetail.Qty | number:2}}</td>
                        <td class="text-end text-muted">{{line.SalesItemLineDetail.UnitPrice | number:2}}</td>
                        <td class="text-end text-muted">{{line.Amount | number:2}}</td>


                    </tr>
                    <tr ng-repeat="line in qboInfo.Line" ng-if="line.DetailType === 'SubTotalLineDetail'">
                        <td class="fw-bold" colspan="6">Subtotal</td>
                        <td class="text-end fw-bold">{{line.Amount | number:2}}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6">Total</th>
                        <th class="text-end">{{qboInfo.TotalAmt | number:2}}</th>
                    </tr>
                    <tr>
                        <th colspan="6">Total Tax</th>
                        <th class="text-end">
                            {{qboInfo.TxnTaxDetail ? (qboInfo.TxnTaxDetail.TotalTax | number:2) : '0.00'}}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="row mx-3 mb-3">
        <div class="col-12">
            <div class="modal-info-text">
                <span>Memo</span>
                <textarea class="form-control" rows="3" readonly style="resize: none; background: #f9f9f9;">{{qboInfo.CustomerMemo.value}}</textarea>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" ng-click="closeModal()">Close</button>
</div>