<footer class="main-footer">
    @yield('footer')
</footer>

{{-- ── AXI Floating Chat Button ──────────────────────────────────────────
     Botón flotante del asistente AXI — aparece en todas las páginas.
     Imagen B (axi-face.png): solo la cara del robot, reconocible en pequeño.
     Al hacer clic lleva al hub del asistente.
     Cuando se integre la IA, este botón abrirá el chat directamente.
────────────────────────────────────────────────────────────────────────── --}}
@auth
<a href="{{ route('ai.hub') }}"
   id="axi-float-btn"
   title="Pregunta a AXI"
   aria-label="Abrir asistente AXI">
    <img src="{{ asset('img/axi/axi-face.png') }}"
         alt="AXI"
         onerror="this.outerHTML='<i class=\'fas fa-robot\' style=\'color:#00b4d8;font-size:1.4rem;\'></i>'">
</a>
@endauth