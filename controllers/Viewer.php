<?php namespace Ducharme\controllers;

use Backend\Classes\Controller;
use Backend\Models\BrandSetting;
use BackendMenu;
use Config;
use Ducharme\classes\PartialManager;
use Ducharme\classes\PartialConfigManager;
use Ducharme\classes\IframeManager;
use function Ducharme\PartialPlayground\Controllers\get;
use function Ducharme\PartialPlayground\Controllers\post;

/**
 * Contrôleur principal du plugin Partial Playground
 */
class Viewer extends Controller
{
    protected ?PartialManager $partialManager = null;
    protected ?PartialConfigManager $configManager = null;
    public ?string $menuModeClass = null;
    public ?string $partialsFolder = null;
    public array $partials = [];
    public ?string $partialsOptionsHtml = null;
    public ?string $selectedPartial = null;
    public array $partialConfig = [];
    public \Backend\Classes\WidgetBase|null $formWidget = null;
    public ?string $partialContentHtml = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        parent::__construct();

        // Définir le contexte du menu backend : plugin et code du menu
        BackendMenu::setContext('Ducharme.PartialPlayground', 'partialplayground');

        // Obtenir la classe CSS du mode menu actif du backend
        $this->menuModeClass = $this->getMenuModeClass();

        // Charge et stocke la config une fois
        $this->partialsFolder = Config::get('ducharme.partialplayground::partials_folder');
    }

    /**
     * Obtient la classe CSS pour le mode menu actif du backend
     *
     * @return string Classe CSS correspondant au mode menu actif du backend
     */
    protected function getMenuModeClass(): string
    {
        $settings = BrandSetting::instance();
        $menuMode = $settings->menu_mode ?? BrandSetting::MENU_INLINE;

        return 'menu-mode-' . $menuMode;
    }

    /**
     * Retourne ou crée une seule fois les instances de PartialManager et PartialConfigManager
     *
     * @return array{0: PartialManager, 1: PartialConfigManager} Tableau contenant les instances des deux managers
     */
    protected function getManagers(): array
    {
        if ($this->partialManager === null || $this->configManager === null) {
            $this->partialManager = new PartialManager($this->partialsFolder);
            $this->configManager = new PartialConfigManager($this->partialsFolder);
        }

        return [$this->partialManager, $this->configManager];
    }

    /**
     * Méthode appelée quand on arrive sur la page principale du contrôleur
     */
    public function index()
    {
        // Titre de la page affichée dans le backend
        $this->pageTitle = 'Partial Playground';

        // Ajouter la feuille de style CSS et le script JS
        $this->addCss('/plugins/ducharme/partialplayground/assets/css/viewer.css');
        $this->addJs('/plugins/ducharme/partialplayground/assets/js/viewer.js');

        // 👉 Obtenir les instances des deux managers
        [$partialManager, $configManager] = $this->getManagers();

        // 👉 Récupère tous les partials d'un thème sous forme arborescente
        $this->partials = $partialManager->getPartialsTree();
        $this->partialsOptionsHtml = $partialManager->renderPartialOptions($this->partials);

        // 👉 Sélectionner le premier partial uniquement si l'arborescence des partials n'est pas vide
        // 👉 Charger la config YAML du partial sélectionné
        // 👉 Créer le widget Form
        // 👉 Rendre le contenu du partial en HTML avec valeur par défaut
        if (!empty($this->partials)) {
            $this->selectedPartial = ($first = $partialManager->findFirstFilePartial($this->partials)) ? $first['key'] : null;
            $this->partialConfig = $configManager->loadPartialConfig($this->selectedPartial);

            if (!empty($this->partialConfig)) {
                $this->formWidget = $configManager->createBackendFormWidget($this->partialConfig, $this);
                $partialDefaultConfigValues = $configManager->getDefaultFieldValues($this->partialConfig);
                $this->partialContentHtml = $partialManager->renderPartialContent($this->selectedPartial, $partialDefaultConfigValues);
            }
        }

        // 👉 Prépare les variables communes pour la vue
        $this->prepareVars();
    }

    /**
     * Méthode appelée quand on arrive sur la page iframe de prévisualisation du contrôleur
     */
    public function iframe()
    {
        // Désactive le layout backend
        $this->layout = false;

        // 👉 Récupère le nom du partial via query string
        $this->selectedPartial = get('selected-partial');

        // 👉 Obtenir les instances des deux managers
        [$partialManager, $configManager] = $this->getManagers();

        // 👉 Charger la config YAML du partial sélectionné
        // 👉 Créer le widget Form
        // 👉 Rendre le contenu du partial en HTML avec valeur par défaut
        $this->partialConfig = $configManager->loadPartialConfig($this->selectedPartial);
        $partialDefaultConfigValues = $configManager->getDefaultFieldValues($this->partialConfig);
        $this->partialContentHtml = $partialManager->renderPartialContent($this->selectedPartial, $partialDefaultConfigValues);

        // 👉 Prépare toutes les variables spécifiques à cette vue
        $this->vars['previewCssPath'] = IframeManager::getPreviewCssPath();
        $this->vars['previewJsPath'] = IframeManager::getPreviewJsPath();
        $this->vars['partialCssPath'] = IframeManager::getPartialCssPath($this->partialConfig);
        $this->vars['partialJsPath'] = IframeManager::getPartialJsPath($this->partialConfig);
        $this->vars['previewLayout'] = IframeManager::getPreviewLayout($this->partialConfig);
        $this->vars['previewColorMode'] = IframeManager::getPreviewColorMode();
        $this->vars['previewThemeConfig'] = Config::get('ducharme.partialplayground::preview_theme');

        // 👉 Prépare les variables communes pour la vue
        $this->prepareVars();
    }

    /**
     * Handler AJAX pour changer le partial
     *
     * @return array<string, string> Tableau des fragments HTML mis à jour
     */
    public function onChangePartial(): array
    {
        // 👉 Réinitialiser l'état interne
        $this->resetState();

        // 👉 Récupère le partial sélectionné
        $this->selectedPartial = post('selectedPartial');

        // 👉 Obtenir les instances des deux managers
        [$partialManager, $configManager] = $this->getManagers();

        // 👉 Charger la config YAML du partial sélectionné
        $this->partialConfig = $configManager->loadPartialConfig($this->selectedPartial);

        // 👉 Créer le widget Form
        // 👉 Rendre le contenu du partial en HTML avec valeur par défaut
        if (!empty($this->partialConfig)) {
            $this->formWidget = $configManager->createBackendFormWidget($this->partialConfig, $this);
            $partialDefaultConfigValues = $configManager->getDefaultFieldValues($this->partialConfig);
            $this->partialContentHtml = $partialManager->renderPartialContent($this->selectedPartial, $partialDefaultConfigValues);
        }

        // 👉 Prépare les variables communes pour la vue
        $this->prepareVars();

        return [
            '#config-header' => $this->makePartial('config_header'),
            '#config-content' => $this->makePartial('config_content'),
            '#preview-header' => $this->makePartial('preview_header'),
            '#preview-content' => $this->makePartial('preview_content'),
        ];
    }

    /**
     * Handler AJAX pour mettre à jour l'aperçu du partial
     *
     * @return array{partialContentHtml: string} Contenu HTML de l'aperçu mis à jour
     */
    public function onUpdatePreview(): array
    {
        // 👉 Réinitialiser l'état interne
        $this->resetState();

        // 👉 Récupérer le partial sélectionné
        $this->selectedPartial = post('selectedPartial');

        // 👉 Récupérer les données du formulaire envoyées
        $formData = post('formData', []);

        // 👉 Obtenir les instances des deux managers
        [$partialManager, $configManager] = $this->getManagers();

        // 👉 Charger la config YAML du partial sélectionné
        $this->partialConfig = $configManager->loadPartialConfig($this->selectedPartial);

        // 👉 Rendre le contenu du partial en HTML avec valeur soumis par l'utilisateur
        if (!empty($this->partialConfig)) {
            $partialDefaultConfigValues = $configManager->getDefaultFieldValues($this->partialConfig);
            $partialMergedConfigValues = array_merge($partialDefaultConfigValues, $formData);
            $this->partialContentHtml = $partialManager->renderPartialContent($this->selectedPartial, $partialMergedConfigValues);
        }

        // 👉 Prépare les variables communes pour la vue
        $this->prepareVars();

        return [
            'partialContentHtml' => $this->partialContentHtml
        ];
    }

    /**
     * Handler AJAX pour réinitialiser l'aperçu du partial en remettant ces valeurs par défauts
     *
     * @return array<string, string> Tableau des fragments HTML mis à jour
     */
    public function onResetPreview(): array
    {
        // 👉 Réinitialiser l'état interne
        $this->resetState();

        // 👉 Récupérer le partial sélectionné
        $this->selectedPartial = post('selectedPartial');

        // 👉 Obtenir les instances des deux managers
        [$partialManager, $configManager] = $this->getManagers();

        // 👉 Charger la config YAML du partial sélectionné
        $this->partialConfig = $configManager->loadPartialConfig($this->selectedPartial);

        // 👉 Charger la config YAML du partial sélectionné
        // 👉 Créer le widget Form
        // 👉 Rendre le contenu du partial en HTML avec valeur par défaut
        if (!empty($this->partialConfig)) {
            $this->formWidget = $configManager->createBackendFormWidget($this->partialConfig, $this);
            $partialDefaultConfigValues = $configManager->getDefaultFieldValues($this->partialConfig);
            $this->partialContentHtml = $partialManager->renderPartialContent($this->selectedPartial, $partialDefaultConfigValues);
        }

        // 👉 Prépare les variables communes pour la vue
        $this->prepareVars();

        return [
            '#config-content' => $this->makePartial('config_content'),
            '#preview-content' => $this->makePartial('preview_content'),
        ];
    }

    /**
     * Handler AJAX pour générer le code partial Twig à copier
     *
     * @return array{partialCode: string} Code Twig formaté prêt à copier
     */
    public function onCopyPartialCode(): array
    {
        // 👉 Récupérer le partial sélectionné
        $this->selectedPartial = post('selectedPartial');

        // 👉 Récupérer les données du formulaire envoyées
        $formData = post('formData', []);

        // 👉 Formater les paramètres en attributs twig
        $formattedParams = [];
        foreach ($formData as $key => $value) {
            if (is_string($value)) {
                // Quote simple pour les strings
                $escaped = str_replace("'", "\\'", $value);
                $formattedValue = "'{$escaped}'";
            } elseif (is_array($value)) {
                // Pour chaque item, si string, on l'entoure de quotes
                $items = [];
                foreach ($value as $item) {
                    if (is_string($item)) {
                        $escapedItem = str_replace("'", "\\'", $item);
                        $items[] = "'{$escapedItem}'";
                    } else {
                        $items[] = $item;
                    }
                }

                // Format en tableau Twig : ['a', 1, true]
                $formattedValue = '[' . implode(', ', $items) . ']';
            } else {
                // Couvre les booléens, int, etc.
                $formattedValue = $value;
            }

            $formattedParams[] = "{$key} = {$formattedValue}";
        }

        // 👉 Assemblage final
        $paramsString = implode("\n\t", $formattedParams);
        $partialPath = $this->partialsFolder . '/' . $this->selectedPartial;
        $partialTag = "{% partial '{$partialPath}'\n\t{$paramsString}\n%}";

        return [
            'partialCode' => $partialTag,
        ];
    }

    /**
     * Réinitialise l'état interne
     */
    protected function resetState(): void
    {
        $this->selectedPartial = null;
        $this->partialConfig = [];
        $this->formWidget = null;
        $this->partialContentHtml = null;
    }

    /**
     * Prépare les variables communes pour la vue
     */
    protected function prepareVars(): void
    {
        $this->vars['menuModeClass'] = $this->menuModeClass;
        $this->vars['partialsFolder'] = $this->partialsFolder;
        $this->vars['partials'] = $this->partials;
        $this->vars['partialsOptionsHtml'] = $this->partialsOptionsHtml;
        $this->vars['selectedPartial'] = $this->selectedPartial;
        $this->vars['partialConfig'] = $this->partialConfig;
        $this->vars['formWidget'] = $this->formWidget;
        $this->vars['partialContentHtml'] = $this->partialContentHtml;
    }
}
