angular.module("app").factory("$qbo", function () {
  let production = true;
  // transactions status map
  const statusMap = {
    1: "NP-", // Non-Pharma
    2: "PH-", // Pharma
    3: "PF-", // professional fee
    4: "AD-", // advances
    5: "OR-", // payment
    6: "GM-", // GMMR for inventory or journal entry
    7: "GPH-", // GMMR for inventory or journal entry pharma
    8: "GNP-", // GMMR for inventory or journal entry non-pharma
    9: "GCM-", // GMMR Credit Memo Inventory
    10: "PHIC-", // GMMR Philhealth
    11: "HMO-", // GMMR HMO
    12: "HCP-", // GMMR HCP
    13: "PYW-", // GMMR Walkin_in payment
    14: "CPH-", // GMMR Pharma Credit Memo
    15: "CNP-", // GMMR Non Pharma Credit Memo
    16: "CM-", // GMMR Credit Memo only, user also for journal, credit memo
    17: "DR-", // GMMR Credit Memo only, user also for journal, credit memo
  };
  // vat/taxation map
  const taxConfig = {
    vat: production ? "15" : "13",
    nonvat: production ? "16" : "14",
    discount: "10",
    included: "TaxInclusive",
    excluded: "TaxExclusive",
    outscope: "NotApplicable",
  };
  // radiology map
  const radioMap = {
    13: { prod: 12, dev: 9 },
    3: { prod: 11, dev: 9 },
    15: { prod: 13, dev: 9 },
    31: { prod: 14, dev: 9 },
  };
  function methodref(ref = 0) {
    switch (ref) {
      case 0: // cash
        return 1;
      case 4: // check
        return 2;
      case 2: // credit card
        return 3;
      case 3: // debit card
        return 4;
      case 19: // gcash
        return 5;
      case 20: // maya
        return 6;
      case 22: // bank
        return 7;
      default:
        return 1;
    }
  }

  function walkMethod(ref) {
    // method (1-cash, 2-check, 3-credit, 4-direct debit, 5-gcash, 6-maya, 7-bank transfer)
    switch (ref) {
      case 0: // cash
        return 1;
      case 3: //check
        return 2;
      case 2: // credit card
        return 3;
      case 12: // debit card
        return 4;
      case 19: // gcash
        return 5;
      case 20: // maya
        return 6;
      case 22: //bank
        return 7;
      default:
        return 1;
    }
    return method;
  }
  function depositref(typeid = 0) {
    if (typeid == 2) {
      return "109";
    } else {
      return "76";
    }
  }
  return {
    status: (sts) => statusMap[sts] || "N/A",
    vat: () => taxConfig.vat,
    nonvat: () => taxConfig.nonvat,
    discount: () => taxConfig.discount,
    included: () => taxConfig.included,
    excluded: () => taxConfig.excluded,
    outscope: () => taxConfig.outscope,
    depositref: depositref,
    methodref: methodref,
    walkMethod: walkMethod,
    radiosales: (code, itemid) => {
      const match = radioMap[code];
      return match ? (production ? match.prod : match.dev) : itemid;
    },
  };
});
