/**
 * global.js - Lógica de UI y Auto-actualización
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('Antigravity AI - Sistema Activado');

    // 1. Detectar si hay elementos procesando para activar el polling
    const procesando = document.querySelector('.progress_anim');
    if (procesando) {
        console.log('Detectado video en proceso, activando auto-refresco...');
        setTimeout(() => {
            window.location.reload();
        }, 5000); // Recarga cada 5 segundos si hay algo pendiente
    }
});

function fn_confirmar_borrado(mensaje = "¿Estás seguro de eliminar este registro?") {
    return confirm(mensaje);
}

// Estilizar los inputs automáticamente
const inputs = document.querySelectorAll('.g_in');
inputs.forEach(input => {
    input.addEventListener('focus', () => {
        input.parentElement.classList.add('active');
    });
    input.addEventListener('blur', () => {
        input.parentElement.classList.remove('active');
    });
});
