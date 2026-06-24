/**
 * global.js - Lógica de UI y Auto-actualización
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('Antigravity AI - Sistema Activado');

    // 1. Detectar si hay elementos procesando o pendientes para activar el polling
    const procesando = document.querySelector('.progress_container, .badge-pendiente');
    if (procesando) {
        console.log('Detectado video en proceso o en cola, activando auto-refresco...');
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

// Lógica de Autocompletado Profesional
document.querySelectorAll('.custom_autocomplete').forEach(input => {
    const listId = input.getAttribute('data-list');
    const datalist = document.getElementById(listId);
    if (!datalist) return;
    
    const options = Array.from(datalist.options).map(opt => opt.value);
    const wrapper = input.closest('.autocomplete_wrapper');
    const listContainer = document.createElement('div');
    listContainer.className = 'autocomplete_list';
    wrapper.appendChild(listContainer);
    
    input.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        listContainer.innerHTML = '';
        if (!val) {
            // Mostrar todos si está vacío
            mostrarTodos();
            return;
        }
        
        let hasMatches = false;
        options.forEach(opt => {
            if (opt.toLowerCase().includes(val)) {
                hasMatches = true;
                const item = document.createElement('div');
                item.className = 'autocomplete_item';
                const regex = new RegExp(`(${val})`, "gi");
                item.innerHTML = opt.replace(regex, "<strong>$1</strong>");
                
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    input.value = opt;
                    listContainer.classList.remove('active');
                });
                listContainer.appendChild(item);
            }
        });
        
        if (hasMatches) listContainer.classList.add('active');
        else listContainer.classList.remove('active');
    });
    
    input.addEventListener('blur', () => {
        listContainer.classList.remove('active');
    });
    
    function mostrarTodos() {
        listContainer.innerHTML = '';
        options.forEach(opt => {
            const item = document.createElement('div');
            item.className = 'autocomplete_item';
            item.textContent = opt;
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                input.value = opt;
                listContainer.classList.remove('active');
            });
            listContainer.appendChild(item);
        });
        if(options.length > 0) listContainer.classList.add('active');
    }

    input.addEventListener('focus', function() {
        if(this.value) {
            this.dispatchEvent(new Event('input'));
        } else {
            mostrarTodos();
        }
    });
});
