<?php

// Register routes
$this->bindClass('Content\\Controller\\Collection', '/content/collection');
$this->bindClass('Content\\Controller\\Singleton', '/content/singleton');
$this->bindClass('Content\\Controller\\Models', '/content/models');
$this->bindClass('Content\\Controller\\Content', '/content');

$this->helper('menus')->addLink('modules', [
    'label'  => 'Content',
    'icon'   => 'content:icon.svg',
    'route'  => '/content',
    'active' => false,
    'group'  => 'Content',
    'prio'   => 3
]);


// events
$this->on('app.layout.assets', function(array &$assets) {
    $assets[] = ['src' => 'content:assets/js/content.js', 'type' => 'module'];
});

$this->on('app.permissions.collect', function($permissions) {

    $permissions['Content'] = [
        'component' => 'ContentModelSettings',
        'src' => 'content:assets/vue-components/content-model-permissions.js',
        'props' => [
            'models' => $this->module('content')->models()
        ]
    ];
});

$this->on('app.search', function($search, $findings) {

    $models = array_values($this->module('content')->models());

    foreach ($models as $model) {

        if (stripos($model['name'], $search) !== false || stripos($model['label'], $search) !== false) {

            if (!$this->helper('acl')->isAllowed("content/{$model['name']}/read")) {
                continue;
            }

            $icon = $model['type'] == 'collection' ? 'content:assets/icons/collection.svg' : 'content:assets/icons/singleton.svg';

            $findings[] = [
                'title' => isset($model['label']) && $model['label'] ? "{$model['label']} ({$model['name']})" : $model['name'],
                'route' => $this->routeUrl($model['type'] == 'collection' ? "/content/collection/items/{$model['name']}" : "/content/singleton/item/{$model['name']}"),
                'group' => 'Content',
                'icon' => $icon
            ];
        }
    }

});
