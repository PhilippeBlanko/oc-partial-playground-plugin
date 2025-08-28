<?php return [
    'plugin' => [
        'name' => 'Partial Playground',
        'description' => 'Interface backend pour tester et prévisualiser des partials avec des paramètres dynamiques',
    ],
    'permission' => [
        '*' => [
            'tab' => 'Partial Playground',
            'label' => 'Accès au Partial Playground',
        ],
    ],
    'navigation' => [
        'label' => 'Partial Playground',
    ],
    'index' => [
        'title' => 'Partial Playground',
        'subtitle' => 'Testez vos partials avec différents paramètres',
        'partial_select_label' => 'Partial',
        'no_partials_found' => 'Aucun partial trouvé dans le dossier <code>:path</code>',
        'configuration' => 'Configuration',
        'reset_button_title' => 'Réinitialiser',
        'copy_code_button_title' => 'Copier le code',
        'config_file_not_found_or_poorly_implemented' => 'Le fichier de configuration <code>:path</code> est introuvable ou mal implémenté',
        'real_time_preview' => 'Aperçu en temps réel',
    ],
    'iframe' => [
        'title' => 'Partial Playground',
        'light_mode_title' => 'Mode clair',
        'dark_mode_title' => 'Mode sombre',
        'hand_tool_title' => 'Main',
        'selection_tool_title' => 'Sélection',
        'zoom_out_title' => 'Dézoomer',
        'zoom_in_title' => 'Zoomer',
        'zoom_fit_title' => 'Ajuster à la taille',
        'rendering_partial_error' => 'Erreur lors du rendu du partial',
    ],
    'messages' => [
        'preview_updated_success' => 'Prévisualisation mise à jour avec succès',
        'preview_update_error' => 'Une erreur est survenue pendant la mise à jour de la prévisualisation',
        'partial_reset_success' => 'Le partial a été réinitialisé avec succès',
        'code_copied_success' => 'Code copié dans le presse-papiers avec succès',
        'code_copy_error' => 'Une erreur est survenue lors de la copie du code',
    ],
];
