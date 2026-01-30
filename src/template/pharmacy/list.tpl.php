 <div class="d-flex justify-content-between">
     <div>
         <breadcrumbs></breadcrumbs>
         <h4 class="fw-semibold text-dark mb-0">Pharmacy Sales</h4>
     </div>
     <div class="d-flex justify-content-end align-items-center">
         <div class="d-flex align-items-center" style="gap:10px">
             <div class="text-white d-flex align-items-center">
                 <span class="text-dark" ng-class="{'text-muted':selectedItems.length <= 0}">{{selectedItems.length}} selected</span>
             </div>
             <hr class="vr">
             <div class="d-flex align-items-center" style="gap:6px">
                 <button class="btn btn-theme-dark" ng-click="handleBookItems(selectedItems)" ng-disabled="selectedItems.length <= 0 || isSending">
                     <i class="ph-bold ph-share-fat me-1"></i>
                     Book/Re-Book
                 </button>
                 <button class="btn btn-danger text-white" ng-click="handleUnBookedItems(selectedItems)" ng-disabled="selectedItems.length <= 0 || isSending">
                     <i class="ph-bold ph-trash text-white me-1"></i>Unbooked
                 </button>
                 <hr class="vr" ng-if="selectedItems.length > 0 && filtered.status == 9">
                 <button class="btn btn-theme-dark text-white" ng-click="linkInvoiceToPayment(selectedItems)" ng-disabled="isSending" ng-if="selectedItems.length > 0 && filtered.status == 9">
                     <i class="ph-bold ph-link-simple me-1"></i>Link To Payment
                 </button>
             </div>
         </div>
     </div>
 </div>
 <!-- filter -->
 <div class="d-flex align-items-end justify-content-between mt-3" style="gap:6px">
     <div class="w-50">
         <div class="input-form-grp bg-white" ng-disabled="isFiltering || isSending" style="width: 300px;">
             <input type="text" placeholder="Search" ng-model="search" ng-model-options="{ debounce: 500 }" ng-disabled="isFiltering || isSending">
             <i class='bx bx-search-alt-2'></i>
         </div>
     </div>
     <div class="w-50">
         <div class="d-flex align-items-end justify-content-end" style="gap: 6px;">
             <div class="filter-input" style="width: 145px;">
                 <span>Status</span>
                 <select class="input-form" ng-model="filtered.isBooked" ng-disabled="isFiltering || isSending">
                     <option ng-value="-1">All Status</option>
                     <option ng-value="0">Not Booked</option>
                     <option ng-value="1">Booked</option>
                     <option ng-value="2">Modified</option>
                     <option ng-value="4">Failed</option>
                     <option ng-value="5">Unbooked</option>
                 </select>
             </div>
             <div class="filter-input" style="width: 260px;">

                 <span>GMMR Status</span>
                 <select class="input-form" ng-model="filtered.status" ng-disabled="isFiltering || isSending">
                     <option ng-value="0">All GMMR Status</option>
                     <option ng-value="9">PHARMA/OPD (walk in)</option>
                     <option ng-value="10">PHARMA SALES (in patient)</option>
                     <option ng-value="12">PHARMA SALES (Charge To)</option>
                 </select>
             </div>
             <div class="filter-input" style="width: 150px;">
                 <span>Date From:</span>
                 <input type="date" class="input-form" ng-model="filtered.startDate" date-input ng-disabled="isFiltering || isSending">
             </div>
             <div class="filter-input" style="width: 150px;">
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
 <div class="table-cms mt-2" style="height: calc(100vh - 280px);">

     <table class="table align-middle mb-0 table-striped">
         <thead class="position-sticky top-0" id="table-print">
             <tr>
                 <th width="1%" class="text-center" style="font-size: 13px;">
                     <input ng-disabled="isFiltering || isSending" type="checkbox" class="form-check-input" ng-model="selectAll" ng-click="handleSelectAllItems(searched)">
                 </th>
                 <th width="1%" nowrap>Date</th>
                 <th width="1%" nowrap>Ref. No.</th>
                 <th nowrap>Patients</th>
                 <th width="10%">Created By</th>
                 <th width="10%">GMMR Status</th>
                 <th class="text-center" width="10%">Status</th>
                 <th class="text-center" width="10%">Payment Linked</th>
                 <th class="text-start" width="10%" nowrap>Last Booked</th>
                 <th class="text-end" width="5%" nowrap>Amount</th>
                 <th class="text-end" width="5%" nowrap>Booked Amt</th>
                 <th class="text-end" width="5%" nowrap>Updated Amt</th>
                 <th class="text-center" width="5%">Actions</th>
             </tr>
         </thead>
         <tbody>
             <tr ng-repeat="items in searched = (invoicesList | filter: search) | limitTo:itemsPerPage:itemsPerPage*(currentPage-1) track by $index">
                 <td>
                     <input type="checkbox" ng-disabled="isLoading == true || isSending == true" class="form-check-input"
                         ng-model="items.selected" ng-click="handleSelectItem(items)">
                 </td>
                 <td nowrap>{{items.trandate | date:"MM/dd/yy"}}</td>
                 <td nowrap>
                     <a class="link-anchor" ng-click="showInvoiceModal(items.tranid)" ng-disabled="isSending">{{items.tranid}}</a>
                 </td>
                 <td nowrap>
                     <span ng-if="items.pxid == 0">- Walk-In Patient -</span>
                     <div class="d-flex align-items-center" ng-if="items.pxid > 0">
                         <a tooltip="Mapped to qbo customer" flow="right">
                             <i class="ph-fill ph-seal-check text-success me-2" ng-if="items.qbopx > 0"></i>
                         </a>
                         {{items.fname}} {{items.mname.substring(0, 1)}}. {{items.lname}} {{items.suffix}}
                     </div>
                 </td>
                 <td nowrap>{{items.ufname.substring(0, 1)}}. {{items.ulname}}</td>
                 <td nowrap>{{items.transtatus}}</td>
                 <td nowrap class="text-center">
                     <span class="status {{ sentStatusClass(items.sent_status) }}">{{ sentStatus(items.sent_status) }}</span>
                 </td>
                 <td class="text-center">{{items.link}}</td>
                 <td nowrap>{{items.sent_date?toISO(items.sent_date):'—' | date:'MMM dd, yyyy hh:mm a'}}</td>
                 <td nowrap class="text-end">{{items.netamount | number:2}}</td>
                 <td nowrap class="text-end">{{items.booked_amt | number:2}}</td>
                 <td nowrap class="text-end">{{items.updated_amt | number:2}}</td>
                 <td class="text-center">
                     <div class="dropdown">
                         <button ng-disabled="selectedItems.length > 0 || isSending" class="btn btn-sm btn-theme-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                             Actions
                         </button>
                         <ul class="dropdown-menu btn-action">
                             <li>
                                 <button class="dropdown-item py-2 d-flex align-items-center" type="button" ng-click="handleBookItems([items])">
                                     <i class="ph-bold ph-share-fat me-2"></i>Book/Re-Book
                                 </button>
                             </li>
                             <li>
                                 <button class="dropdown-item py-2 d-flex align-items-center" type="button" ng-click="linkInvoiceToPayment([items])"
                                     ng-if="items.sent_id > 0 && items.tstatus == 9"
                                     ng-disabled="items.pay_id > 0">
                                     <i class="ph-bold ph-link-simple me-2"></i>Link Payment
                                 </button>
                             </li>
                             <li>
                                 <button class="dropdown-item py-2 d-flex align-items-center" type="button" ng-click="showInvoiceModal(items.tranid)">
                                     <i class="ph-bold ph-pencil-line me-2"></i>GMMR Details
                                 </button>
                             </li>
                             <li>
                                 <button class="dropdown-item py-2 d-flex align-items-center" type="button" ng-click="findInvoice(items.sent_id)" ng-disabled="items.sent_id == 0">
                                     <i class="ph-bold ph-notebook me-2"></i>QBO Details
                                 </button>
                             </li>
                             <li>
                                 <hr class="dropdown-divider">
                             </li>
                             <li>
                                 <button class="dropdown-item py-2 d-flex align-items-center text-danger delete" type="button" ng-click="handleUnBookedItems([items])" ng-disabled="items.sent_id == 0">
                                     <i class="ph-bold ph-trash me-2 text-danger"></i>Unbooked
                                 </button>
                             </li>
                             </li>
                         </ul>
                     </div>
                 </td>
             </tr>
         </tbody>
         <tfoot class="position-sticky bottom-0" ng-hide="isFiltering || isSending">
             <tr>
                 <td colspan="9" class="fw-bold">Total</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'netamount') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'booked_amt') | number:2}}</td>
                 <td class="fw-bold text-end">{{getTotal(searched, 'updated_amt') | number:2}}</td>
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
     <ul style="margin-bottom: 0 !important;" uib-pagination total-items="searched.length" num-pages="numPages" items-per-page="itemsPerPage" ng-model="currentPage" max-size="5" boundary-link-numbers="true" ng-change="changePage(invoicesList)"></ul>
 </div>