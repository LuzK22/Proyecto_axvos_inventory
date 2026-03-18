/**
 * AXVOS INVENTORY — Global UI enhancements
 *
 * Scrollbar hover en Chrome/Edge:
 * - Elementos normales (content-wrapper): clase en body funciona.
 * - Elementos position:fixed (main-sidebar): Chrome no propaga recálculo
 *   desde body. La clase debe ponerse en el propio elemento fixed.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ── Sidebar de módulos: clase en .main-sidebar (position:fixed) ── */
    const mainSidebar = document.querySelector('.main-sidebar');
    if (mainSidebar) {
        mainSidebar.addEventListener('mouseenter', () => mainSidebar.classList.add('sb-hover'));
        mainSidebar.addEventListener('mouseleave', () => mainSidebar.classList.remove('sb-hover'));
    }

    /* ── Contenido principal: clase en body (elemento normal) ───────── */
    const content = document.querySelector('.content-wrapper');
    if (content) {
        content.addEventListener('mouseenter', () => document.body.classList.add('axvos-sb-content-hover'));
        content.addEventListener('mouseleave', () => document.body.classList.remove('axvos-sb-content-hover'));
    }

});
