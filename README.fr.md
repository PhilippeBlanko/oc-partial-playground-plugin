# Plugin Partial Playground pour October CMS

**Partial Playground** est un plugin backend pour October CMS qui permet de **tester et prévisualiser vos partials avec des paramètres dynamiques**, directement depuis l’interface d’administration. Il facilite le développement et la mise au point de composants réutilisables.

## Fonctionnalités principales

- **Prévisualisation en temps réel** : visualisez immédiatement l’effet des modifications sur vos partials.
- **Gestion des fichiers CSS et JS associés** : incluez et testez facilement les styles et scripts spécifiques à chaque partial.
- **Copie du code généré** : récupérez rapidement le code prêt à être intégré dans vos pages ou templates.
- **Interface intuitive** : modifiez les paramètres dynamiques et observez instantanément le rendu final.

## Installation

Vous pouvez installer ce plugin depuis le **Marketplace October CMS** ou en utilisant **Composer**.

### Via Marketplace

1. Allez dans le backend d’October CMS : **Settings > System > Plugins**.
2. Recherchez le plugin **Partial Playground**.
3. Cliquez sur le plugin pour l’installer.

### Via Composer

Ouvrez votre terminal, placez-vous à la racine de votre projet October CMS et exécutez la commande suivante :

```bash
php artisan plugin:install Ducharme.PartialPlayground
```

## Configuration

Le plugin **Partial Playground** propose deux niveaux de configuration : globale et spécifique à chaque partial.

### 1. Configuration globale du plugin

La configuration globale de **Partial Playground** peut être personnalisée dans votre projet.  
Copiez le fichier `plugins/ducharme/partialplayground/config/config.php` dans le dossier `config/ducharme/partialplayground` et modifiez les valeurs selon vos besoins.

- **`partials_folder`** : dossier où sont stockés vos partials, relatif à `partials/` du thème actif.
- **`preview_css_file`** : fichier CSS global appliqué à toutes les prévisualisations.
- **`preview_js_file`** : fichier JS global appliqué à toutes les prévisualisations.
- **`preview_layout`** : layout par défaut de la prévisualisation (`content` ou `full`).
- **`preview_default_theme`** : thème par défaut (`light`, `dark`, ou `auto`).
- **`preview_theme`** : configuration pour appliquer le thème (`target`, `type`, `name`, `value`).

### 2. Configuration spécifique à chaque partial

Pour chaque partial, deux fichiers doivent être présents **au même niveau dans le dossier du partial** :

#### a) Fichier `.htm`

- Contient le code HTML de votre partial.
- Doit être **bien isolé**, comme un vrai composant réutilisable.
- Inclut uniquement le markup nécessaire au partial, sans dépendances externes non gérées par le plugin.

#### b) Fichier `.yaml`

- Définit les **champs dynamiques** et les paramètres configurables du partial.
- Permet de définir un **fichier CSS et JS** spécifique au partial, en plus de ceux configurés globalement :
  - **`preview_css_file`** : chemin relatif vers un fichier CSS spécifique au partial (optionnel). 
  - **`preview_js_file`** : chemin relatif vers un fichier JS spécifique au partial (optionnel). 
- Permet de définir le **layout de prévisualisation** spécifique au partial (`content` ou `full`) via :
  - **`preview_layout`** : remplace le layout global si nécessaire.

### 3. Types de champs disponibles pour le fichier `.yaml`

Voici tous les types de champs que vous pouvez utiliser dans vos fichiers YAML pour configurer un partial :

