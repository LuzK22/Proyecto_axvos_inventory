<x-guest-layout>

    {{-- Logo / título --}}
    <h2 class="text-lg font-semibold text-gray-800 mb-1">Iniciar sesión</h2>
    <p class="text-sm text-gray-500 mb-5">Ingresa tus credenciales para acceder al sistema.</p>

    {{-- Estado de sesión (ej: contraseña restablecida) --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Error de cuenta bloqueada --}}
    @if($errors->has('login') && str_contains($errors->first('login'), 'bloqueada'))
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-800 text-sm rounded flex items-start gap-2">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            <div>
                <strong class="font-semibold block">Cuenta bloqueada</strong>
                {{ $errors->first('login') }}
            </div>
        </div>
    @elseif($errors->has('login'))
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 text-sm rounded">
            {{ $errors->first('login') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Usuario o correo --}}
        <div class="mb-4">
            <x-input-label for="login" value="Usuario o correo electrónico" />
            <x-text-input
                id="login"
                class="block mt-1 w-full"
                type="text"
                name="login"
                :value="old('login')"
                required
                autofocus
                autocomplete="username"
            />
        </div>

        {{-- Contraseña --}}
        <div class="mb-1">
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                {{-- Enlace "Olvidé mi contraseña" --}}
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-indigo-600 hover:text-indigo-500 underline underline-offset-2">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            @if($errors->has('password'))
                <p class="mt-1 text-sm text-red-600">{{ $errors->first('password') }}</p>
            @endif
        </div>

        {{-- Recordarme --}}
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">Mantener sesión iniciada</span>
            </label>
        </div>

        <div class="mt-5">
            <x-primary-button class="w-full justify-center">
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>

    {{-- Nota informativa sobre bloqueo --}}
    <p class="mt-5 text-center text-xs text-gray-400">
        <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        Después de {{ \App\Models\User::MAX_LOGIN_ATTEMPTS }} intentos fallidos, la cuenta se bloquea {{ \App\Models\User::LOCKOUT_MINUTES }} minutos automáticamente.
    </p>

</x-guest-layout>
