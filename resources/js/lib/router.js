import { writable } from 'svelte/store';

// Almacenar la ruta actual
export const currentRoute = writable(window.location.pathname);

// Navegar a una nueva ruta
export function navigate(path) {
    window.history.pushState({}, '', path);
    currentRoute.set(path);
}

// Actualizar la ruta actual cuando se cambia la URL
window.addEventListener('popstate', () => {
    currentRoute.set(window.location.pathname);
});