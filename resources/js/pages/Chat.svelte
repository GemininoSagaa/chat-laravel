<script>
    import { onMount, onDestroy } from 'svelte';
    import { navigate } from '../lib/router.js';
    import { user } from '../stores/auth.js';
    
    // Variables de estado
    let isLoading = true;
    let contacts = [];
    let groups = [];
    let selectedChat = null;
    let messages = [];
    let newMessage = '';
    
    // Variables para modales
    let showAddFriend = false;
    let showFriendRequests = false;
    let showCreateGroup = false;
    
    onMount(async () => {
        if (!$user) {
            navigate('/login');
            return;
        }
        
        try {
            isLoading = true;
            console.log("Iniciando carga de datos");
            
            // Intentar cargar datos básicos
            await loadInitialData();
            
        } catch (error) {
            console.error('Error en Chat.svelte:', error);
        } finally {
            isLoading = false;
        }
    });
    
    async function loadInitialData() {
        try {
            // Cargar contactos
            const friendsResponse = await window.axios.get('/api/friends');
            console.log('Respuesta de amigos:', friendsResponse.data);
            contacts = friendsResponse.data.friends || [];
            
            // Cargar grupos
            const groupsResponse = await window.axios.get('/api/groups');
            console.log('Respuesta de grupos:', groupsResponse.data);
            groups = groupsResponse.data.groups || [];
            
        } catch (error) {
            console.error('Error al cargar datos iniciales:', error);
            contacts = [];
            groups = [];
        }
    }
    
    function openAddFriendModal() {
        showAddFriend = true;
        showFriendRequests = false;
        showCreateGroup = false;
    }
    
    function closeAddFriendModal() {
        showAddFriend = false;
    }
    
    function openFriendRequestsModal() {
        showFriendRequests = true;
        showAddFriend = false;
        showCreateGroup = false;
    }
    
    function closeFriendRequestsModal() {
        showFriendRequests = false;
    }
    
    function openCreateGroupModal() {
        showCreateGroup = true;
        showAddFriend = false;
        showFriendRequests = false;
    }
    
    function closeCreateGroupModal() {
        showCreateGroup = false;
    }
    
    async function handleLogout() {
        try {
            await window.axios.post('/api/logout');
            localStorage.removeItem('token');
            user.set(null);
            navigate('/login');
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
        }
    }
</script>

