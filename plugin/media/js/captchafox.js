/**
 * CaptchaFox for Joomla: renders the CaptchaFox widgets explicitly.
 *
 * Only controls the widget. Validation stays with Joomla: the widget writes its token into the
 * field cf-captcha-response, which the server verifies.
 *
 * @copyright  (C) 2026 Scoria Labs GmbH
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
(() => {
  'use strict';

  const SELECTOR = '[data-captchafox-joomla]';

  const optionsFor = (container) => {
    const options = { sitekey: container.dataset.sitekey };

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

  // Called by the CaptchaFox API once it is loaded (onload parameter of the script URL).
  window.captchaFoxJoomlaOnLoad = renderAll;

  // The API may already be loaded, e.g. when another script loaded it first.
  if (window.captchafox && typeof window.captchafox.render === 'function') {
    renderAll();
  }
})();
