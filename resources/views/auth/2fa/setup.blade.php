<x-guest-layout>

    {{-- Encabezado --}}
    <h2 class="text-lg font-semibold text-gray-800 mb-1">
        Configurar autenticación de dos factores
    </h2>
    <p class="text-sm text-gray-500 mb-5">
        Protege tu cuenta con un segundo factor de seguridad (TOTP).
    </p>

    {{-- Alertas de sesión --}}
    @if (session('info'))
        <div class="mb-4 p-3 bg-blue-50 border border-blue-300 text-blue-800 text-sm rounded">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 text-sm rounded">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Paso 1: QR --}}
    <div class="mb-5">
        <p class="text-sm font-medium text-gray-700 mb-3">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold mr-1">1</span>
            Escanea este código con <strong>Google Authenticator</strong> o <strong>Authy</strong>
        </p>

        {{--
            QR generado en servidor con simplesoftwareio/simple-qrcode (SVG).
            No se realiza ninguna llamada externa — el secreto nunca sale del servidor.
        --}}
        <div class="flex justify-center my-4">
            <div class="p-3 bg-white border-2 border-gray-200 rounded-xl shadow-sm inline-block">
                {!! $qrCode !!}
            </div>
        </div>

        <p class="text-xs text-gray-500 text-center mt-2">
            ¿No puedes escanear el QR? Ingresa esta clave manualmente en la app:
        </p>
        <div class="mt-1 text-center">
            <code class="inline-block bg-gray-100 border border-gray-200 text-gray-800 text-sm font-mono px-3 py-1.5 rounded tracking-widest select-all">
                {{ $manualKey }}
            </code>
        </div>
    </div>

    {{-- Paso 2: Confirmar código --}}
    <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-3">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold mr-1">2</span>
            Ingresa el código de 6 dígitos que muestra la app
        </p>

        <form method="POST" action="{{ route('2fa.confirm') }}">
            @csrf

            <x-text-input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                class="block w-full text-center text-2xl tracking-[0.5em] font-mono"
                maxlength="6"
                autocomplete="one-time-code"
                autofocus
                placeholder="000000"
                value="{{ old('code') }}"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />

            <div class="flex items-center justify-between mt-5">
                {{-- Opción para configurar después (acceso sin 2FA activo) --}}
                <a href="{{ route('dashboard') }}"
                   class="text-sm text-gray-400 hover:text-gray-600 underline underline-offset-2">
                    Configurar después
                </a>

                <x-primary-button>
                    Activar 2FA
                </x-primary-button>
            </div>
        </form>
    </div>

</x-guest-layout>
