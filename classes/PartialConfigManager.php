<?php namespace Ducharme\classes;

use Backend\Classes\Controller;
use Backend\Widgets\Form;
use Cms\Classes\Theme;
use File;
use Model;
use Yaml;

/**
 * Gère la configuration des partials définie via fichiers .yaml
 */
class PartialConfigManager
{
    protected ?string $partialsFolder;
    protected array $allowedConfigFieldTypes = ['text', 'number', 'checkbox', 'switch', 'radio', 'textarea', 'dropdown', 'datepicker', 'colorpicker', 'codeeditor', 'richeditor', 'mediafinder', 'hint'];

    /**
     * Constructeur
     *
     * @param string|null $partialsFolder Dossier (relatif à /partials) où chercher les partials
     */
    public function __construct(?string $partialsFolder)
    {
        $this->partialsFolder = $partialsFolder;
    }

    /**
     * Retourne le chemin absolu vers le dossier de partials ($partialsFolder)
     *
     * @return string Chemin absolu sur le disque
     */
    protected function getBasePath(): string
    {
        $theme = Theme::getActiveTheme();
        $base = $theme->getPath() . '/partials';

        // Si un sous-dossier est précisé, on l'ajoute
        return $this->partialsFolder ? $base . '/' . $this->partialsFolder : $base;
    }

    /**
     * Charge et retourne la configuration YAML d’un partial, en filtrant les champs selon les types autorisés
     *
     * @param string $partialName Nom du partial relatif au dossier de partials (sans extension)
     * @return array Configuration YAML parsée et filtrée, ou tableau vide si erreur ou fichier absent
     */
    public function loadPartialConfig(string $partialName): array
    {
        $basePath = $this->getBasePath();
        $configPath = $basePath . '/' . $partialName . '.yaml';

        if (!File::exists($configPath)) {
            return [];
        }

        try {
            $content = File::get($configPath);
            $config = Yaml::parse($content) ?: [];

            return $this->filterAllowedFields($config);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Filtre les champs pour ne conserver que ceux avec un type autorisé
     *
     * @param array $config Configuration YAML complète d’un partial
     * @return array Configuration filtrée
     */
    protected function filterAllowedFields(array $config): array
    {
        if (!isset($config['fields']) || !is_array($config['fields'])) {
            return $config;
        }

        $config['fields'] = array_filter(
            $config['fields'],
            fn($fieldConfig) => isset($fieldConfig['type']) && in_array($fieldConfig['type'], $this->allowedConfigFieldTypes)
        );

        return $config;
    }

    /**
     * Extrait les valeurs par défaut des champs définis dans la configuration YAML d'un partial
     *
     * @param array $config
     * @return array
     */
    public function getDefaultFieldValues(array $config): array
    {
        $defaults = [];

        if (!isset($config['fields']) || !is_array($config['fields'])) {
            return $defaults;
        }

        foreach ($config['fields'] as $fieldKey => $fieldConfig) {
            if (isset($fieldConfig['default'])) {
                $defaults[$fieldKey] = $fieldConfig['default'];
            }
        }

        return $defaults;
    }

    /**
     * Crée et retourne un widget Form backend basé sur la configuration YAML d’un partial
     *
     * @param array $configArray Configuration YAML (généralement issue de loadPartialConfig)
     * @param Controller $controller Contrôleur backend qui héberge le widget
     * @return \Backend\Classes\WidgetBase|null Widget Form prêt à l’usage, ou null si aucun champ
     */
    public function createBackendFormWidget(array $configArray, Controller $controller): ?\Backend\Classes\WidgetBase
    {
        $widgetConfig = $controller->makeConfig($configArray);
        $widgetConfig->alias = 'partialForm';
        $widgetConfig->model = new class extends Model {};

        $form = $controller->makeWidget(Form::class, $widgetConfig);
        $form->bindToController();

        return empty($form->getFields()) ? null : $form;
    }
}
