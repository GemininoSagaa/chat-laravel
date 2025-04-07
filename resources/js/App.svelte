<script>
    import { onMount } from 'svelte';
    import { writable } from 'svelte/store';
    import { navigate, currentRoute } from './lib/router.js';
    import Login from './pages/Login.svelte';
    import Register from './pages/Register.svelte';
    import Chat from './pages/Chat.svelte';
    import TestConnection from './components/TestConnection.svelte';
    import api from './lib/api.js';

    // Estado de autenticación
    const user = writable(null);
    const isLoading = writable(true);
    const showDebug = writable(false);
    const apiBaseUrl = writable('');

    onMount(async () => {
        // Detectar la URL base
        const currentPath = window.location.pathname;
        let baseURL = window.location.origin;

        // Si estamos en un subdirectorio, ajustar la URL base para incluir ese directorio
        if (currentPath.includes('/laravel/chat-laravel/public')) {
            baseURL = `${baseURL}/laravel/chat-laravel/public`;
        }
        
        apiBaseUrl.set(baseURL);
        console.log('App Base URL:', baseURL);
        
        try {
            // Verificar autenticación
            const token = localStorage.getItem('token');
            
            if (token) {
                console.log('Token encontrado, verificando usuario...');
                api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                
                const response = await api.get('/api/user');
                
                if (response.data) {
                    console.log('Usuario autenticado:', response.data);
                    user.set(response.data);
                }
            } else {
                console.log('No hay token de autenticación');
                user.set(null);
            }
        } catch (error) {
            console.error('Error al verificar autenticación:', error);
            user.set(null);
        } finally {
            isLoading.set(false);
        }
    });

    function goToLogin() {
        navigate('/login');
    }

    function goToRegister() {
        navigate('/register');
    }
    
    function toggleDebug() {
        showDebug.update(value => !value);
    }

    console.log("Ruta actual:", $currentRoute);
</script>

<main>
    {#if $isLoading}
        <div class="loading">
            <div class="spinner"></div>
            <p>Cargando aplicación...</p>
        </div>
    {:else}
        <div class="app-container">
            <button class="debug-toggle" on:click={toggleDebug}>
                {$showDebug ? 'Ocultar Debug' : 'Mostrar Debug'}
            </button>
            
            {#if $showDebug}
                <div class="debug-panel">
                    <h3>Información de Debug</h3>
                    <p>URL Base: {$apiBaseUrl}</p>
                    <p>Ruta actual: {$currentRoute}</p>
                    <p>Usuario: {$user ? $user.name : 'No autenticado'}</p>
                    
                    <div class="debug-actions">
                        <button class="btn" on:click={() => navigate('/')}>Ir a Home</button>
                        <button class="btn" on:click={() => navigate('/login')}>Ir a Login</button>
                        <button class="btn" on:click={() => navigate('/chat')}>Ir a Chat</button>
                    </div>
                    
                    <TestConnection />
                </div>
            {:else}
                {#if $currentRoute === '/login'}
                    <Login />
                {:else if $currentRoute === '/register'}
                    <Register />
                {:else if $currentRoute === '/chat'}
                    <Chat />
                {:else}
                <div class="welcome-container">
                    <h1>Bienvenido a Chat App</h1>
                    <p>Inicia sesión o regístrate para comenzar a chatear</p>
                    <div class="button-container">
                        <button class="btn" on:click={goToLogin}>Iniciar Sesión</button>
                        <button class="btn btn-register" on:click={goToRegister}>Registrarse</button>
                    </div>
                </div>
                {/if}
            {/if}
        </div>
    {/if}
</main>


<style>
    main {
        width: 100%;
        min-height: 100vh;
    }
    
    .app-container {
        width: 100%;
        min-height: 100vh;
        position: relative;
    }
    
    .loading {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f8f9fa;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3490dc;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .welcome-container {
        text-align: center;
        max-width: 500px;
        padding: 2rem;
        border-radius: 8px;
        background-color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin: 100px auto;
    }
    
    .welcome-container h1 {
        margin-bottom: 1rem;
        color: #3490dc;
    }
    
    .welcome-container p {
        margin-bottom: 2rem;
        color: #666;
    }
    
    .button-container {
        display: flex;
        justify-content: center;
        gap: 1rem;
    }
    
    .btn {
        padding: 10px 20px;
        background: #3490dc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
    }
    
    .btn:hover {
        background: #2779bd;
    }
    
    .btn-register {
        background: #4caf50;
    }
    
    .btn-register:hover {
        background: #388e3c;
    }
    
    .debug-toggle {
        position: fixed;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8rem;
        z-index: 9999;
    }
    
    .debug-toggle:hover {
        background: #5a6268;
    }
    
    .debug-panel {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(248, 249, 250, 0.95);
        padding: 20px;
        overflow: auto;
        z-index: 9998;
    }
    
    .debug-panel h3 {
        margin-bottom: 15px;
        color: #3490dc;
    }
    
    .debug-actions {
        display: flex;
        gap: 10px;
        margin: 15px 0;
    }
    
    .debug-actions .btn {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
</style>