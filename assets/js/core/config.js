(function exposeConfig(windowObject) {
  "use strict";

  windowObject.AfricoConfig = Object.freeze({
    apiBaseUrl: windowObject.AFRICO_API_BASE_URL || "/api",
    requestTimeout: 15000,
    tokenStorageKey: "africo_cash_access_token",
    themeStorageKey: "africo_cash_theme",
  });
})(window);
