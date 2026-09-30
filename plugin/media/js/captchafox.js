/**
 * CaptchaFox for Joomla: renders the CaptchaFox widgets explicitly.
 *
 * Controls the widget and keeps a form from being sent without a token. Joomla's own form
 * validation is left untouched, and the server-side verification of the token in the field
 * cf-captcha-response stays decisive.
 *
 * @copyright  (C) 2026 Scoria Labs GmbH
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
(() => {
  'use strict';

  const SELECTOR = '[data-captchafox-joomla]';
  const HINT_CLASS = 'captchafox-joomla-hint';

  const hintOf = (container) => {
    const next = container.nextElementSibling;
    return next && next.classList.contains(HINT_CLASS) ? next : null;
  };

  const clearHint = (container) => {
    const hint = hintOf(container);
    if (hint) {
      hint.remove();
    }
  };

  const showHint = (container) => {
    let hint = hintOf(container);

    if (!hint) {
      hint = document.createElement('div');
      hint.className = `${HINT_CLASS} invalid-feedback d-block`;
      hint.setAttribute('role', 'alert');
      container.after(hint);
    }

    hint.textContent = container.dataset.messageUnsolved || '';
    container.scrollIntoView({ block: 'center', behavior: 'smooth' });

    const checkbox = container.querySelector('[role="checkbox"]');
    if (checkbox) {
      checkbox.focus({ preventScroll: true });
    }
  };

  const optionsFor = (container) => {
    const options = {
      sitekey: container.dataset.sitekey,
      onVerify: () => clearHint(container),
    };

    ['mode', 'theme', 'start', 'lang'].forEach((name) => {
      if (container.dataset[name]) {
        options[name] = container.dataset[name];
      }
    });

    return options;
  };

  const renderAll = () => {
    document.querySelectorAll(SELECTOR).forEach((container) => {
      if (container.dataset.cfState) {
        return;
      }

      container.dataset.cfState = 'rendering';

      // render() resolves with the widget id; every later call on this widget passes it explicitly.
      Promise.resolve(window.captchafox.render(container, optionsFor(container)))
        .then((widgetId) => {
          container.dataset.cfWidgetId = String(widgetId);
          container.dataset.cfState = 'rendered';
        })
        .catch(() => {
          container.dataset.cfState = 'error';
        });
    });
  };

  const resubmit = (form, submitter) => {
    if (typeof form.requestSubmit !== 'function') {
      form.submit();
    } else if (submitter && submitter.form === form) {
      form.requestSubmit(submitter);
    } else {
      form.requestSubmit();
    }
  };

  // Cancelling (e.g. the article form's Cancel, task "article.cancel") must never need a captcha.
  const isCancel = (form, submitter) => {
    if (submitter && submitter.formNoValidate) {
      return true;
    }

    const task = form.elements.namedItem('task');

    return !!task && typeof task.value === 'string' && /\.cancel$/.test(task.value);
  };

  // A form is only sent with a token. Hidden mode shows no widget: the check runs on submit, then the
  // form is sent again with the token. In the other modes the visitor gets a hint at the widget.
  // Runs in the bubbling phase, so a submit that Joomla's form validation already stopped is left alone.
  const onSubmit = (event) => {
    const form = event.target;

    if (event.defaultPrevented || !(form instanceof HTMLFormElement) || isCancel(form, event.submitter)) {
      return;
    }

    const container = form.querySelector(SELECTOR);
    const token = container && container.querySelector('[name="cf-captcha-response"]');

    if (!container || (token && token.value)) {
      return;
    }

    event.preventDefault();

    if (container.dataset.mode !== 'hidden') {
      showHint(container);

      return;
    }

    // Not rendered yet: the visitor can simply submit again.
    if (!container.dataset.cfWidgetId || !window.captchafox) {
      return;
    }

    window.captchafox
      .execute(container.dataset.cfWidgetId)
      .then(() => resubmit(form, event.submitter))
      .catch(() => {
        // Challenge failed or was closed: the form stays as it is.
      });
  };

  document.addEventListener('submit', onSubmit);

  // Called by the CaptchaFox API once it is loaded (onload parameter of the script URL).
  window.captchaFoxJoomlaOnLoad = renderAll;

  // The API may already be loaded, e.g. when another script loaded it first.
  if (window.captchafox && typeof window.captchafox.render === 'function') {
    renderAll();
  }
})();
