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
            <div class="not-found">
                <h1>404</h1>
                <p>Página no encontrada</p>
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
    
    .not-found {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: #555;
    }
    
    .not-found h1 {
        font-size: 6rem;
        margin: 0;
    }

</style>