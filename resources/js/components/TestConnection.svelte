<script>
    import { onMount } from 'svelte';
    import api from '../lib/api';
    
    let status = 'Esperando...';
    let error = null;
    let user = null;
    let baseUrl = window.location.origin;
    
    onMount(async () => {
        status = 'Comprobando conexión a la API...';
        
        try {
            // Comprobar si hay un token
            const token = localStorage.getItem('token');
            if (!token) {
                status = 'No hay token de autenticación. Por favor inicia sesión primero.';
                return;
            }
            
            // Establecer el token de autorización
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            
            // Intentar obtener datos del usuario
            const response = await api.get('/api/user');
            
            if (response.data) {
                user = response.data;
                status = 'Conexión exitosa';
            } else {
                status = 'Respuesta vacía del servidor';
            }
        } catch (err) {
            status = 'Error de conexión';
            error = err;
            console.error('Error al comprobar conexión:', err);
        }
    });
    
    async function testLogin() {
        try {
            status = 'Intentando iniciar sesión...';
            
            const loginResponse = await api.post('/api/login', {
                email: 'test@example.com',
                password: 'password'
            });
            
            if (loginResponse.data && loginResponse.data.token) {
                localStorage.setItem('token', loginResponse.data.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${loginResponse.data.token}`;
                
                user = loginResponse.data.user;
                status = 'Inicio de sesión exitoso';
            } else {
                status = 'Respuesta de inicio de sesión incorrecta';
            }
        } catch (err) {
            status = 'Error de inicio de sesión';
            error = err;
            console.error('Error al iniciar sesión:', err);
        }
    }
    
    function logout() {
        localStorage.removeItem('token');
        api.defaults.headers.common['Authorization'] = '';
        user = null;
        status = 'Sesión cerrada';
    }
</script>

<div class="test-container">
    <h2>Prueba de Conexión API</h2>
    
    <div class="status-card">
        <h3>Estado: {status}</h3>
        <p>URL Base: {baseUrl}</p>
        
        {#if user}
            <div class="user-info">
                <h4>Información de Usuario</h4>
                <p>ID: {user.id}</p>
                <p>Nombre: {user.name}</p>
                <p>Email: {user.email}</p>
            </div>
            
            <button class="btn" on:click={logout}>Cerrar Sesión</button>
        {:else}
            <button class="btn" on:click={testLogin}>Probar Login</button>
        {/if}
        
        {#if error}
            <div class="error-details">
                <h4>Detalles del Error</h4>
                <p>Mensaje: {error.message}</p>
                {#if error.response}
                    <p>Código: {error.response.status}</p>
                    <p>Respuesta: {JSON.stringify(error.response.data)}</p>
                {/if}
            </div>
        {/if}
    </div>
</div>

<style>
    .test-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    h2 {
        color: #3490dc;
        margin-bottom: 20px;
    }
    
    .status-card {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    
    .user-info, .error-details {
        margin-top: 20px;
        padding: 10px;
        background-color: white;
        border-radius: 5px;
        border: 1px solid #eee;
    }
    
    .btn {
        margin-top: 15px;
        padding: 8px 16px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn:hover {
        background-color: #2779bd;
    }
    
    .error-details {
        border-color: #f5c6cb;
        background-color: #f8d7da;
        color: #721c24;
    }
</style>