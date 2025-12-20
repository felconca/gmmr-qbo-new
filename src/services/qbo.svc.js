angular.module("app").factory("$qbo", function () {
  let production = true;
  // transactions status map
  const statusMap = {
    1: "NP-", // Non-Pharma
    2: "PH-", // Pharma
    3: "PF-", // professional fee
    4: "AD-", // advances
    5: "PY-", // payment
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
    16: "CM-", // GMMR Credit Memo only, user also for journal credit memo
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

  return {
    status: (sts) => statusMap[sts] || "N/A",
    vat: () => taxConfig.vat,
    nonvat: () => taxConfig.nonvat,
    discount: () => taxConfig.discount,
    included: () => taxConfig.included,
    excluded: () => taxConfig.excluded,
    outscope: () => taxConfig.outscope,
    radiosales: (code, itemid) => {
      const match = radioMap[code];
      return match ? (production ? match.prod : match.dev) : itemid;
    },
  };
});
