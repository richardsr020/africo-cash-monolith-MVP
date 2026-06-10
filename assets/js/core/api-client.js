(function exposeApiClient(windowObject) {
  "use strict";

  const config = windowObject.AfricoConfig;
  const auth = windowObject.AfricoAuth;

  function createUnavailableClient() {
    async function fail() {
      throw new Error("Service momentanément indisponible.");
    }

    return {
      get: fail,
      post: fail,
      put: fail,
      patch: fail,
      delete: fail,
    };
  }

  if (!windowObject.axios) {
    windowObject.AfricoApi = createUnavailableClient();
    return;
  }

  const client = windowObject.axios.create({
    baseURL: config.apiBaseUrl,
    timeout: config.requestTimeout,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  client.interceptors.request.use((requestConfig) => {
    const token = auth.getToken();

    if (token) {
      requestConfig.headers.Authorization = `Bearer ${token}`;
    }

    return requestConfig;
  });

  client.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response && error.response.status === 401) {
        auth.clearToken();
      }

      return Promise.reject(error);
    },
  );

  windowObject.AfricoApi = client;
})(window);
