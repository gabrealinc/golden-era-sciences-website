/**
 * Golden Era Sciences — front-end behaviour.
 *
 * Vanilla JS, no dependencies, no build step.
 * Handles: mobile menu, FAQ accordion, newsletter signup.
 */
(function () {
  'use strict';

  /* --- Mobile menu ----------------------------------------------------- */
  function initMobileMenu() {
    var burger = document.querySelector('.ge-burger');
    var menu = document.getElementById('ge-mobile-menu');
    if (!burger || !menu) return;

    burger.addEventListener('click', function () {
      var open = menu.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Close after following a link on mobile.
    menu.addEventListener('click', function (event) {
      if (event.target.closest('a')) {
        menu.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* --- FAQ accordion ---------------------------------------------------- */
  function initFaq() {
    var questions = document.querySelectorAll('.ge-faq__q');
    Array.prototype.forEach.call(questions, function (button) {
      button.addEventListener('click', function () {
        var panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) return;
        var open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel.classList.toggle('is-open', !open);
      });
    });
  }

  /* --- Newsletter signup ------------------------------------------------ */
  function initSubscribe() {
    var forms = document.querySelectorAll('[data-ge-subscribe]');

    Array.prototype.forEach.call(forms, function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();

        var l10n = window.geL10n || {};
        var status = form.querySelector('[data-ge-status]');
        var button = form.querySelector('button[type="submit"]');
        var original = button ? button.textContent : '';

        if (status) {
          status.textContent = '';
          status.className = 'ge-form__status';
        }
        if (button) {
          button.disabled = true;
          button.textContent = l10n.sending || 'Sending…';
        }

        fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        })
          .then(function (response) {
            return response.json().catch(function () {
              return {
                success: false,
                data: { message: l10n.genericError || 'Something went wrong. Please try again.' }
              };
            });
          })
          .then(function (result) {
            var ok = result && result.success;
            var message =
              (result && result.data && result.data.message) ||
              (ok
                ? l10n.subscribed || 'Subscribed.'
                : l10n.genericError || 'Something went wrong. Please try again.');

            if (status) {
              status.textContent = message;
              status.className = 'ge-form__status ' + (ok ? 'is-ok' : 'is-err');
            }
            if (ok) form.reset();
          })
          .catch(function () {
            if (status) {
              status.textContent = l10n.networkError || 'Network error. Please try again.';
              status.className = 'ge-form__status is-err';
            }
          })
          .finally(function () {
            if (button) {
              button.disabled = false;
              button.textContent = original;
            }
          });
      });
    });
  }

  /* --- Product variations ----------------------------------------------
   * Imported products often have attributes with only one valid choice.
   * Select those automatically so the customer only makes real decisions.
   */
  function initSingleChoiceVariations() {
    var forms = document.querySelectorAll('form.variations_form');
    Array.prototype.forEach.call(forms, function (form) {
      var selects = form.querySelectorAll('select');
      Array.prototype.forEach.call(selects, function (select) {
        var choices = Array.prototype.filter.call(select.options, function (option) {
          return option.value !== '' && !option.disabled;
        });
        if (!select.value && choices.length === 1) {
          select.value = choices[0].value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    });
  }

  function initAccessibilityDetails() {
    var galleryTrigger = document.querySelector('.woocommerce-product-gallery__trigger');
    if (galleryTrigger && !galleryTrigger.getAttribute('aria-label')) {
      galleryTrigger.setAttribute('aria-label', 'Open full-size product image');
    }
  }

  /* --- Init -------------------------------------------------------------- */
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    initMobileMenu();
    initFaq();
    initSubscribe();
    initSingleChoiceVariations();
    initAccessibilityDetails();
  });
})();

(function () {
  var search = document.getElementById('ge-report-search');
  if (!search) return;
  search.addEventListener('input', function () {
    var query = search.value.toLowerCase().trim();
    var count = 0;
    var rows = document.querySelectorAll('[data-report-search]');
    for (var i = 0; i < rows.length; i++) {
      rows[i].hidden = rows[i].getAttribute('data-report-search').indexOf(query) === -1;
      if (!rows[i].hidden) count++;
    }
    document.getElementById('ge-report-empty').hidden = count > 0;
  });
}());
(function () {
  var frame = document.querySelector('[data-report-frame]');
  var status = document.getElementById('ge-report-loading');
  if (frame && status) {
    frame.addEventListener('load', function () { status.textContent = 'Report viewer loaded. Download PDF if the document is not visible.'; });
  }
}());
