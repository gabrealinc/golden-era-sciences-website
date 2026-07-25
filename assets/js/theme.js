/**
 * Golden Era Sciences — front-end behaviour.
 *
 * Vanilla JS, no dependencies, no build step.
 * Handles: age gate, mobile menu, FAQ accordion, newsletter signup.
 */
(function () {
  'use strict';

  /* --- Age gate ---------------------------------------------------------
   * Stored in localStorage rather than a cookie so it never varies the
   * server response, which keeps page caching intact.
   */
  function initAgeGate() {
    var gate = document.getElementById('ge-agegate');
    if (!gate) return;

    var KEY = 'ge-age-verified';
    var verified = false;

    try {
      verified = window.localStorage.getItem(KEY) === 'yes';
    } catch (e) {
      // Private browsing or storage disabled: show the gate, do not trap them.
      verified = false;
    }

    if (verified) return;

    var accept = gate.querySelector('[data-ge-agegate="accept"]');
    var decline = gate.querySelector('[data-ge-agegate="decline"]');
    var message = gate.querySelector('[data-ge-agegate-message]');
    var regions = [];

    // The gate is an opaque full-screen overlay. Without this, Tab walks
    // straight past it into the nav and product links underneath, which are
    // invisible but still focusable and clickable.
    function setInert(on) {
      regions = regions.length
        ? regions
        : Array.prototype.slice.call(
            document.querySelectorAll('body > header, body > main, body > footer, body > .ge-header')
          );
      regions.forEach(function (el) {
        if (on) {
          el.setAttribute('inert', '');
          el.setAttribute('aria-hidden', 'true');
        } else {
          el.removeAttribute('inert');
          el.removeAttribute('aria-hidden');
        }
      });
    }

    // Fallback for browsers without inert: cycle Tab between the two buttons.
    function trapTab(event) {
      if (event.key !== 'Tab' && event.keyCode !== 9) return;
      var focusable = [accept, decline].filter(function (el) {
        return el && !el.disabled;
      });
      if (!focusable.length) return;
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    gate.hidden = false;
    document.body.classList.add('ge-locked');
    setInert(true);
    document.addEventListener('keydown', trapTab);

    if (accept) {
      accept.focus();
      accept.addEventListener('click', function () {
        try {
          window.localStorage.setItem(KEY, 'yes');
        } catch (e) {
          /* ignore */
        }
        gate.hidden = true;
        document.body.classList.remove('ge-locked');
        setInert(false);
        document.removeEventListener('keydown', trapTab);
      });
    }

    if (decline) {
      decline.addEventListener('click', function () {
        // Send them away rather than disabling both buttons, which used to
        // strand the visitor behind an opaque overlay with no way out.
        if (message) message.hidden = false;
        var exit =
          (window.geL10n && window.geL10n.exitUrl) || 'https://www.google.com';
        window.setTimeout(function () {
          window.location.replace(exit);
        }, 1200);
      });
    }
  }

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

  /* --- Init -------------------------------------------------------------- */
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    initAgeGate();
    initMobileMenu();
    initFaq();
    initSubscribe();
  });
})();
