/**
 * CASPA School OS – api.js
 *
 * Thin jQuery $.ajax wrapper that enforces the API contract:
 *   Success:  { ok: true,  data: ... }
 *   Failure:  { ok: false, error: "..." }
 *
 * Usage:
 *   api.post('/api/auth/login.php', { email, password })
 *     .then(data => { ... })          // data = response.data
 *     .catch(err  => { ... });        // err  = Error with .message
 *
 *   api.get('/api/users/list.php', { role: 'teacher' })
 *     .then(data => { ... });
 *
 * Automatic behaviours:
 *   • 401 → redirect to /index.php?error=session_expired
 *   • Network errors are caught and converted to rejections
 *   • Content-Type: application/x-www-form-urlencoded for POST
 */

/* global $, APP_URL */

const api = (() => {
  'use strict';

  // APP_URL is injected via a <meta> or inline script by layout_header.php.
  // Fallback to window.location.origin for safety.
  const BASE = (typeof APP_URL !== 'undefined')
    ? APP_URL
    : window.location.origin;

  /**
   * Core request function.
   *
   * @param {'GET'|'POST'} method
   * @param {string}       endpoint   – absolute path from root, e.g. '/api/auth/login.php'
   * @param {Object}       payload    – data to send
   * @returns {Promise<any>}          – resolves with response.data, rejects with Error
   */
  function _request(method, endpoint, payload = {}) {
    const url = endpoint.startsWith('http') ? endpoint : BASE + endpoint;

    return new Promise((resolve, reject) => {
      $.ajax({
        url,
        method,
        data: payload,
        dataType: 'json',
        // For file uploads callers should set processData/contentType themselves
        // and pass the FormData directly.
      })
        .done((response) => {
          if (response && response.ok === true) {
            resolve(response.data ?? null);
          } else {
            const msg = (response && response.error) ? response.error : 'An unexpected error occurred.';
            reject(new Error(msg));
          }
        })
        .fail((jqXHR) => {
          // 401 – not authenticated → redirect to login
          if (jqXHR.status === 401) {
            window.location.href = BASE + '/index.php?error=session_expired';
            return;
          }

          // Try to parse a JSON error body from the server
          let msg = 'Network or server error. Please try again.';
          try {
            const json = JSON.parse(jqXHR.responseText);
            if (json && json.error) {
              msg = json.error;
            }
          } catch (_) {
            // Not JSON – use default message
          }

          reject(new Error(msg));
        });
    });
  }

  /**
   * GET request.
   * @param {string} endpoint
   * @param {Object} [params={}]  – serialised as query-string
   * @returns {Promise<any>}
   */
  function get(endpoint, params = {}) {
    return _request('GET', endpoint, params);
  }

  /**
   * POST request.
   * @param {string} endpoint
   * @param {Object|FormData} [payload={}]
   * @returns {Promise<any>}
   */
  function post(endpoint, payload = {}) {
    return _request('POST', endpoint, payload);
  }

  /**
   * POST with FormData (e.g. file uploads).
   * Caller builds the FormData; this sets processData/contentType correctly.
   *
   * @param {string}   endpoint
   * @param {FormData} formData
   * @returns {Promise<any>}
   */
  function upload(endpoint, formData) {
    const url = endpoint.startsWith('http') ? endpoint : BASE + endpoint;

    return new Promise((resolve, reject) => {
      $.ajax({
        url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
      })
        .done((response) => {
          if (response && response.ok === true) {
            resolve(response.data ?? null);
          } else {
            reject(new Error((response && response.error) || 'Upload failed.'));
          }
        })
        .fail((jqXHR) => {
          if (jqXHR.status === 401) {
            window.location.href = BASE + '/index.php?error=session_expired';
            return;
          }
          reject(new Error('Upload error. Please try again.'));
        });
    });
  }

  return { get, post, upload };
})();
