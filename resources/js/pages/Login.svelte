<script>
    import { onMount } from 'svelte';
    import { navigate } from '../lib/router.js';
    import { user } from '../stores/auth.js';
    import api from '../lib/api.js';
    
    let email = '';
    let password = '';
    let error = '';
    let loading = false;
    let apiUrl = '';
    
    onMount(() => {
        // Detectar la URL base
        const currentPath = window.location.pathname;
        let baseURL = window.location.origin;

        // Si estamos en un subdirectorio, ajustar la URL base para incluir ese directorio
        if (currentPath.includes('/laravel/chat-laravel/public')) {
            baseURL = `${baseURL}/laravel/chat-laravel/public`;
        }
        
        apiUrl = `${baseURL}/api/login`;
        console.log('Login API URL:', apiUrl);
        
        if ($user) {
            navigate('/chat');
        }
    });
    
    async function handleSubmit() {
        error = '';
        loading = true;
        console.log('Intentando iniciar sesión con:', { email, password });
        
        try {
            const response = await api.post('/api/login', {
                email,
                password
            });
            
            console.log('Respuesta de login:', response.data);
            
            if (response.data && response.data.token) {
                localStorage.setItem('token', response.data.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
                user.set(response.data.user);
                navigate('/chat');
            } else {
                error = 'Respuesta del servidor no válida';
                console.error('Respuesta inesperada:', response.data);
            }
        } catch (err) {
            console.error('Error completo:', err);
            
            if (err.response && err.response.data && err.response.data.message) {
                error = err.response.data.message;
            } else if (err.response && err.response.data && err.response.data.errors) {
                error = Object.values(err.response.data.errors)[0][0];
            } else if (err.response && err.response.status === 404) {
                error = 'No se pudo conectar al servidor API. Ruta no encontrada.';
            } else {
                error = 'Error al iniciar sesión. Revisa la conexión al servidor.';
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
        
        <div class="debug-info">
            <p>API URL: {apiUrl}</p>
        </div>
        
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
                    autocomplete="email"
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
                    autocomplete="current-password"
                />
            </div>
            
            <button type="submit" class="btn btn-block" disabled={loading}>
                {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
            </button>
        </form>
        
        <div class="auth-footer">
            <!-- svelte-ignore a11y-invalid-attribute -->
            <p>¿No tienes una cuenta? <a href="#" on:click|preventDefault={() => navigate('/register')}>Regístrate</a></p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
        background-color: #f8f9fa;
    }
    
    .auth-card {
        width: 100%;
        max-width: 400px;
        padding: 30px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    h1 {
        margin-bottom: 20px;
        color: #3490dc;
        text-align: center;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: border-color 0.2s;
    }
    
    .form-control:focus {
        border-color: #3490dc;
        outline: none;
    }
    
    .btn {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .btn:hover {
        background-color: #2779bd;
    }
    
    .btn:disabled {
        background-color: #a0aec0;
        cursor: not-allowed;
    }
    
    .alert {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .auth-footer {
        margin-top: 20px;
        text-align: center;
    }
    
    .auth-footer a {
        color: #3490dc;
        text-decoration: none;
    }
    
    .auth-footer a:hover {
        text-decoration: underline;
    }
    
    .debug-info {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f1f3f5;
        border-radius: 4px;
        font-size: 12px;
        color: #6c757d;
    }
</style>