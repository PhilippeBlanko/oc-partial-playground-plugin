<?php namespace Ducharme\classes;

use Cms\Classes\Theme;
use File;
use Lang;
use function Ducharme\PartialPlayground\Classes\app;
use function Ducharme\PartialPlayground\Classes\e;

/**
 * Gère les partials (.htm) d’un dossier spécifique dans le thème actif
 */
class PartialManager
{
    protected ?string $partialsFolder;

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
     * Génère un nom lisible à partir d’un nom de fichier ou dossier technique
     *
     * @param string $rawName Nom brut (ex: 'callTo-action_banner')
     * @return string Nom lisible (ex: 'Call To Action Banner')
     */
    protected function prettifyName(string $rawName): string
    {
        // Remplace les délimiteurs par des espaces
        $pretty = str_replace(['-', '_', '.'], ' ', $rawName);

        // Ajoute un espace avant chaque majuscule camelCase
        $pretty = preg_replace('/(?<=[a-z])([A-Z])/', ' $1', $pretty);

        return ucwords(strtolower($pretty));
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
     * Récupère tous les partials d'un thème sous forme arborescente, à partir du dossier de partials
     *
     * @return array Arborescence des partials (fichiers et dossiers imbriqués)
     */
    public function getPartialsTree(): array
    {
        $basePath = $this->getBasePath();
        return File::isDirectory($basePath) ? $this->buildPartialsTree($basePath) : [];
    }

    /**
     * Parcourt récursivement les partials pour construire l’arborescence
     *
     * @param string $currentPath Chemin absolu courant dans le système de fichiers
     * @param string $relativePath Chemin relatif depuis le dossier de base
     * @return array Arborescence partielle des fichiers à ce niveau
     */
    protected function buildPartialsTree(string $currentPath, string $relativePath = ''): array
    {
        $result = [];

        // Fichiers .htm
        $files = File::files($currentPath);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'htm') {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $key = $relativePath ? $relativePath . '/' . $filename : $filename;
                $prettyName = $this->prettifyName($filename);

                $result[] = [
                    'type' => 'file',
                    'key' => $key,
                    'name' => $prettyName,
                ];
            }
        }

        // Dossiers
        $folders = File::directories($currentPath);
        foreach ($folders as $dir) {
            $folderName = basename($dir);
            $prettyName = $this->prettifyName($folderName);
            $newRelativePath = $relativePath ? $relativePath . '/' . $folderName : $folderName;
            $children = $this->buildPartialsTree($dir, $newRelativePath);

            $result[] = [
                'type' => 'folder',
                'key' => $newRelativePath,
                'name' => $prettyName,
                'children' => $children,
            ];
        }

        return $result;
    }

    /**
     * Génère les options HTML pour un <select>, avec indentation visuelle
     *
     * @param array $partials Arborescence des partials
     * @param int $level Niveau de profondeur (sert à l’indentation visuelle)
     * @return string HTML <option> prêt à insérer dans un <select>
     */
    public function renderPartialOptions(array $partials, int $level = 0): string
    {
        $html = '';
        $indent = str_repeat('|&nbsp;&nbsp;', $level);

        foreach ($partials as $item) {
            if ($item['type'] === 'file') {
                // option sélectionnable = fichier
                $html .= '<option value="' . e($item['key']) . '" title="' . e($item['key']) . '.htm">' . $indent . e($item['name']) . '</option>';
            } elseif ($item['type'] === 'folder') {
                // option désactivée = dossier
                $html .= '<option disabled title="' . e($item['key']) . '">' . $indent . e($item['name']) . '</option>';
                if (!empty($item['children'])) {
                    $html .= $this->renderPartialOptions($item['children'], $level + 1);
                }
            }
        }

        return $html;
    }

    /**
     * Trouve le premier partial de type "file" dans la structure hiérarchique
     *
     * @param array $partials Arborescence retournée par getPartialsTree()
     * @return array|null L’élément "file" trouvé ou null si aucun
     */
    public function findFirstFilePartial(array $partials): ?array
    {
        foreach ($partials as $item) {
            if ($item['type'] === 'file') {
                return $item;
            } elseif ($item['type'] === 'folder' && !empty($item['children'])) {
                $found = $this->findFirstFilePartial($item['children']);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Rendre le contenu d’un partial en HTML via Twig
     *
     * @param string $partialName Nom du partial relatif au dossier de partials (sans extension)
     * @param array $vars Variables Twig à injecter dans le template
     * @return string HTML rendu ou message d’erreur
     */
    public function renderPartialContent(string $partialName, array $vars = []): string
    {
        $basePath = $this->getBasePath();
        $partialPath = $basePath . '/' . $partialName . '.htm';

        $content = File::get($partialPath);
        $twig = app('twig.environment');

        try {
            return $twig->createTemplate($content)->render($vars);
        } catch (\Exception) {
            return '
                <div class="pp-callout-danger">
                    <p>' . Lang::get('ducharme.partialplayground::lang.iframe.rendering_partial_error') . '</p>
                </div>
            ';
        }
    }
}
