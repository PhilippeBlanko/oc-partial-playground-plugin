// ==================== Variables globales ====================

let scale = 1; // Échelle actuelle du contenu (1 = 100%)
let offsetX = 0; // Décalage horizontal du contenu en pixels
let offsetY = 0; // Décalage vertical du contenu en pixels
let isDragging = false; // Indique si l'utilisateur est en train de faire un drag/pan
let lastX = 0; // Dernière coordonnée X de la souris pendant le drag
let lastY = 0; // Dernière coordonnée Y de la souris pendant le drag
const zoomStepPx = 10; // Pas de zoom en pixel pour zoomIn/zoomOut
const minScale = 0.1; // Échelle de zoom minimale autorisée (10%)
const maxScale = 2; // Échelle de zoom maximale autorisée (200%)

// ==================== DOM ====================

const textToolbarZoom = document.getElementById('text-toolbar-zoom');
const iframeContainer = document.getElementById('iframe-container');
const contentWrapper = document.getElementById('content-wrapper');

// ==================== Thème ====================

/**
 * Applique ou retire un thème sur un élément selon le type
 *
 * @param {HTMLElement} el - Élément DOM sur lequel appliquer le thème
 * @param {'class'|'attribute'} type - Mode d'application : 'class' pour une classe CSS, 'attribute' pour un attribut
 * @param {string|null} name - Nom de l'attribut si type=attribute
 * @param {string} value - Classe CSS ou valeur d'attribut
 * @param {boolean} apply - true = appliquer, false = retirer
 */
function setTheme(el, type, name, value, apply = true) {
  if (type === 'class') {
    if (apply) {
      el.classList.add(value);
    } else {
      el.classList.remove(value);
    }
  } else if (type === 'attribute' && name) {
    if (apply) {
      el.setAttribute(name, value ? value : '');
    } else {
      el.removeAttribute(name);
    }
  }
}

/**
 * Applique le thème sélectionné et optionnellement sauvegarde le choix dans un cookie
 *
 * @param {'light'|'dark'} selectedChoice - Le thème choisi par l'utilisateur
 * @param {boolean} saveCookie - Si true, crée/actualise le cookie
 */
function applyTheme(selectedChoice, saveCookie = false) {
  const config = window.previewThemeConfig;
  if (!config) {
    return;
  }

  const el = document.querySelector(config.target);
  if (!el) {
    return;
  }

  const themeConfig = config[selectedChoice];
  const otherTheme = selectedChoice === 'light' ? 'dark' : 'light';
  const otherConfig = config[otherTheme];

  // Supprime l’autre thème
  setTheme(el, otherConfig.type, otherConfig.name, otherConfig.value, false);

  // Applique le thème sélectionné
  setTheme(el, themeConfig.type, themeConfig.name, themeConfig.value, true);

  // Met à jour le data-attribute global
  document.documentElement.dataset.colorMode = selectedChoice;

  // Sauvegarde dans un cookie (30 jours) seulement si c’est une action utilisateur
  if (saveCookie) {
    const expires = new Date();
    expires.setTime(expires.getTime() + 30 * 24 * 60 * 60 * 1000);
    document.cookie = `iframe_color_mode_user=${selectedChoice}; expires=${expires.toUTCString()}; path=/`;
  }
}

/**
 * Initialise la barre de contrôle du thème (light/dark) et applique la valeur initiale
 */
function initTheme() {
  applyTheme(window.previewColorMode, false);

  document.querySelectorAll('#radio-toolbar-theme input[name="theme"]').forEach((radio) => {
    radio.addEventListener('change', (e) => applyTheme(e.target.value, true));
  });
}

// ==================== Zoom ====================

/**
 * Met à jour la transformation CSS du contenu en fonction de scale et offsets
 */