- [`Text`](https://docs.octobercms.com/3.x/element/form/field-text.html)
- [`Number`](https://docs.octobercms.com/3.x/element/form/field-number.html)
- [`Checkbox`](https://docs.octobercms.com/3.x/element/form/field-checkbox.html)
- [`Switch`](https://docs.octobercms.com/3.x/element/form/field-switch.html)
- [`Radio`](https://docs.octobercms.com/3.x/element/form/field-radio.html)
- [`Textarea`](https://docs.octobercms.com/3.x/element/form/field-textarea.htmla)
- [`Dropdown`](https://docs.octobercms.com/3.x/element/form/field-dropdown.html)
- [`Date Picker`](https://docs.octobercms.com/3.x/element/form/widget-datepicker.html)
- [`Color Picker`](https://docs.octobercms.com/3.x/element/form/widget-colorpicker.html)
- [`Code Editor`](https://docs.octobercms.com/3.x/element/form/widget-codeeditor.html)
- [`Rich Editor`](https://docs.octobercms.com/3.x/element/form/widget-richeditor.html)
- [`Media Finder`](https://docs.octobercms.com/3.x/element/form/widget-mediafinder.html)
- [`Hint`](https://docs.octobercms.com/3.x/element/form/ui-hint.html)

Pour plus de détails sur chaque type de champ et les options possibles, consultez la documentation officielle : [Form Fields – October CMS](https://docs.octobercms.com/3.x/element/form-fields.html)

## Exemples

### Alert

```html
{# alert.htm #}

{# --- Import Bootstrap 5.3 --- #}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

{# --- Dynamic parameters --- #}
{% set type = type ?: 'info' %}
{% set message = message ?: 'Ceci est une alerte.' %}
{% set dismissible = dismissible ? 'alert-dismissible' : '' %}

{# --- Alert --- #}
<div class="alert alert-{{ type }} {{ dismissible }}" role="alert">
    {% if dismissible %}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    {% endif %}
    {{ message }}
</div>
```

```yaml
{# alert.yaml #}
  
fields:

  type:
    label: "Alert Type"
    type: dropdown
    default: info
    options:
      primary: Primary
      secondary: Secondary
      success: Success
      danger: Danger
      warning: Warning
      info: Info
      light: Light
      dark: Dark
    comment: "Defines the style of the alert"
    
  message:
    label: "Message"
    type: textarea
    default: "This is an alert."
    comment: "The text to display inside the alert"
    
  dismissible:
    label: "Show close button"
    type: switch
    default: 1
    comment: "Allows the user to close the alert"

preview_layout: "full"
```

### Button

```html
{# button.htm #}

{# --- Import Bootstrap 5.3 --- #}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

{# --- Dynamic parameters --- #}
{% set text = text ?: 'Click me' %}
{% set type = type ?: 'button' %}
{% set style = style ?: 'primary' %}
{% set outline = outline ? 'btn-outline-' ~ style : 'btn-' ~ style %}
{% set size = size ?: '' %}
{% set disabled = disabled ? 'disabled' : '' %}

{# --- Button or link --- #}
{% if type == 'link' and href %}
    <a href="{{ href }}" class="btn {{ outline }} {{ size ? 'btn-' ~ size : '' }} {{ disabled }}">
        {{ text }}
    </a>
{% else %}
    <button type="{{ type }}" class="btn {{ outline }} {{ size ? 'btn-' ~ size : '' }}" {{ disabled }}>
        {{ text }}
    </button>
{% endif %}
```

```yaml
{# button.yaml #}
  
fields:

  text:
    label: "Button Text"
    type: text
    default: "Click me"
    comment: "Text displayed inside the button"
    placeholder: "Ex: Submit"
    required: true
    
  type:
    label: "Button Type"
    type: dropdown
    default: button
    options:
      button: Button
      submit: Submit
      reset: Reset
      link: Link
    comment: "Defines the button behavior"
    
  href:
    label: "Link URL"
    type: text
    default: "#"
    comment: "The URL the button points to if type is 'Link'"
    trigger:
      action: show
      field: type
      condition: value[link]
    
  style:
    label: "Style"
    type: dropdown
    default: primary
    options:
      primary: Primary
      secondary: Secondary
      success: Success
      danger: Danger
      warning: Warning
      info: Info
      light: Light
      dark: Dark
      link: Link
    comment: "Button color and style"
    
  outline:
    label: "Outline Style"
    type: switch
    default: 0
    comment: "Display only the button border"
    
  size:
    label: "Size"
    type: dropdown
    default: ''
    options:
      '': Normal
      sm: Small
      lg: Large
    comment: "Button size"
    
  disabled:
    label: "Disable Button"
    type: switch
    default: 0
    comment: "The button will be greyed out and not clickable"

```

### Toggle switch

```html
{# toggle-switch.htm {#

{# --- Toggle switch --- #}
<button id="toggle-switch-button"
        class="toggle-switch-btn"
        data-label="{{ label|default('OFF') }}"
        data-active-label="{{ active_label|default('ON') }}">
    {{ label|default('OFF') }}
</button>
```

```yaml
{# toggle-switch.yaml #}

fields:

  note:
    type: "hint"
    comment: "This partial uses its own CSS and JS files as defined by the <code>preview_css_file</code> and <code>preview_js_file</code> keys in the partial's configuration."
    commentHtml: true
    
  label:
    label: "Toggle Label"
    type: text
    default: "OFF"
    comment: "Text initially displayed on the button"
    
  active_label:
    label: "Active Label"
    type: text
    default: "ON"
    comment: "Text displayed when the toggle is activated"

preview_css_file: "assets/css/toggle-switch.css"
preview_js_file: "assets/js/toggle-switch.js"
```

D’autres exemples sont disponibles dans le dossier [examples](https://github.com/PhilippeBlanko/oc-partial-playground-plugin/tree/main/examples) du plugin.

## Contribuer

Les contributions sont les bienvenues !

- Forkez le projet et créez votre branche pour les améliorations ou corrections.
- Soumettez une [Pull Request](https://github.com/PhilippeBlanko/oc-partial-playground-plugin/pulls) avec une description claire des changements.
- Signalez les bugs ou problèmes via les [Issues](https://github.com/PhilippeBlanko/oc-partial-playground-plugin/issues).

Merci de respecter les bonnes pratiques de contribution et de documenter vos modifications.

## Licence

Ce plugin est distribué sous licence **MIT**.  
Le texte complet de la licence MIT est disponible ici : [MIT License](https://github.com/PhilippeBlanko/oc-partial-playground-plugin/blob/main/LICENCE)

## Documentation

Cette documentation a été générée en partie avec l'aide d'une intelligence artificielle.  
La version anglaise de ce README est disponible ici : [README.md](README.md)

