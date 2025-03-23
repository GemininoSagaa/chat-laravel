<script>
    import { onMount } from 'svelte';
    import { writable } from 'svelte/store';
    import { navigate } from './lib/router.js';

    // Estado de autenticación
    const user = writable(null);
    let isLoading = true;

    onMount(async () => {
        try {
            const response = await window.axios.get('/api/user');
            user.set(response.data);
        } catch (error) {
            user.set(null);
        } finally {
            isLoading = false;
        }
    });
</script>

<main>
    {#if isLoading}
        <div class="loading">Cargando...</div>
    {:else}
        {#if $user}
            <slot />
        {:else}
            <div class="auth-container">
                <div class="auth-card">
                    <h1>Chat App</h1>
                    <div class="auth-tabs">
                        <button on:click={() => navigate('/login')}>Iniciar Sesión</button>
                        <button on:click={() => navigate('/register')}>Registrarse</button>
                    </div>
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
        font-size: 1.2rem;
        color: #555;
    }
    
    .auth-container {
        width: 100%;
        max-width: 400px;
        padding: 20px;
    }
    
    .auth-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .auth-card h1 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #333;
    }
    
    .auth-tabs {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .auth-tabs button {
        flex: 1;
        padding: 8px 0;
        background: #3490dc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .auth-tabs button:hover {
        background: #2779bd;
    }
</style>