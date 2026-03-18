<li class="nav-item">
    <a class="nav-link" data-widget="pushmenu" href="#"
        @if(config('adminlte.sidebar_collapse_remember'))
            data-enable-remember="true"
        @endif
        @if(!config('adminlte.sidebar_collapse_remember_no_transition'))
            data-no-transition-after-reload="false"
        @endif
        @if(config('adminlte.sidebar_collapse_auto_size'))
            data-auto-collapse-size="{{ config('adminlte.sidebar_collapse_auto_size') }}"
        @endif>
        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" style="display:block;">
            <circle cx="2.5"  cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="9"    cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="15.5" cy="2.5"  r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="2.5"  cy="9"    r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="9"    cy="9"    r="2.5" fill="#00b4d8"/>
            <circle cx="15.5" cy="9"    r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="2.5"  cy="15.5" r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="9"    cy="15.5" r="2"   fill="#0d1b2a" opacity="0.45"/>
            <circle cx="15.5" cy="15.5" r="2"   fill="#0d1b2a" opacity="0.45"/>
        </svg>
        <span class="sr-only">{{ __('adminlte::adminlte.toggle_navigation') }}</span>
    </a>
</li>