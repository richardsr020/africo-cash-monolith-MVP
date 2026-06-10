(function exposeAuthToken(windowObject) {
  "use strict";

  const config = windowObject.AfricoConfig;
  let memoryToken = null;

  function storageGet(storage, key) {
    try {
      return storage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function storageSet(storage, key, value) {
    try {
      if (value) {
        storage.setItem(key, value);
      } else {
        storage.removeItem(key);
      }
    } catch (error) {
      memoryToken = value || null;
    }
  }

  function readToken() {
    if (memoryToken) {
      return memoryToken;
    }

    return storageGet(windowObject.sessionStorage, config.tokenStorageKey) || storageGet(windowObject.localStorage, config.tokenStorageKey);
  }

  function writeToken(token, remember = false) {
    memoryToken = token || null;
    storageSet(windowObject.sessionStorage, config.tokenStorageKey, remember ? null : token);
    storageSet(windowObject.localStorage, config.tokenStorageKey, remember ? token : null);
  }

  function clearToken() {
    writeToken(null);
  }

  windowObject.AfricoAuth = Object.freeze({
    getToken: readToken,
    setToken: writeToken,
    clearToken,
  });
})(window);
