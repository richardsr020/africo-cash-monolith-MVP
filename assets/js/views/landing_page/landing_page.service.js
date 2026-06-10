(function exposeLandingService(windowObject) {
  "use strict";

  const api = windowObject.AfricoApi;

  async function requestEarlyAccess(payload) {
    const response = await api.post("/auth/register-intent", payload);
    return response.data;
  }

  windowObject.AfricoLandingService = Object.freeze({
    requestEarlyAccess,
  });
})(window);
