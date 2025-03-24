<script>
    import { onMount } from 'svelte';
    import { navigate } from '../lib/router.js';
    import { user } from '../stores/auth.js';
    
    let email = '';
    let password = '';
    let error = '';
    let loading = false;
    
    onMount(() => {
        if ($user) {
            navigate('/chat');
        }
    });
    
    async function handleSubmit() {
        error = '';
        loading = true;
        
        try {
            const response = await window.axios.post('/api/login', {
                email,
                password
            });
            
            localStorage.setItem('token', response.data.token);
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
            user.set(response.data.user);
            navigate('/chat');
        } catch (err) {
            if (err.response && err.response.data && err.response.data.message) {
                error = err.response.data.message;
            } else if (err.response && err.response.data && err.response.data.errors) {
                error = Object.values(err.response.data.errors)[0][0];
            } else {
                error = 'Error al iniciar sesión. Inténtalo de nuevo.';
            }
        } finally {
            loading = false;
        }
    }
</script>

<div class="auth-container">
    <div class="auth-card">
        <h1>Iniciar Sesión</h1>
        
        {#if error}
            <div class="alert alert-danger">{error}</div>
        {/if}
        
        <form on:submit|preventDefault={handleSubmit}>
            <div class="form-group">
                <label for="email" class="form-label">Correo electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    class="form-control" 
                    placeholder="correo@ejemplo.com" 
                    bind:value={email} 
                    required
                />
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    class="form-control" 
                    placeholder="Tu contraseña" 
                    bind:value={password} 
                    required
                />
            </div>
            
            <button type="submit" class="btn btn-block" disabled={loading}>
                {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
            </button>
        </form>
        
        <div class="auth-footer">
            <!-- svelte-ignore a11y_invalid_attribute -->
            <!-- svelte-ignore a11y-invalid-attribute -->
            <p>¿No tienes una cuenta? <a href="#" on:click|preventDefault={() => navigate('/register')}>Regístrate</a></p>
        </div>
    </div>
</div>

<style>
    .auth-footer {
        margin-top: 1.5rem;
        text-align: center;
    }
    
    .auth-footer a {
        color: #3490dc;
        text-decoration: none;
    }
    
    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>