function updateTransform() {
  contentWrapper.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale})`;
  textToolbarZoom.textContent = Math.round(scale * 100) + '%';
}

/**
 * Calcule le zoom pour que le contenu s'ajuste à l'écran et le centre
 */
function calculateZoomFit() {
  // Dimensions disponibles dans le viewport (avec marges pour toolbar / padding)
  const containerWidth = window.innerWidth - 40;
  const containerHeight = window.innerHeight - 80;

  // Dimensions réelles du contenu
  const contentWidth = contentWrapper.scrollWidth;
  const contentHeight = contentWrapper.scrollHeight;

  // Si le contenu a une taille valide
  if (contentWidth && contentHeight) {
    // Détermine l'échelle maximale qui permet de faire tenir le contenu
    scale = Math.min(containerWidth / contentWidth, containerHeight / contentHeight, 1);

    // Recentre le contenu dans le viewport après le zoom
    offsetX = (window.innerWidth - contentWidth * scale) / 2;
    offsetY = (window.innerHeight - contentHeight * scale) / 2;

    // Applique la transformation CSS
    updateTransform();
  }
}

/**
 * Zoom centré sur l'écran par pas linéaire fixe (arrondi à 10%)
 *
 * @param {number} stepPx - Pas de zoom en pourcentage (ex: 10 ou -10)
 */
function zoomByStep(stepPx) {
  // Centre du viewport (écran) par rapport à la fenêtre
  const viewportCenterX = window.innerWidth / 2;
  const viewportCenterY = window.innerHeight / 2;

  // Centre du contenu par rapport à l'écran avant le zoom
  const contentCenterX = (viewportCenterX - offsetX) / scale;
  const contentCenterY = (viewportCenterY - offsetY) / scale;

  // Pourcentage actuel du zoom
  let currentPercent = Math.round(scale * 100);
  let newPercent;

  // Si on n'est pas sur une dizaine, on arrondit à la dizaine appropriée selon la direction
  if (currentPercent % 10 !== 0) {
    if (stepPx > 0) {
      // Zoom in : on va à la dizaine supérieure
      newPercent = Math.ceil(currentPercent / 10) * 10;
    } else {
      // Zoom out : on va à la dizaine inférieure
      newPercent = Math.floor(currentPercent / 10) * 10;
    }
  } else {
    // Si on est déjà sur une dizaine, on applique le step normalement
    newPercent = currentPercent + stepPx;
  }

  // Limite entre minScale et maxScale
  const minPercent = minScale * 100;
  const maxPercent = maxScale * 100;
  newPercent = Math.max(minPercent, Math.min(maxPercent, newPercent));

  let newScale = newPercent / 100;

  // Recalcule les offsets pour garder le centre du contenu au centre de l'écran
  offsetX = viewportCenterX - contentCenterX * newScale;
  offsetY = viewportCenterY - contentCenterY * newScale;

  // Applique le nouveau scale et met à jour la transformation CSS
  scale = newScale;
  updateTransform();
}

/**
 * Zoom avant de la valeur définie par zoomStepPx
 */
function zoomIn() {
  zoomByStep(zoomStepPx);
}

/**
 * Zoom arrière de la valeur définie par zoomStepPx
 */
function zoomOut() {
  zoomByStep(-zoomStepPx);
}

/**
 * Zoom pour que le contenu s'ajuste à l'écran (équivalent à "fit to screen")
 */
function zoomToFit() {
  calculateZoomFit();
}

/**
 * Initialise les boutons de zoom avant/arrière (+/-) dans la barre de contrôle du zoom
 */
function initZoomInOut() {
  document.getElementById('button-toolbar-zoom-in').addEventListener('click', zoomIn);
  document.getElementById('button-toolbar-zoom-out').addEventListener('click', zoomOut);
}

/**
 * Initialise le bouton "Zoom to Fit" et applique le zoom lors du chargement et du redimensionnement de l'iframe
 */
function initZoomFit() {
  zoomToFit();
  window.addEventListener('resize', zoomToFit);
  document.getElementById('button-toolbar-zoom-fit').addEventListener('click', zoomToFit);
}

// ==================== Pan / Drag ====================

/**
 * Retourne le mode pan sélectionné ('cursor' ou 'grab')
 *
 * @returns {'cursor'|'grab'} - Mode pan actuellement sélectionné
 */
function getPanMode() {
  const checked = document.querySelector('#radio-toolbar-pan input:checked');
  return checked ? checked.value : 'cursor';
}

/**
 * Met à jour le curseur selon le mode pan
 */
function updateCursorStyle() {
  const mode = getPanMode();
  iframeContainer.style.cursor = mode === 'grab' ? 'grab' : 'auto';
}

/**
 * Initialise le changement de curseur selon le mode pan
 */
function initPanCursor() {
  document.querySelectorAll('#radio-toolbar-pan input[name="pan"]').forEach((radio) => {
    radio.addEventListener('change', updateCursorStyle);
  });
}

/**
 * Initialise le drag/pan sur l'iframe
 */
function initPanDrag() {
  // Détecte le début du drag si le mode est 'grab'
  iframeContainer.addEventListener('mousedown', e => {
    if (getPanMode() === 'grab') {
      isDragging = true;
      lastX = e.clientX;
      lastY = e.clientY;
      e.preventDefault();
    }
  });

  // Déplace le contenu lors du mouvement de la souris
  iframeContainer.addEventListener('mousemove', e => {
    if (isDragging) {
      const dx = e.clientX - lastX;
      const dy = e.clientY - lastY;
      offsetX += dx;
      offsetY += dy;
      lastX = e.clientX;
      lastY = e.clientY;
      updateTransform();
    }
  });

  // Termine le drag quand on relâche le bouton de la souris
  iframeContainer.addEventListener('mouseup', () => {
    isDragging = false;
  });

  // Termine le drag si la souris sort de l'iframe
  iframeContainer.addEventListener('mouseleave', () => {
    isDragging = false;
  });
}

/**
 * Initialise le pan complet (cursor + drag) dans la barre de contrôle du pan
 */
function initPan() {
  initPanCursor();
  initPanDrag();
}

// ==================== Init ====================

document.addEventListener('DOMContentLoaded', function() {
  initTheme();
  initPan();
  initZoomInOut();
  initZoomFit();
});
