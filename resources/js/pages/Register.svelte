<script>
    import { onMount } from 'svelte';
    import { navigate } from '../lib/router.js';
    import { user } from '../stores/auth.js';
    
    let name = '';
    let email = '';
    let password = '';
    let password_confirmation = '';
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
        
        if (password !== password_confirmation) {
            error = 'Las contraseñas no coinciden';
            loading = false;
            return;
        }
        
        try {
            const response = await window.axios.post('/register', {
                name,
                email,
                password,
                password_confirmation
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
                error = 'Error al registrarse. Inténtalo de nuevo.';
            }
        } finally {
            loading = false;
        }
    }
</script>

<div class="auth-container">
    <div class="auth-card">
        <h1>Registro</h1>
        
        {#if error}
            <div class="alert alert-danger">{error}</div>
        {/if}
        
        <form on:submit|preventDefault={handleSubmit}>
            <div class="form-group">
                <label for="name" class="form-label">Nombre</label>
                <input 
                    type="text" 
                    id="name" 
                    class="form-control" 
                    placeholder="Tu nombre" 
                    bind:value={name} 
                    required
                />
            </div>
            
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
                    minlength="8"
                />
            </div>
            
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    class="form-control" 
                    placeholder="Confirmar contraseña" 
                    bind:value={password_confirmation} 
                    required
                    minlength="8"
                />
            </div>
            
            <button type="submit" class="btn btn-block" disabled={loading}>
                {loading ? 'Registrando...' : 'Registrarse'}
            </button>
        </form>
        
        <div class="auth-footer">
            <!-- svelte-ignore a11y-invalid-attribute -->
            <p>¿Ya tienes una cuenta? <a href="#" on:click|preventDefault={() => navigate('/login')}>Iniciar sesión</a></p>
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