<script>
    import { onMount } from 'svelte';
    import { writable } from 'svelte/store';
    import { navigate, currentRoute } from './lib/router.js';
    import Login from './pages/Login.svelte';
    import Register from './pages/Register.svelte';
    import Chat from './pages/Chat.svelte';

    // Estado de autenticación
    const user = writable(null);
    const isLoading = writable(true);

    onMount(async () => {
        try {
            const response = await window.axios.get('/api/user');
            user.set(response.data);
        } catch (error) {
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

    console.log("Ruta actual:", $currentRoute);
</script>

<main>
    {#if $isLoading}
        <div class="loading">Cargando...</div>
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
</main>


<style>

    main {
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .loading {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        font-size: 1.2rem;
        color: #555;
    }
    
    .welcome-container {
        text-align: center;
        max-width: 500px;
        padding: 2rem;
        border-radius: 8px;
        background-color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

</style>