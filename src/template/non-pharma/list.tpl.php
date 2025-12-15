 <!-- filter -->
 <div class="d-flex align-items-end justify-content-between mt-2" style="gap:6px">
     <div class="w-50">
         <div class="input-form-grp bg-white w-50" ng-disabled="isFiltering || isSending">
             <input type="text" placeholder="Search" ng-model="search" ng-model-options="{ debounce: 500 }" ng-disabled="isFiltering || isSending">
             <i class='bx bx-search-alt-2'></i>
         </div>
     </div>
     <div class="">
         <div class="d-flex align-items-end justify-content-end" style="gap: 6px;">
             <div class="filter-input w-25">
                 <span>Booked Status</span>
                 <select class="input-form" ng-model="filtered.isBooked" ng-disabled="isFiltering || isSending">
                     <option ng-value="-1">All Status</option>
                     <option ng-value="0">UnBooked</option>
                     <option ng-value="1">Booked</option>
                 </select>
             </div>
             <div class="filter-input w-50">

                 <span>Status</span>
                 <select class="input-form" ng-model="filtered.status" ng-disabled="isFiltering || isSending">
                     <option ng-value="0">All Status</option>
                     <option ng-value="1">Radiology Sales</option>
                     <option ng-value="4">Med-OPD</option>
                     <option ng-value="5">Med-InPatient</option>
                     <option ng-value="6">Laboratory</option>
                     <option ng-value="16">General Sales</option>
                     <option ng-value="21">PT Charges</option>
                 </select>
             </div>
             <div class="filter-input w-25">
                 <span>Date From:</span>
                 <input type="date" class="input-form" ng-model="filtered.startDate" date-input ng-disabled="isFiltering || isSending">
             </div>
             <div class="filter-input w-25">
                 <span>Date To:</span>
                 <input type="date" class="input-form" ng-model="filtered.endDate" date-input ng-disabled="isFiltering || isSending">
             </div>
             <button class="btn btn-theme-dark" ng-click="handleFilter(filtered)"
                 ng-disabled="isFiltering || isSending">
                 <img ng-if="isFiltering" style="width:20px; height:20px" src="src/assets/img/loader_24x.svg" alt="">
                 <span ng-if="!isFiltering" class="text-white">GO</span>
             </button>
         </div>
     </div>
 </div>
 <!-- content table -->
 <div class="table-cms table-responsive mt-2" style="height: calc(100vh - 280px);">
     <table class="table align-middle mb-0 table-striped">
         <thead class="position-sticky top-0" id="table-print">
             <tr>
                 <th width="1%" class="text-center" style="font-size: 13px;">
                     <div ng-if="isAllBookInPage || searched.length == 0" class="table-icon"><i class='bx bxs-bookmark-alt'></i></div>
                     <input ng-if="!isAllBookInPage && searched.length > 0" ng-disabled="isLoading == true || isSending == true" type="checkbox" class="form-check-input" ng-model="select_all" ng-click="handleCheckAllInvoices(searched)">
                 </th>
                 <th width="1%" nowrap>Date</th>
                 <th width="1%" nowrap>Ref. No.</th>
                 <th nowrap>Patients</th>
                 <th width="10%">Created By</th>
                 <th width="10%">Status</th>
                 <th class="text-end" width="8%">Gross</th>
                 <th class="text-end" width="8%">Line Disc.</th>
                 <th class="text-end" width="8%">SR/PWD Disc.</th>
                 <th class="text-end" width="8%">Vat Amt</th>
                 <th class="text-end" width="8%">Net Of Vat</th>
                 <th class="text-end" width="8%">Amount</th>
                 <th class="text-center" width="5%">Actions</th>
             </tr>
         </thead>
         <tbody>
             <tr ng-repeat="items in searched = (invoicesList | filter: search) | limitTo:itemsPerPage:itemsPerPage*(currentPage-1) track by $index">
                 <td>
                     <div ng-if="items.sent_status == 1" class="table-icon"><i class='bx bxs-bookmark-alt'></i></div>
                     <input ng-if="items.sent_status == 0" type="checkbox" ng-disabled="isLoading == true || isSending == true" class="form-check-input" ng-model="items.selected" ng-click="handleSelectInvoice(items)">
                 </td>
                 <td nowrap>{{items.trandate | date:"MM/dd/yy"}}</td>
                 <td nowrap>
                     <a class="link-anchor" ng-click="showInvoiceModal(items.tranid)">{{items.tranid}}</a>
                 </td>
                 <td nowrap>{{items.pxfname}} {{items.pxmname.substring(0, 1)}}. {{items.pxlname}} {{items.suffix}}</td>
                 <td nowrap>{{items.ufname.substring(0, 1)}}. {{items.ulname}}</td>
                 <td nowrap>{{items.transtatus}}</td>
                 <td nowrap class="text-end">{{items.gross | number:2}}</td>
                 <td nowrap class="text-end">{{items.ldiscount | number:2}}</td>
                 <td nowrap class="text-end">{{items.discount | number:2}}</td>
                 <td nowrap class="text-end">{{items.vat | number:2}}</td>
                 <td nowrap class="text-end">{{items.netofvat | number:2}}</td>
                 <td nowrap class="text-end">{{items.netamount | number:2}}</td>
                 <td class="text-center">
                     <span ng-if="items.sent_status == 1" class="badge-dark">Booked</span>
                     <button ng-if="items.sent_status == 0" class="btn btn-sm btn-theme-green w-100" ng-click="handleBookInvoice(items)" ng-disabled="isSending == true">
                         <img src="src/public/assets/images/loader_10x.svg" ng-if="isSending == true" alt="">{{isSending == true ? "Booking..." : "Book"}}
                     </button>
                 </td>
             </tr>
         </tbody>
         <tfoot class="position-sticky bottom-0">
             <tr>
                 <td colspan="6" class="fw-bold">Total</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'gross') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'ldiscount') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'discount') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'vat') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'netofvat') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'netamount') | number:2}}</td>
                 <td></td>
             </tr>
         </tfoot>
     </table>
 </div>
 <!-- pagination -->
 <div class="d-flex align-items-center justify-content-between pt-3">
     <span>
         Showing {{
        searched.length > 0 ? formatNumber((currentPage - 1) * itemsPerPage + 1) : 0
        }} to {{
            searched.length > 0 ? formatNumber(Math.min(currentPage * itemsPerPage, searched.length)) : 0
        }} of {{formatNumber(searched.length)}} entries
     </span>
     <ul style="margin-bottom: 0 !important;" uib-pagination total-items="searched.length" num-pages="numPages" items-per-page="itemsPerPage" ng-model="currentPage" max-size="5" boundary-link-numbers="true" ng-change="changePage()"></ul>
 </div>