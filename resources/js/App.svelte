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
        {#if globalThis.$currentRoute === '/login'}
            <Login />
        {:else if globalThis.$currentRoute === '/register'}
            <Register />
        {:else if globalThis.$currentRoute === '/chat'}
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