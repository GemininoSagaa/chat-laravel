import { writable } from 'svelte/store';

// User store
export const user = writable(null);

// Function to logout
export async function logout() {
    try {
        if (localStorage.getItem('token')) {
            await window.axios.post('/api/logout');
        }
    } catch (error) {
        console.error('Error during logout:', error);
    } finally {
        // Clear local storage and user store regardless of API success
        localStorage.removeItem('token');
        window.axios.defaults.headers.common['Authorization'] = '';
        user.set(null);
    }
}

// Funcion que se encarga de verficar que los usarios esten autenticados
export async function checkAuth() {
    const token = localStorage.getItem('token');
    
    if (!token) {
        user.set(null);
        return false;
    }
    
    try {
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        const response = await window.axios.get('/api/user');
        user.set(response.data);
        return true;
    } catch (error) {
        logout();
        return false;
    }
}