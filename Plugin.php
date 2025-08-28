<?php namespace Ducharme\PartialPlayground;

use System\Classes\PluginBase;
use Backend;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'ducharme.partialplayground::lang.plugin.name',
            'description' => 'ducharme.partialplayground::lang.plugin.description',
            'author' => 'Philippe Ducharme',
            'icon' => 'icon-puzzle-piece'
        ];
    }

    public function registerPermissions()
    {
        return [
            'ducharme.partialplayground.*' => [
                'tab' => 'ducharme.partialplayground::lang.permission.*.tab',
                'label' => 'ducharme.partialplayground::lang.permission.*.label',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'partialplayground' => [
                'label' => 'ducharme.partialplayground::lang.navigation.label',
                'url' => Backend::url('ducharme/partialplayground/viewer'),
                'icon' => 'icon-puzzle-piece',
                'permissions' => ['ducharme.partialplayground.*'],
                'order' => 500,
            ],
        ];
    }
}
