/**
 * CASPA School OS – ui.js
 *
 * UI state helpers:
 *   showToast(msg, type)      – Bootstrap toast notification
 *   showLoading(el)           – loading spinner inside an element
 *   showEmpty(el, msg)        – empty-state illustration + message
 *   showError(el, msg)        – error-state message
 *   clearState(el)            – remove any state rendering inside el
 *
 * All functions accept a CSS selector string, a DOM element,
 * or a jQuery object for `el`.
 *
 * Types for showToast: 'success' | 'danger' | 'warning' | 'info'
 */

/* global bootstrap */

const ui = (() => {
  'use strict';

  // -------------------------------------------------------------------------
  // Internal: resolve element to jQuery object
  // -------------------------------------------------------------------------
  function _$el(el) {
    if (typeof el === 'string') return $(el);
    return $(el);
  }

  // -------------------------------------------------------------------------
  // showToast(msg, type = 'info')
  // -------------------------------------------------------------------------
  /**
   * Display a self-dismissing Bootstrap toast in the #toastContainer.
   *
   * @param {string} msg
   * @param {'success'|'danger'|'warning'|'info'} [type='info']
   */
  function showToast(msg, type = 'info') {
    const icons = {
      success: 'bi-check-circle-fill',
      danger:  'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info:    'bi-info-circle-fill',
    };

    const icon  = icons[type] || icons.info;
    const id    = 'toast_' + Date.now();

    const html = `
      <div id="${id}" class="toast caspa-toast-${type} align-items-center border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center gap-2 toast-body">
          <i class="bi ${icon} text-${type === 'danger' ? 'danger' : type}"></i>
          <span>${_escHtml(msg)}</span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>`;

    const $container = $('#toastContainer');
    $container.append(html);

    const toastEl = document.getElementById(id);
    const toast   = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();

    // Clean up DOM after hide
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  // -------------------------------------------------------------------------
  // showLoading(el, msg?)
  // -------------------------------------------------------------------------
  /**
   * Replace the contents of `el` with a centred spinner.
   *
   * @param {string|Element|jQuery} el
   * @param {string} [msg='Loading…']
   */
  function showLoading(el, msg = 'Loading\u2026') {
    _$el(el).html(`
      <div class="caspa-loading" role="status" aria-live="polite">
        <div class="caspa-spinner" aria-hidden="true"></div>
        <span class="visually-hidden">Loading</span>
        <span>${_escHtml(msg)}</span>
      </div>`);
  }

  // -------------------------------------------------------------------------
  // showEmpty(el, msg?)
  // -------------------------------------------------------------------------
  /**
   * Replace the contents of `el` with an empty-state message.
   *
   * @param {string|Element|jQuery} el
   * @param {string} [msg='No records found.']
   */
  function showEmpty(el, msg = 'No records found.') {
    _$el(el).html(`
      <div class="caspa-empty">
        <div class="caspa-empty-icon" aria-hidden="true">📭</div>
        <p class="mb-0">${_escHtml(msg)}</p>
      </div>`);
  }

  // -------------------------------------------------------------------------
  // showError(el, msg?)
  // -------------------------------------------------------------------------
  /**
   * Replace the contents of `el` with an error message.
   *
   * @param {string|Element|jQuery} el
   * @param {string} [msg='Something went wrong. Please try again.']
   */
  function showError(el, msg = 'Something went wrong. Please try again.') {
    _$el(el).html(`
      <div class="caspa-error">
        <div class="caspa-error-icon" aria-hidden="true">⚠️</div>
        <p class="mb-0">${_escHtml(msg)}</p>
      </div>`);
  }

  // -------------------------------------------------------------------------
  // clearState(el)
  // -------------------------------------------------------------------------
  /**
   * Empty an element that was previously populated by a state helper.
   *
   * @param {string|Element|jQuery} el
   */
  function clearState(el) {
    _$el(el).empty();
  }

  // -------------------------------------------------------------------------
  // Internal: minimal HTML escaping for user-supplied strings
  // -------------------------------------------------------------------------
  function _escHtml(str) {
    return String(str)
      .replace(/&/g,  '&amp;')
      .replace(/</g,  '&lt;')
      .replace(/>/g,  '&gt;')
      .replace(/"/g,  '&quot;')
      .replace(/'/g,  '&#039;');
  }

  // -------------------------------------------------------------------------
  // Public API
  // -------------------------------------------------------------------------
  return {
    showToast,
    showLoading,
    showEmpty,
    showError,
    clearState,
  };
})();
