import { writable } from 'svelte/store';

// Almacenar la ruta actual
export const currentRoute = writable(window.location.pathname);

// Almacenar parámetros de la ruta
export const routeParams = writable({});

// Rutas para la aplicación
const routes = [
  { path: '/', component: 'Home' },
  { path: '/login', component: 'Login' },
  { path: '/register', component: 'Register' },
  { path: '/chat', component: 'Chat' },
  { path: '/friends', component: 'Friends' },
  { path: '/groups', component: 'Groups' }
];

// Navegar a una nueva ruta
export function navigate(path, params = {}) {
  window.history.pushState({}, '', path);
  currentRoute.set(path);
  routeParams.set(params);
}

// Encontrar componente para la ruta actual
export function findComponentForRoute(path) {
  const route = routes.find(route => route.path === path);
  return route ? route.component : 'NotFound';
}

// Actualizar la ruta actual cuando se cambia la URL
window.addEventListener('popstate', () => {
  currentRoute.set(window.location.pathname);
});