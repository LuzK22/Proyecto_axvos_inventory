@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

<nav class="main-header navbar
    {{ config('adminlte.classes_topnav_nav', 'navbar-expand') }}
    {{ config('adminlte.classes_topnav', 'navbar-white navbar-light') }}">

    {{-- Navbar left links --}}
    <ul class="navbar-nav">
        {{-- Left sidebar toggler link --}}
        @include('adminlte::partials.navbar.menu-item-left-sidebar-toggler')

        {{-- Configured left links --}}
        @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-left'), 'item')

        {{-- Custom left links --}}
        @yield('content_top_nav_left')
    </ul>

    {{-- Navbar right links --}}
    <ul class="navbar-nav ml-auto">
        {{-- Custom right links --}}
        @yield('content_top_nav_right')

        {{-- Configured right links --}}
        @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-right'), 'item')

        {{-- Brand mark: [AX hex] AXVOS Inventory + 3×3 dots (decorative only) --}}
        <li class="nav-item d-none d-md-flex align-items-center pr-3" style="border-right:1px solid rgba(0,0,0,0.08);margin-right:4px;">
            <img src="{{ asset('img/axvos-hex.svg') }}" alt="AX" style="width:28px;height:28px;margin-right:6px;flex-shrink:0;">
            <span style="margin-right:7px;font-size:0.8rem;font-weight:700;letter-spacing:0.5px;color:#0d1b2a;opacity:0.7;white-space:nowrap;">
                <span style="color:#00b4d8;">AX</span>VOS
                <span style="font-weight:400;color:#00C6FF;opacity:1;">Inventory</span>
            </span>
            <svg width="14" height="14" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" style="display:block;flex-shrink:0;">
                <circle cx="2.5"  cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="9"    cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="15.5" cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="2.5"  cy="9"    r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="9"    cy="9"    r="2.5" fill="#00b4d8"/>
                <circle cx="15.5" cy="9"    r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="2.5"  cy="15.5" r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="9"    cy="15.5" r="2"   fill="#0d1b2a" opacity="0.35"/>
                <circle cx="15.5" cy="15.5" r="2"   fill="#0d1b2a" opacity="0.35"/>
            </svg>
        </li>

        {{-- User menu link --}}
        @if(Auth::user())
            @if(config('adminlte.usermenu_enabled'))
                @include('adminlte::partials.navbar.menu-item-dropdown-user-menu')
            @else
                @include('adminlte::partials.navbar.menu-item-logout-link')
            @endif
        @endif

        {{-- Right sidebar toggler link --}}
        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.navbar.menu-item-right-sidebar-toggler')
        @endif
    </ul>

</nav>
