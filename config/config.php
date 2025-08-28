<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Partials Folder
    |--------------------------------------------------------------------------
    |
    | The folder (relative to the "partials/" directory of the active theme)
    | where your partials are stored.
    |
    | Set to null to allow scanning from the root of the "partials/" directory.
    |
    | Example: if you store your components under "partials/components/",
    | set this value to "components".
    |
    */

    'partials_folder' => env('PARTIAL_PLAYGROUND_PARTIALS_FOLDER', null),

    /*
    |--------------------------------------------------------------------------
    | Global CSS File for Preview
    |--------------------------------------------------------------------------
    |
    | Path to a global CSS file used when rendering partial previews.
    | This path should be relative to the active theme root.
    |
    | Example: "assets/css/style.css" will be resolved to
    | themes/your-theme/assets/css/style.css.
    |
    */

    'preview_css_file' => env('PARTIAL_PLAYGROUND_PREVIEW_CSS_FILE', null),

    /*
    |--------------------------------------------------------------------------
    | Global JavaScript File for Preview
    |--------------------------------------------------------------------------
    |
    | Path to a global JS file used when rendering partial previews.
    | This path should be relative to the active theme root.
    |
    | Example: "assets/js/script.js" will be resolved to
    | themes/your-theme/assets/js/script.js.
    |
    */

    'preview_js_file' => env('PARTIAL_PLAYGROUND_PREVIEW_JS_FILE', null),

    /*
    |--------------------------------------------------------------------------
    | Default Preview Layout
    |--------------------------------------------------------------------------
    |
    | Default layout mode used when rendering partial previews in the iframe.
    |
    | Available options:
    | - 'content' : the component is displayed at its natural width, centered horizontally and vertically.
    | - 'full' : the component spans the full width of the iframe, centered vertically.
    |
    | Individual partials can override this value in their YAML configuration
    | using the "preview_layout" key.
    |
    */
    'preview_layout' => env('PARTIAL_PLAYGROUND_PREVIEW_LAYOUT', 'content'),

    /*
    |--------------------------------------------------------------------------
    | Default Preview Theme
    |--------------------------------------------------------------------------
    |
    | Default theme applied to the preview when the user has not chosen a theme.
    |
    | Set to 'auto' to use the backend effective color mode
    | (BrandSetting::getColorMode()).
    |
    | Example: 'light', 'dark', 'auto'
    |
    */

    'preview_default_theme' => env('PARTIAL_PLAYGROUND_PREVIEW_DEFAULT_THEME', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Preview Theme Configuration
    |--------------------------------------------------------------------------
    |
    | Defines where and how to apply the theme inside the preview iframe.
    |
    | - target : CSS selector where the theme will be applied (e.g., 'body' or 'html')
    | - type : 'class' or 'attribute'
    | - value : class name or attribute value (can be null if type=attribute)
    | - name : attribute name (only if type=attribute)
    |
    */

    'preview_theme' => [

        'target' => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_TARGET', 'html'),

        'light' => [
            'type'  => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_LIGHT_TYPE', 'class'),
            'name'  => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_LIGHT_NAME', null),
            'value' => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_LIGHT_VALUE', 'light'),
        ],

        'dark' => [
            'type'  => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_DARK_TYPE', 'class'),
            'name'  => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_DARK_NAME', null),
            'value' => env('PARTIAL_PLAYGROUND_PREVIEW_THEME_DARK_VALUE', 'dark'),
        ],

    ],

];
