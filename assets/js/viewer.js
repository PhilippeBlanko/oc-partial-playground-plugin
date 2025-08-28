let currentRequest = null;

/**
 * Crée une fonction "debounced" qui retarde l'exécution de la callback
 * jusqu'à ce qu'un certain délai soit écoulé sans nouvel appel.
 *
 * Utile pour éviter d'exécuter trop souvent une fonction lors d'événements fréquents
 * (ex: saisie dans un champ texte).
 *
 * @param {Function} callback - La fonction à exécuter après le délai.
 * @param {number} delay - Le délai en millisecondes avant d'exécuter la callback.
 * @returns {Function} Une fonction qui regroupe les appels répétés en un seul.
 */
function debounce(callback, delay) {
  let timeoutId;

  function debouncedFunction(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      callback.apply(this, args);
    }, delay);
  }

  return debouncedFunction;
}

/**
 * Parse un objet FormData en un objet JavaScript structuré, en regroupant correctement les champs de type multiple
 *
 * @param {FormData} formData - L'objet FormData à transformer.
 * @returns {Object} Un objet avec les valeurs du formulaire prêtes à être utilisées.
 */
function parseFormData(formData) {
  const data = {};

  for (const [key, value] of formData.entries()) {
    const isArray = key.endsWith('[]');
    const cleanKey = isArray ? key.slice(0, -2) : key;

    if (isArray) {
      if (!data[cleanKey]) {
        data[cleanKey] = [];
      }

      data[cleanKey].push(value);
    } else {
      data[cleanKey] = value;
    }
  }

  return data;
}

/**
 * Réinitialise les scripts et déclenche les événements dans l'iframe
 *
 * - Recharge tous les scripts utilisateur (inline ou externe)
 * - Encapsule chaque script dans une IIFE pour éviter les conflits de variables
 * - Relance les events DOMContentLoaded et load pour les scripts qui en dépendent
 *
 * @param {HTMLIFrameElement} iframe - L'iframe contenant le preview
 */
