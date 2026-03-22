<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Tu cuenta requiere verificación de dos factores. Ingresa el código de 6 dígitos
        de tu aplicación de autenticación.
    </div>

    @if (session('warning'))
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm rounded">
            {{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-red-600 text-sm">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('2fa.validate') }}">
        @csrf
        <div>
            <x-input-label for="code" value="Código de autenticación" />
            <x-text-input id="code" name="code" type="text" inputmode="numeric"
                class="block mt-1 w-full" maxlength="6" autocomplete="one-time-code" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-sm text-gray-500 underline">
                Cerrar sesión
            </a>
            <x-primary-button>Verificar</x-primary-button>
        </div>

        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    </form>
</x-guest-layout>
