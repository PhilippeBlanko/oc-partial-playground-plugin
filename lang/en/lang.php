<?php return [
    'plugin' => [
        'name' => 'Partial Playground',
        'description' => 'Backend interface to test and preview partials with dynamic parameters',
    ],
    'permission' => [
        '*' => [
            'tab' => 'Partial Playground',
            'label' => 'Access Partial Playground',
        ],
    ],
    'navigation' => [
        'label' => 'Partial Playground',
    ],
    'index' => [
        'title' => 'Partial Playground',
        'subtitle' => 'Test your partials with different parameters',
        'partial_select_label' => 'Partial',
        'no_partials_found' => 'No partials found in folder <code>:path</code>',
        'configuration' => 'Configuration',
        'reset_button_title' => 'Reset',
        'copy_code_button_title' => 'Copy Code',
        'config_file_not_found_or_poorly_implemented' => 'The configuration file <code>:path</code> is missing or poorly implemented',
        'real_time_preview' => 'Real-time Preview',
    ],
    'iframe' => [
        'title' => 'Partial Playground',
        'light_mode_title' => 'Light Mode',
        'dark_mode_title' => 'Dark Mode',
        'hand_tool_title' => 'Hand',
        'selection_tool_title' => 'Selection',
        'zoom_out_title' => 'Zoom Out',
        'zoom_in_title' => 'Zoom In',
        'zoom_fit_title' => 'Fit to Screen',
        'rendering_partial_error' => 'Error rendering partial',
    ],
    'messages' => [
        'preview_updated_success' => 'Preview updated successfully',
        'preview_update_error' => 'An error occurred while updating the preview',
        'partial_reset_success' => 'The partial has been reset successfully',
        'code_copied_success' => 'Code copied to clipboard successfully',
        'code_copy_error' => 'An error occurred while copying the code',
    ],
];