<div class="chat-container">
    {#if isLoading}
        <div class="loading-screen">
            <div class="spinner"></div>
            <p>Cargando... Por favor espere</p>
        </div>
    {:else}
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-info">
                <h3>{$user ? $user.name : 'Usuario'}</h3>
                <div class="action-buttons">
                    <button class="btn-icon" on:click={openFriendRequestsModal}>
                        <span class="icon">👥</span>
                    </button>
                    <button class="btn-icon" on:click={openAddFriendModal}>
                        <span class="icon">➕</span>
                    </button>
                    <button class="btn-icon" on:click={handleLogout}>
                        <span class="icon">🚪</span>
                    </button>
                </div>
            </div>
            
            <!-- Lista de contactos -->
            <div class="section">
                <div class="section-header">
                    <h4>Contactos</h4>
                    <button class="btn-add" on:click={openAddFriendModal}>Añadir</button>
                </div>
                <div class="contact-list">
                    {#if contacts && contacts.length > 0}
                        {#each contacts as contact}
                            <div class="contact-item">
                                <span class="contact-name">{contact.name}</span>
                                <span class="status-dot" class:online={contact.status === 'online'}></span>
                            </div>
                        {/each}
                    {:else}
                        <div class="empty-list">No tienes contactos</div>
                    {/if}
                </div>
            </div>
            
            <!-- Lista de grupos -->
            <div class="section">
                <div class="section-header">
                    <h4>Grupos</h4>
                    <button class="btn-add" on:click={openCreateGroupModal}>Crear</button>
                </div>
                <div class="group-list">
                    {#if groups && groups.length > 0}
                        {#each groups as group}
                            <div class="group-item">
                                <span class="group-name">{group.name}</span>
                            </div>
                        {/each}
                    {:else}
                        <div class="empty-list">No tienes grupos</div>
                    {/if}
                </div>
            </div>
        </div>
        
        <!-- Área de chat -->
        <div class="chat-area">
            <div class="empty-chat">
                <p>Selecciona un chat para comenzar a conversar</p>
            </div>
        </div>
        
        <!-- Modales -->
        {#if showAddFriend}
            <div class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Añadir Amigo</h3>
                        <button class="close-btn" on:click={closeAddFriendModal}>×</button>
                    </div>
                    <div class="modal-body">
                        <p>Ingresa el correo electrónico del amigo que deseas añadir</p>
                        <input type="email" placeholder="correo@ejemplo.com" class="input-field" />
                    </div>
                    <div class="modal-footer">
                        <button class="btn">Enviar Solicitud</button>
                        <button class="btn btn-cancel" on:click={closeAddFriendModal}>Cancelar</button>
                    </div>
                </div>
            </div>
        {/if}
        
        {#if showFriendRequests}
            <div class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Solicitudes de Amistad</h3>
                        <button class="close-btn" on:click={closeFriendRequestsModal}>×</button>
                    </div>
                    <div class="modal-body">
                        <div class="empty-list">No tienes solicitudes pendientes</div>
                    </div>
                </div>
            </div>
        {/if}
        
        {#if showCreateGroup}
            <div class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Crear Grupo</h3>
                        <button class="close-btn" on:click={closeCreateGroupModal}>×</button>
                    </div>
                    <div class="modal-body">
                        <p>Ingresa el nombre del grupo</p>
                        <input type="text" placeholder="Nombre del grupo" class="input-field" />
                        <p>Selecciona los miembros</p>
                        <div class="members-list">
                            {#if contacts && contacts.length > 0}
                                {#each contacts as contact}
                                    <div class="member-item">
                                        <label>
                                            <input type="checkbox" />
                                            {contact.name}
                                        </label>
                                    </div>
                                {/each}
                            {:else}
                                <div class="empty-list">No tienes amigos para agregar</div>
                            {/if}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn">Crear Grupo</button>
                        <button class="btn btn-cancel" on:click={closeCreateGroupModal}>Cancelar</button>
                    </div>
                </div>
            </div>
        {/if}
    {/if}
</div>

<style>
    .chat-container {
        display: flex;
        height: 100vh;
        width: 100%;
    }
    
    .loading-screen {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;
        background-color: rgba(255, 255, 255, 0.9);
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
    
    .sidebar {
        width: 300px;
        background-color: #f5f5f5;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    .user-info {
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #e9ecef;
        border-bottom: 1px solid #ddd;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 5px;
        border-radius: 50%;
        transition: background-color 0.2s;
    }
    
    .btn-icon:hover {
        background-color: #ddd;
    }
    
    .section {
        margin-bottom: 10px;
    }
    
    .section-header {
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f1f3f5;
    }
    
    .btn-add {
        padding: 3px 8px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.8rem;
    }
    
    .contact-list, .group-list {
        padding: 0;
    }
    
    .contact-item, .group-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .contact-item:hover, .group-item:hover {
        background-color: #e9ecef;
    }
    
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #adb5bd;
    }
    
    .status-dot.online {
        background-color: #2ecc71;
    }
    
    .empty-list {
        padding: 15px;
        color: #6c757d;
        text-align: center;
        font-style: italic;
    }
    
    .chat-area {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #fff;
    }
    
    .empty-chat {
        color: #6c757d;
        text-align: center;
    }
    
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    
    .modal-content {
        background-color: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .modal-header {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 15px;
        overflow-y: auto;
        flex: 1;
    }
    
    .input-field {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .members-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-top: 10px;
    }
    
    .member-item {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }
    
    .member-item:last-child {
        border-bottom: none;
    }
    
    .member-item label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .modal-footer {
        padding: 15px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    .btn {
        padding: 8px 16px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-cancel {
        background-color: #6c757d;
    }
</style>