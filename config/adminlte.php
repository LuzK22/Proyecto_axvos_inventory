<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'AXVOS Inventory',
    'title_prefix' => '',
    'title_postfix' => ' | AXVOS',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<span style="margin-left:20px;"><span style="font-weight:900;letter-spacing:1px;font-size:20px;color:#00b4d8;">AX</span><span style="font-weight:700;font-size:20px;color:#fff;">VOS</span><span style="font-size:18px;color:#7ecfea;font-weight:400;margin-left:4px;">Inventory</span></span>',
    'logo_img' => 'img/axvos-hex.svg',
    'logo_img_class' => 'brand-image elevation-0',
    'logo_img_style' => 'width:80px;height:80px;',
    'logo_img_xl' => 'img/axvos-hex.svg',
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_xl_style' => 'width:80px;height:80px;',
    'logo_img_alt' => 'AXVOS Inventory',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-dark',
    'usermenu_image' => false,
    'usermenu_desc' => true,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4 axvos-sidebar',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => null,
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => true,
    'dashboard_url' => 'dashboard',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */
'menu' => [

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    [
        'text' => 'Dashboard',
        'route' => 'dashboard',
        'icon'  => 'fas fa-home',
    ],

    /*
    |--------------------------------------------------------------------------
    | TECNOLOGÍA
    |--------------------------------------------------------------------------
    */
    ['header' => 'TECNOLOGÍA', 'can' => 'tech.assets.view'],

    ['text' => 'Activos TI',        'icon' => 'fas fa-laptop',       'route' => 'tech.assets.hub',       'can' => 'tech.assets.view'],
    ['text' => 'Asignaciones TI',   'icon' => 'fas fa-user-check',   'route' => 'tech.assignments.hub',  'can' => 'tech.assets.assign'],
    ['text' => 'Préstamos TI',      'icon' => 'fas fa-handshake',    'route' => 'tech.loans.hub',        'can' => 'tech.assets.view'],
    ['text' => 'Bajas TI',          'icon' => 'fas fa-ban',          'route' => 'tech.disposals.hub',    'can' => 'tech.assets.disposal.view'],
    ['text' => 'Reportes TI',       'icon' => 'fas fa-chart-bar',    'route' => 'tech.reports.hub',      'can' => 'tech.reports.view'],

    /*
    |--------------------------------------------------------------------------
    | COLABORADORES  (visible para Auxiliar_TI y Gestor_Activos)
    |--------------------------------------------------------------------------
    */
    ['header' => 'COLABORADORES', 'can' => 'collaborators.view'],

    ['text' => 'Colaboradores',  'icon' => 'fas fa-users',      'route' => 'collaborators.index', 'can' => 'collaborators.view'],

    /*
    |--------------------------------------------------------------------------
    | OTROS ACTIVOS
    |--------------------------------------------------------------------------
    */
    ['header' => 'OTROS ACTIVOS', 'can' => 'assets.view'],

    ['text' => 'Otros Activos',          'icon' => 'fas fa-boxes',        'route' => 'assets.hub',            'can' => 'assets.view'],
    ['text' => 'Asignaciones',           'icon' => 'fas fa-user-tag',     'route' => 'assets.assignments.hub','can' => 'assets.assign'],
    ['text' => 'Préstamos Otros Activos','icon' => 'fas fa-handshake',    'route' => 'assets.loans.hub',      'can' => 'assets.view'],
    ['text' => 'Bajas',                  'icon' => 'fas fa-trash',        'route' => 'assets.disposals.hub',  'can' => 'assets.disposal.view'],
    ['text' => 'Reportes',               'icon' => 'fas fa-file-excel',   'route' => 'assets.reports.hub',    'can' => 'assets.reports.view'],

    /*
    |--------------------------------------------------------------------------
    | APROBACIONES  (solo Aprobador y Admin)
    |--------------------------------------------------------------------------
    */
    ['header' => 'APROBACIONES', 'can' => 'tech.assets.disposal.approve'],

    ['text' => 'Bajas TI',          'icon' => 'fas fa-ban',          'route' => 'tech.disposals.hub',    'can' => 'tech.assets.disposal.approve'],
    ['text' => 'Bajas Otros',       'icon' => 'fas fa-trash-alt',    'route' => 'assets.disposals.hub',  'can' => 'assets.disposal.approve'],

    /*
    |--------------------------------------------------------------------------
    | AUDITORÍA  (solo Auditor y Admin)
    |--------------------------------------------------------------------------
    */
    ['header' => 'AUDITORÍA', 'can' => 'audit.view'],

    ['text' => 'Auditoría Global',  'icon' => 'fas fa-search-dollar', 'route' => 'audit.hub',            'can' => 'audit.view'],

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN
    |--------------------------------------------------------------------------
    */
    ['header' => 'HERRAMIENTAS', 'can' => 'tech.assets.view'],

    ['text' => 'Asistente IA', 'icon' => 'fas fa-robot', 'route' => 'ai.hub', 'can' => 'tech.assets.view', 'icon_color' => 'cyan'],

    ['header' => 'ADMINISTRACIÓN', 'can' => 'users.manage'],

    ['text' => 'Administración', 'icon' => 'fas fa-cog',          'route' => 'admin.hub',          'can' => 'users.manage'],
    ['text' => 'Respaldo',       'icon' => 'fas fa-database',     'route' => 'admin.backup.index', 'can' => 'users.manage', 'icon_color' => 'cyan'],

    /*
    |--------------------------------------------------------------------------
    | MI CUENTA — visible para todos los usuarios autenticados
    |--------------------------------------------------------------------------
    */
    ['header' => 'MI CUENTA'],

    [
        'text'       => 'Centro de Seguridad',
        'icon'       => 'fas fa-shield-alt',
        'icon_color' => 'red',
        'route'      => 'security.index',
    ],
    [
        'text'       => 'Mi Perfil',
        'icon'       => 'fas fa-user-cog',
        'icon_color' => 'cyan',
        'route'      => 'profile.edit',
    ],

    /*
    |--------------------------------------------------------------------------
    | MENÚ DESPLEGABLE SUPERIOR — clic en el nombre del usuario (topnav)
    |--------------------------------------------------------------------------
    */
    [
        'text'        => 'Centro de Seguridad',
        'icon'        => 'fas fa-shield-alt',
        'icon_color'  => 'red',
        'route'       => 'security.index',
        'topnav_user' => true,
    ],
    [
        'text'        => 'Mi Perfil',
        'icon'        => 'fas fa-user-cog',
        'topnav_user' => true,
        'route'       => 'profile.edit',
    ],
    [
        'text'        => 'Cambiar Contraseña',
        'icon'        => 'fas fa-key',
        'topnav_user' => true,
        'route'       => 'profile.edit',
        'url'         => 'profile#update-password-form',
    ],

],


       

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
        'AxvosTheme' => [
            'active' => true,
            'files' => [
                [
                    'type'     => 'css',
                    'asset'    => true,
                    'location' => 'css/axvos.css',
                ],
                [
                    'type'     => 'css',
                    'asset'    => true,
                    'location' => 'css/module-hub.css',
                ],
                [
                    'type'     => 'js',
                    'asset'    => true,
                    'location' => 'js/axvos.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