function reloadIframeScriptsAndEvents(iframe) {
  const doc = iframe.contentDocument || iframe.contentWindow.document;

  if (doc) {
    // Sélection de tous les scripts à relancer :
    // 1. Scripts externes ou globaux avec data-user-script
    // 2. Scripts inline dans #content-wrapper
    const wrapperScripts = Array.from(doc.querySelectorAll('#content-wrapper script'));
    const userScripts = Array.from(doc.querySelectorAll('script[data-user-script]'));
    const scriptsToReload = [...new Set([...wrapperScripts, ...userScripts])];

    // Relancer tous les scripts utilisateur
    scriptsToReload.forEach((script) => {
      const src = script.src;
      const newScript = document.createElement('script');
      newScript.setAttribute('data-user-script', 'true');

      if (src) {
        // Script externe : fetch + encapsulation IIFE
        fetch(src)
          .then((r) => r.text())
          .then((code) => {
            // Nettoyage des directives sourceMappingURL
            const cleanCode = code.replace(/\/\/[#@]\s*sourceMappingURL=.*$/gm, '');
            newScript.textContent = `(function(){${cleanCode}})();`;
            script.replaceWith(newScript);
          })
          .catch(err => console.error('External script reload error:', err));
      } else {
        // Script inline : encapsulation IIFE directe
        const inlineCode = script.textContent;
        newScript.textContent = `(function(){${inlineCode}})();`;
        script.replaceWith(newScript);
      }
    });

    // Relancer les événements pour les scripts qui en dépendent
    const domContentEvent = new Event('DOMContentLoaded', {
      bubbles: true,
      cancelable: true
    });
    doc.dispatchEvent(domContentEvent);

    const loadEvent = new Event('load', {
      bubbles: true,
      cancelable: true
    });
    doc.defaultView.dispatchEvent(loadEvent);
  }
}

/**
 * Met à jour dynamiquement le contenu HTML affiché dans le content wrapper de l'iframe de prévisualisation
 *
 * @param {string} partialContentHtml - Le contenu HTML à injecter.
 */
function updatePreviewIframeContent(partialContentHtml) {
  const iframe = document.getElementById('preview-iframe');
  const doc = iframe.contentDocument || iframe.contentWindow.document;

  if (doc) {
    // Met à jour le HTML
    const contentWrapper = doc.getElementById('content-wrapper');
    contentWrapper.innerHTML = partialContentHtml;

    // Relance les scripts et événements dans l'iframe
    reloadIframeScriptsAndEvents(iframe);
  }
}

/**
 * Déclenche la mise à jour du preview en récupérant les valeurs du formulaire
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/system/assets/js/framework.js#L3315
 */
function triggerPreviewUpdate(form) {
  const formData = new FormData(form);
  const formDataObj = parseFormData(formData);
  const selectedPartial = document.getElementById('selected-partial');

  // Si une requête précédente est encore active (non terminée), on l'annule
  if (currentRequest && currentRequest.readyState !== 4) {
    currentRequest.abort();
  }

  currentRequest = $.request('onUpdatePreview', {
    data: {
      selectedPartial: selectedPartial.value,
      formData: formDataObj,
    },
    success: function(data) {
      if (data.partialContentHtml) {
        updatePreviewIframeContent(data.partialContentHtml);
      }
      $.oc.flashMsg({ text: window.translations.messages.preview_updated_success, class: 'success' });
    },
    error: function(data, responseCode) {
      if (responseCode !== -3) {
        $.oc.flashMsg({ text: window.translations.messages.preview_update_error, class: 'error' });
      }
    },
    complete: function() {
      currentRequest = null;
    }
  });
}

/**
 * Debounce de triggerPreviewUpdate pour limiter la fréquence d'exécution.
 *
 * @type {Function}
 */
const debouncedTriggerPreviewUpdate = debounce(triggerPreviewUpdate, 300);

/**
 * Initialise les écouteurs pour les champs "basiques" :
 * text, number, checkbox, switch, radio, textarea et dropdown.
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 */
function initBasicFields(form) {
  const fields = form.querySelectorAll('input:not([data-datepicker]):not([data-timepicker]):not([type="hidden"]), textarea, select');

  fields.forEach((field) => {
    let eventType;

    if (field.tagName === 'SELECT') {
      eventType = 'change';
    } else if (field.type === 'checkbox' || field.type === 'radio') {
      eventType = 'change';
    } else {
      eventType = 'input';
    }

    field.addEventListener(eventType, () => {
      debouncedTriggerPreviewUpdate(form);
    })
  });
}

/**
 * Initialise les écouteurs pour les champs datepicker
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/backend/assets/foundation/controls/datepicker/datepicker.js
 */
function initDatepickerFields(form) {
  const containersFields = form.querySelectorAll('[data-control="datepicker"]');

  containersFields.forEach((containerField) => {
    const inputLocker = containerField.querySelector('input[data-datetime-value]');
    let lastValue = inputLocker.value;

    $(containerField).on('change.oc.datepicker', function () {
      const currentValue = inputLocker.value;

      if (currentValue !== lastValue) {
        lastValue = currentValue;
        debouncedTriggerPreviewUpdate(form);
      }
    });
  });
}

/**
 * Initialise les écouteurs pour les champs colorpicker
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/backend/formwidgets/colorpicker/assets/js/colorpicker.js
 */
function initColorpickerFields(form) {
  const containersFields = form.querySelectorAll('[data-control="colorpicker"]');

  containersFields.forEach((containerField) => {
    const inputLocker = containerField.querySelector(containerField.dataset.dataLocker);
    let lastValue = inputLocker.value;

    $(containerField).on('change.oc.colorpicker', function () {
      const currentValue = inputLocker.value;

      if (currentValue !== lastValue) {
        lastValue = currentValue;
        debouncedTriggerPreviewUpdate(form);
      }
    });
  });
}

/**
 * Initialise les écouteurs pour les champs codeeditor
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/backend/formwidgets/codeeditor/assets/js/codeeditor.js
 */
function initCodeeditorFields(form) {
  const containersFields = form.querySelectorAll('[data-control="codeeditor"]');

  containersFields.forEach((containerField) => {
    const textareaLocker = containerField.querySelector('textarea');

    $(containerField).on('oc.codeEditorReady', function () {
      const editor = ace.edit(containerField.querySelector('.editor-code'));

      editor.getSession().on('change', function () {
        textareaLocker.value = editor.getSession().getValue();
        debouncedTriggerPreviewUpdate(form);
      });
    });
  });
}

/**
 * Initialise les écouteurs pour les champs richeditor
 *
 * Gère la complexité du mode Vue (October v3), où l'éditeur Froala est initialisé de façon asynchrone et encapsulé.
 * - D'abord, on attend que l'instance `oc.richEditor` soit disponible.
 * - Ensuite, on attend que l'éditeur Froala interne (`getEditor()`) soit prêt.
 * - Une fois prêt, on écoute l’événement `contentChanged` pour réagir aux modifications.
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/backend/formwidgets/richeditor/assets/js/richeditor.js
 */
function initRicheditorFields(form) {
  const containersFields = form.querySelectorAll('[data-control="richeditor"]');

  containersFields.forEach((containerField) => {
    // Tente de récupérer l'instance RichEditor attachée au container.
    const tryGetInstance = () => {
      const richEditorInstance = $(containerField).data('oc.richEditor');

      if (richEditorInstance) {
        // Tente de récupérer l'instance Froala interne via `getEditor()`.
        const tryGetEditor = () => {
          const editor = richEditorInstance.getEditor();

          if (editor) {
            // Quand l'éditeur est prêt, on écoute l'événement de changement de contenu
            editor.events.on('contentChanged', function () {
              // Attendre (setTimeout 0) pour garantir que le textarea est bien mis à jour
              setTimeout(() => {
                debouncedTriggerPreviewUpdate(form);
              }, 0);
            });

            return;
          }

          setTimeout(tryGetEditor, 300);
        };

        tryGetEditor();
        return;
      }

      setTimeout(tryGetInstance, 300);
    };

    tryGetInstance();
  });
}

/**
 * Initialise les écouteurs pour les champs mediafinder
 *
 * @param {HTMLFormElement} form - Le formulaire contenant les champs.
 * @see https://github.com/octobercms/october/blob/4.x/modules/media/formwidgets/mediafinder/assets/js/mediafinder.js
 */
function initMediafinderFields(form) {
  const containersFields = form.querySelectorAll('[data-control="mediafinder"]');

  containersFields.forEach((containerField) => {
    const divLocker = containerField.querySelector('div[data-data-locker]');

    $(divLocker).on('change', function () {
      debouncedTriggerPreviewUpdate(form);
    });
  });
}

/**
 * Initialise la configuration des champs du formulaire pour déclencher la mise à jour d'aperçu
 *
 * @param {HTMLFormElement} form - L'élément formulaire à configurer.
 */
function initSidebarConfig(form) {
  // Champs type text, number, checkbox, switch, radio, textarea et dropdown
  initBasicFields(form);

  // Champs type datepicker
  initDatepickerFields(form);

  // Champs type colorpicker
  initColorpickerFields(form);

  // Champs type codeeditor
  initCodeeditorFields(form);

  // Champs type richeditor
  initRicheditorFields(form);

  // Champs type mediafinder
  initMediafinderFields(form);
}

/**
 * Déclenche la mise à jour du partial sélectionné et reconfigure les champs du formulaire
 *
 * @param {HTMLSelectElement} partialSelect - Le champ select du partial.
 */
function triggerChangePartial(partialSelect) {
  // Affiche la barre de progression
  $.oc.stripeLoadIndicator.show();

  // Exécute la requête
  $.request('onChangePartial', {
    data: {
      selectedPartial: partialSelect.value,
    },
    progressBar: false,
    afterUpdate: function () {
      // Met à jour la configuration du formulaire après le changement du partial
      const form = document.getElementById('Form-partialForm');
      if (form) {
        initSidebarConfig(form);
      }

      // Masque la barre de progression
      $.oc.stripeLoadIndicator.hide();
    },
  });
}

/**
 * Déclenche l'appel backend pour réinitialiser l'aperçu du partial en remettant ces valeurs par défauts
 */
window.triggerResetPreview = function () {
  const selectedPartial = document.getElementById('selected-partial');

  // Affiche la barre de progression
  $.oc.stripeLoadIndicator.show();

  // Exécute la requête
  $.request('onResetPreview', {
    data: {
      selectedPartial: selectedPartial.value,
    },
    progressBar: false,
    afterUpdate: function () {
      $.oc.flashMsg({ text: window.translations.messages.partial_reset_success, class: 'success' });

      // Met à jour la configuration du formulaire après réinitialisation
      const form = document.getElementById('Form-partialForm');
      if (form) {
        initSidebarConfig(form);
      }

      // Masque la barre de progression
      $.oc.stripeLoadIndicator.hide();
    },
  });
}

/**
 * Déclenche l'appel backend pour copier le code du partial courant
 */
window.copyPartialCode = function () {
  const form = document.getElementById('Form-partialForm');
  const formData = new FormData(form);
  const formDataObj = parseFormData(formData);
  const selectedPartial = document.getElementById('selected-partial');

  $.request('onCopyPartialCode', {
    data: {
      selectedPartial: selectedPartial.value,
      formData: formDataObj,
    },
    success: function(data) {
      if (data.partialCode) {
        navigator.clipboard.writeText(data.partialCode).then(() => {
          $.oc.flashMsg({ text: window.translations.messages.code_copied_success, class: 'success' });
        })
      } else {
        this.error();
      }
    },
    error: function() {
      $.oc.flashMsg({ text: window.translations.messages.code_copy_error, class: 'error' });
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const partialSelect = document.getElementById('partial-select');
  const form = document.getElementById('Form-partialForm');

  if (partialSelect) {
    partialSelect.addEventListener('change', () => triggerChangePartial(partialSelect));
  }

  if (form) {
    initSidebarConfig(form);
  }
});
