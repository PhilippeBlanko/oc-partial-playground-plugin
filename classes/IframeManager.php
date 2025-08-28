<?php namespace Ducharme\classes;

use Cms\Classes\Theme;
use Backend\Models\BrandSetting;
use Config;

/**
 * Gèrer les fichiers CSS/JS pour l'iframe
 */
class IframeManager
{
    /**
     * Retourne le nom du thème actif
     *
     * @return string Nom du thème actif
     */
    public static function getThemeDir(): string
    {
        return Theme::getActiveTheme()->getDirName();
    }

    /**
     * Vérifie si un fichier existe dans le thème actif
     *
     * @param string $relativePath Chemin relatif du fichier dans le thème
     * @return bool True si le fichier existe, False sinon
     */
    protected static function themeFileExists(string $relativePath): bool
    {
        return file_exists(base_path('themes/' . self::getThemeDir() . '/' . ltrim($relativePath, '/')));
    }

    /**
     * Retourne le chemin complet d’un fichier du thème, utilisable dans <link> ou <script>
     *
     * @param string $relativePath Chemin relatif du fichier dans le thème
     * @return string Chemin complet pour inclusion dans la vue
     */
    protected static function getThemeFilePath(string $relativePath): string
    {
        return '/themes/' . self::getThemeDir() . '/' . ltrim($relativePath, '/');
    }

    /**
     * Retourne le chemin complet du CSS global à injecter dans le preview iframe
     *
     * @return string|null Chemin complet vers le CSS, ou null si non défini ou fichier inexistant
     */
    public static function getPreviewCssPath(): ?string
    {
        $file = Config::get('ducharme.partialplayground::preview_css_file');
        return ($file && self::themeFileExists($file)) ? self::getThemeFilePath($file) : null;
    }

    /**
     * Retourne le chemin complet du JS global à injecter dans le preview iframe
     *
     * @return string|null Chemin complet vers le JS, ou null si non défini ou fichier inexistant
     */
    public static function getPreviewJsPath(): ?string
    {
        $file = Config::get('ducharme.partialplayground::preview_js_file');
        return ($file && self::themeFileExists($file)) ? self::getThemeFilePath($file) : null;
    }

    /**
     * Retourne le chemin complet du CSS spécifique à un partial
     *
     * @param array|null $partialConfig Configuration YAML du partial
     * @return string|null Chemin complet vers le CSS du partial, ou null si non défini ou fichier inexistant
     */
    public static function getPartialCssPath(?array $partialConfig): ?string
    {
        $file = $partialConfig['preview_css_file'] ?? null;
        return ($file && self::themeFileExists($file)) ? self::getThemeFilePath($file) : null;
    }

    /**
     * Retourne le chemin complet du JS spécifique à un partial
     *
     * @param array|null $partialConfig Configuration YAML du partial
     * @return string|null Chemin complet vers le JS du partial, ou null si non défini ou fichier inexistant
     */
    public static function getPartialJsPath(?array $partialConfig): ?string
    {
        $file = $partialConfig['preview_js_file'] ?? null;
        return ($file && self::themeFileExists($file)) ? self::getThemeFilePath($file) : null;
    }

    /**
     * Retourne le layout à utiliser pour le preview d'un partial
     *
     * @param array|null $partialConfig Configuration YAML du partial
     * @return string 'content' ou 'full'
     */
    public static function getPreviewLayout(?array $partialConfig): string
    {
        // 1. Vérifie si le partial définit un layout spécifique
        if (!empty($partialConfig['preview_layout'])) {
            return $partialConfig['preview_layout'];
        }

        // 2. Sinon, retourne le layout global défini dans la config plugin
        return Config::get('ducharme.partialplayground::preview_layout', 'content');
    }

    /**
     * Retourne le mode couleur à appliquer dans le preview iframe
     *
     * @return string "light" ou "dark"
     */
    public static function getPreviewColorMode(): string
    {
        // 1. Vérifie si l'utilisateur a défini un mode via cookie
        if (!empty($_COOKIE['iframe_color_mode_user'])) {
            return $_COOKIE['iframe_color_mode_user'];
        }

        // 2. Récupère le mode par défaut dans la config plugin
        $defaultTheme = Config::get('ducharme.partialplayground::preview_default_theme');

        // 3. Si auto, prend la valeur BrandSetting, sinon prend la valeur config
        if ($defaultTheme === 'auto') {
            return BrandSetting::getColorMode();
        }

        return $defaultTheme;
    }
}
