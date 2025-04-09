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
    
    // Variables para las solicitudes de amistad
    let friendRequests = {
        sent: [],
        received: []
    };
    
    // Variables para modales
    let showAddFriend = false;
    let showFriendRequests = false;
    let showCreateGroup = false;
    
    // Variables para el modal de añadir amigos
    let friendEmail = '';
    let addFriendError = '';
    let addFriendSuccess = '';
    let isSubmitting = false;
    
    // Variables para el modal de crear grupo
    let groupName = '';
    let selectedMembers = {};
    let createGroupError = '';
    
    // Configurar Echo para escuchar eventos en tiempo real
    let echoSetup = false;
    
    onMount(async () => {
        console.log('[CHAT] Componente montado');
        
        if (!$user) {
            console.log('[CHAT] Usuario no autenticado, redirigiendo a login');
            navigate('/login');
            return;
        }
        
        try {
            isLoading = true;
            console.log("[CHAT] Iniciando carga de datos del usuario:", $user.id);
            
            // Intentar cargar datos básicos
            await loadInitialData();
            
            // Configurar Echo solo una vez
            if (!echoSetup) {
                setupEchoChannels();
                echoSetup = true;
            }
            
        } catch (error) {
            console.error('[CHAT] Error al inicializar chat:', error);
        } finally {
            isLoading = false;
        }
    });
    
    onDestroy(() => {
        console.log('[CHAT] Componente desmontado, limpiando listeners');
        // Limpiar listeners de Echo si es necesario
        if (echoSetup && window.Echo) {
            // Ejemplo: window.Echo.leave('chat.channel');
        }
    });
    
    async function loadInitialData() {
        console.log('[CHAT] Cargando datos iniciales');
        
        try {
            // Cargar contactos y solicitudes de amistad
            const friendsResponse = await window.axios.get('/api/friends');
            console.log('[CHAT] Respuesta de amigos:', friendsResponse.data);
            
            contacts = friendsResponse.data.friends || [];
            friendRequests.sent = friendsResponse.data.sent_requests || [];
            friendRequests.received = friendsResponse.data.received_requests || [];
            
            console.log('[CHAT] Amigos cargados:', contacts.length);
            console.log('[CHAT] Solicitudes enviadas:', friendRequests.sent.length);
            console.log('[CHAT] Solicitudes recibidas:', friendRequests.received.length);
            
            // Cargar grupos
            const groupsResponse = await window.axios.get('/api/groups');
            console.log('[CHAT] Respuesta de grupos:', groupsResponse.data);
            groups = groupsResponse.data.groups || [];
            console.log('[CHAT] Grupos cargados:', groups.length);
            
        } catch (error) {
            console.error('[CHAT] Error al cargar datos iniciales:', error);
            contacts = [];
            groups = [];
            friendRequests = { sent: [], received: [] };
        }
    }
    
    function setupEchoChannels() {
        console.log('[ECHO] Configurando canales de tiempo real');
        
        if (!window.Echo) {
            console.error('[ECHO] Echo no está disponible');
            return;
        }
        
        try {
            // Escuchar mensajes privados
            const privateChannel = `chat.${$user.id}`;
            console.log(`[ECHO] Suscribiéndose al canal privado: ${privateChannel}`);
            
            window.Echo.private(privateChannel)
                .listen('MessageSent', (event) => {
                    console.log('[ECHO] Nuevo mensaje recibido:', event);
                    handleNewMessage(event.message);
                })
                .listen('UserTyping', (event) => {
                    console.log('[ECHO] Usuario escribiendo:', event);
                    // Manejar indicador de escritura
                });
                
            console.log('[ECHO] Canales configurados correctamente');
        } catch (error) {
            console.error('[ECHO] Error al configurar canales de Echo:', error);
        }
    }
    
    function handleNewMessage(message) {
        console.log('[CHAT] Manejando nuevo mensaje:', message);
        // Implementar lógica para añadir mensaje al chat activo
    }
    
    // Funciones para modales
    function openAddFriendModal() {
        console.log('[MODAL] Abriendo modal para añadir amigo');
        showAddFriend = true;
        showFriendRequests = false;
        showCreateGroup = false;
        
        // Resetear estado del formulario
        friendEmail = '';
        addFriendError = '';
        addFriendSuccess = '';
    }
    
    function closeAddFriendModal() {
        console.log('[MODAL] Cerrando modal para añadir amigo');
        showAddFriend = false;
    }
    
    function openFriendRequestsModal() {
        console.log('[MODAL] Abriendo modal de solicitudes de amistad');
        showFriendRequests = true;
        showAddFriend = false;
        showCreateGroup = false;
    }
    
    function closeFriendRequestsModal() {
        console.log('[MODAL] Cerrando modal de solicitudes de amistad');
        showFriendRequests = false;
    }
    
    function openCreateGroupModal() {
        console.log('[MODAL] Abriendo modal para crear grupo');
        showCreateGroup = true;
        showAddFriend = false;
        showFriendRequests = false;
        
        // Resetear formulario
        groupName = '';
        selectedMembers = {};
        createGroupError = '';
    }
    
    function closeCreateGroupModal() {
        console.log('[MODAL] Cerrando modal para crear grupo');
        showCreateGroup = false;
    }
    
    // Funciones de amistad
    async function sendFriendRequest() {
        console.log('[FRIEND] Enviando solicitud de amistad a:', friendEmail);
        
        // Resetear mensajes
        addFriendError = '';
        addFriendSuccess = '';
        isSubmitting = true;
        
        try {
            const response = await window.axios.post('/api/friends/request', {
                email: friendEmail
            });
            
            console.log('[FRIEND] Respuesta de solicitud de amistad:', response.data);
            
            // Mostrar mensaje de éxito
            addFriendSuccess = 'Solicitud enviada correctamente';
            
            // Limpiar el campo de correo
            friendEmail = '';
            
            // Recargar datos de amistades después de un breve tiempo
            setTimeout(async () => {
                await loadInitialData();
                closeAddFriendModal();
            }, 2000);
            
        } catch (error) {
            console.error('[FRIEND] Error al enviar solicitud de amistad:', error);
            
            if (error.response && error.response.data && error.response.data.message) {
                addFriendError = error.response.data.message;
            } else {
                addFriendError = 'Error al enviar la solicitud. Intenta nuevamente.';
            }
        } finally {
            isSubmitting = false;
        }
    }
    
    async function acceptFriendRequest(userId) {
        console.log('[FRIEND] Aceptando solicitud de amistad de usuario ID:', userId);
        
        try {
            const response = await window.axios.post(`/api/friends/accept/${userId}`);
            console.log('[FRIEND] Respuesta al aceptar solicitud:', response.data);
            
            // Recargar datos
            await loadInitialData();
            
        } catch (error) {
            console.error('[FRIEND] Error al aceptar solicitud de amistad:', error);
        }
    }
    
    async function rejectFriendRequest(userId) {
        console.log('[FRIEND] Rechazando solicitud de amistad de usuario ID:', userId);
        
        try {
            const response = await window.axios.post(`/api/friends/reject/${userId}`);
            console.log('[FRIEND] Respuesta al rechazar solicitud:', response.data);
            
            // Recargar datos
            await loadInitialData();
            
        } catch (error) {
            console.error('[FRIEND] Error al rechazar solicitud de amistad:', error);
        }
    }
    
    // Funciones de grupo
    async function createGroup() {
        console.log('[GROUP] Creando grupo:', groupName);
        
        try {
            // Convertir objeto de miembros seleccionados a array de IDs
            const members = Object.keys(selectedMembers).filter(id => selectedMembers[id]);
            console.log('[GROUP] Miembros seleccionados:', members);
            
            if (members.length === 0) {
                createGroupError = 'Debes seleccionar al menos un miembro para el grupo';
                return;
            }
            
            const response = await window.axios.post('/api/groups', {
                name: groupName,
                members: members
            });
            
            console.log('[GROUP] Grupo creado:', response.data);
            
            // Recargar datos y cerrar modal
            await loadInitialData();
            closeCreateGroupModal();
            
        } catch (error) {
            console.error('[GROUP] Error al crear grupo:', error);
            
            if (error.response && error.response.data && error.response.data.message) {
                createGroupError = error.response.data.message;
            } else {
                createGroupError = 'Error al crear el grupo. Intenta nuevamente.';
            }
        }
    }
    
    // Funciones de chat
    function selectChat(chat, isGroup) {
        console.log('[CHAT] Seleccionando chat:', chat, 'Es grupo:', isGroup);
        selectedChat = { ...chat, isGroup };
        loadChatMessages(chat.id, isGroup);
    }
    
    async function loadChatMessages(chatId, isGroup) {
        console.log('[CHAT] Cargando mensajes para', isGroup ? 'grupo' : 'usuario', 'ID:', chatId);
        
        try {
            const endpoint = isGroup 
                ? `/api/groups/${chatId}/messages`
                : `/api/messages/${chatId}`;
                
            const response = await window.axios.get(endpoint);
            console.log('[CHAT] Mensajes cargados:', response.data);
            
            messages = response.data.messages || [];
            
        } catch (error) {
            console.error('[CHAT] Error al cargar mensajes:', error);
            messages = [];
        }
    }
    
    async function sendMessage() {
        if (!newMessage.trim() || !selectedChat) return;
        
        console.log('[CHAT] Enviando mensaje a', selectedChat.isGroup ? 'grupo' : 'usuario', 'ID:', selectedChat.id);
        
        try {
            const endpoint = selectedChat.isGroup
                ? `/api/groups/${selectedChat.id}/messages`
                : `/api/messages/${selectedChat.id}`;
                
            const response = await window.axios.post(endpoint, {
                content: newMessage
            });
            
            console.log('[CHAT] Mensaje enviado:', response.data);
            
            // Añadir mensaje a la lista local
            if (response.data.message) {
                messages = [...messages, response.data.message];
            }
            
            // Limpiar campo de mensaje
            newMessage = '';
            
        } catch (error) {
            console.error('[CHAT] Error al enviar mensaje:', error);
        }
    }
    
    // Función de cierre de sesión
    async function handleLogout() {
        console.log('[AUTH] Iniciando cierre de sesión');
        
        try {
            await window.axios.post('/api/logout');
            console.log('[AUTH] Sesión cerrada en el servidor');
            
            localStorage.removeItem('token');
            user.set(null);
            navigate('/login');
            
        } catch (error) {
            console.error('[AUTH] Error al cerrar sesión:', error);
            
            // Forzar cierre de sesión local en caso de error
            localStorage.removeItem('token');
            user.set(null);
            navigate('/login');
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
                    <button class="btn-icon" on:click={openFriendRequestsModal} title="Solicitudes de amistad">
                        <span class="icon">👥</span>
                        {#if friendRequests.received.length > 0}
                            <span class="badge">{friendRequests.received.length}</span>
                        {/if}
                    </button>
                    <button class="btn-icon" on:click={openAddFriendModal} title="Añadir amigo">
                        <span class="icon">➕</span>
                    </button>
                    <button class="btn-icon" on:click={handleLogout} title="Cerrar sesión">
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
                            <!-- svelte-ignore a11y-click-events-have-key-events -->
                            <!-- svelte-ignore a11y-no-static-element-interactions -->
                            <div 
                                class="contact-item" 
                                class:active={selectedChat && !selectedChat.isGroup && selectedChat.id === contact.id}
                                on:click={() => selectChat(contact, false)}
                            >
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
                            <!-- svelte-ignore a11y-click-events-have-key-events -->
                            <!-- svelte-ignore a11y-no-static-element-interactions -->
                            <div 
                                class="group-item"
                                class:active={selectedChat && selectedChat.isGroup && selectedChat.id === group.id}
                                on:click={() => selectChat(group, true)}
                            >
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
            {#if selectedChat}
                <div class="chat-header">
                    <h3>{selectedChat.name}</h3>
                    {#if !selectedChat.isGroup && selectedChat.status}
                        <span class="status-indicator" class:online={selectedChat.status === 'online'}>
                            {selectedChat.status === 'online' ? 'en línea' : 'desconectado'}
                        </span>
                    {/if}
                </div>
                
                <div class="messages-container">
                    {#if messages.length > 0}
                        {#each messages as message}
                            <div class="message-item" class:sent={message.sender_id === $user.id} class:received={message.sender_id !== $user.id}>
                                <div class="message-content">{message.content}</div>
                                <div class="message-time">{new Date(message.created_at).toLocaleTimeString()}</div>
                            </div>
                        {/each}
                    {:else}
                        <div class="empty-messages">No hay mensajes aún. ¡Sé el primero en escribir!</div>
                    {/if}
                </div>
                
                <div class="message-input-container">
                    <input 
                        type="text" 
                        placeholder="Escribe un mensaje..." 
                        bind:value={newMessage}
                        on:keypress={(e) => e.key === 'Enter' && sendMessage()}
                    />
                    <button class="send-btn" on:click={sendMessage}>Enviar</button>
                </div>
            {:else}
                <div class="empty-chat">
                    <p>Selecciona un chat para comenzar a conversar</p>
                </div>
            {/if}
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
                        
                        {#if addFriendError}
                            <div class="alert alert-danger">{addFriendError}</div>
                        {/if}
                        
                        {#if addFriendSuccess}
                            <div class="alert alert-success">{addFriendSuccess}</div>
                        {/if}
                        
                        <input 
                            type="email" 
                            placeholder="correo@ejemplo.com" 
                            class="input-field"
                            bind:value={friendEmail} 
                            disabled={isSubmitting}
                        />
                    </div>
                    <div class="modal-footer">
                        <button 
                            class="btn" 
                            on:click={sendFriendRequest} 
                            disabled={isSubmitting || !friendEmail}
                        >
                            {isSubmitting ? 'Enviando...' : 'Enviar Solicitud'}
                        </button>
                        <button 
                            class="btn btn-cancel" 
                            on:click={closeAddFriendModal}
                            disabled={isSubmitting}
                        >
                            Cancelar
                        </button>
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
                        <h4>Solicitudes recibidas</h4>
                        {#if friendRequests.received && friendRequests.received.length > 0}
                            <div class="requests-list">
                                {#each friendRequests.received as request}
                                    <div class="request-item">
                                        <span class="request-name">{request.user.name}</span>
                                        <div class="request-actions">
                                            <button class="btn btn-sm btn-accept" on:click={() => acceptFriendRequest(request.user.id)}>Aceptar</button>
                                            <button class="btn btn-sm btn-reject" on:click={() => rejectFriendRequest(request.user.id)}>Rechazar</button>
                                        </div>
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <div class="empty-list">No tienes solicitudes pendientes</div>
                        {/if}
                        
                        <h4>Solicitudes enviadas</h4>
                        {#if friendRequests.sent && friendRequests.sent.length > 0}
                            <div class="requests-list">
                                {#each friendRequests.sent as request}
                                    <div class="request-item">
                                        <span class="request-name">{request.friend.name}</span>
                                        <span class="request-status">Pendiente</span>
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <div class="empty-list">No tienes solicitudes enviadas</div>
                        {/if}
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
                        
                        {#if createGroupError}
                            <div class="alert alert-danger">{createGroupError}</div>
                        {/if}
                        
                        <input 
                            type="text" 
                            placeholder="Nombre del grupo" 
                            class="input-field"
                            bind:value={groupName}
                        />
                        
                        <p>Selecciona los miembros</p>
                        <div class="members-list">
                            {#if contacts && contacts.length > 0}
                                {#each contacts as contact}
                                    <div class="member-item">
                                        <label>
                                            <input 
                                                type="checkbox" 
                                                bind:checked={selectedMembers[contact.id]} 
                                            />
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
                        <button 
                            class="btn" 
                            on:click={createGroup}
                            disabled={!groupName}
                        >
                            Crear Grupo
                        </button>
                        <button class="btn btn-cancel" on:click={closeCreateGroupModal}>
                            Cancelar
                        </button>
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