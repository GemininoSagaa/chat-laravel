<script>
    import { onMount, onDestroy } from 'svelte';
    import { user, logout } from '../stores/auth.js';
    import { navigate } from '../lib/router.js';
    
    let contacts = [];
    let groups = [];
    let selectedChat = null;
    let messages = [];
    let newMessage = '';
    let typingTimeout;
    let typingUsers = {};
    
    // Variables para crear grupo
    let showCreateGroup = false;
    let groupName = '';
    let selectedMembers = [];
    
    // Variable para agregar amigo
    let showAddFriend = false;
    let friendEmail = '';
    let addFriendError = '';
    let addFriendSuccess = '';
    
    // Variable para solicitudes de amistad
    let friendRequests = [];
    let showFriendRequests = false;
    
    onMount(async () => {
        if (!$user) {
            navigate('/login');
            return;
        }
        
        // Cargar amigos y grupos
        await loadFriends();
        await loadGroups();
        await loadFriendRequests();
        
        // Escuchar eventos en tiempo real
        listenForMessages();
        listenForTyping();
    });
    
    onDestroy(() => {
        // Limpiar cualquier timeout
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }
        
        // Desuscribirse de canales si es necesario
        if (window.Echo) {
            window.Echo.leaveAllChannels();
        }
    });
    
    async function loadFriends() {
        try {
            const response = await window.axios.get('/api/friends');
            contacts = response.data.friends;
        } catch (error) {
            console.error('Error al cargar amigos:', error);
        }
    }
    
    async function loadGroups() {
        try {
            const response = await window.axios.get('/api/groups');
            groups = response.data.groups;
        } catch (error) {
            console.error('Error al cargar grupos:', error);
        }
    }
    
    async function loadFriendRequests() {
        try {
            const response = await window.axios.get('/api/friends');
            friendRequests = response.data.received_requests;
        } catch (error) {
            console.error('Error al cargar solicitudes de amistad:', error);
        }
    }
    
    function listenForMessages() {
        if (!window.Echo) return;
        
        // Escuchar mensajes directos
        window.Echo.private(`chat.${$user.id}.${selectedChat?.id}`)
            .listen('MessageSent', (e) => {
                messages = [...messages, e.message];
            });
            
        // Si es un grupo, escuchar mensajes del grupo
        if (selectedChat?.group_id) {
            window.Echo.private(`group.${selectedChat.group_id}`)
                .listen('MessageSent', (e) => {
                    messages = [...messages, e.message];
                });
        }
    }
    
    function listenForTyping() {
        if (!window.Echo || !selectedChat) return;
        
        window.Echo.private(`chat.${$user.id}.${selectedChat.id}`)
            .listen('UserTyping', (e) => {
                const userId = e.senderId;
                
                // Actualizar estado de escritura
                typingUsers = { ...typingUsers, [userId]: true };
                
                // Limpiar después de 3 segundos
                setTimeout(() => {
                    typingUsers = { ...typingUsers, [userId]: false };
                }, 3000);
            });
    }
    
    async function selectChat(chat, isGroup = false) {
        // Limpiar chat anterior
        messages = [];
        selectedChat = { ...chat, isGroup };
        
        try {
            let response;
            
            if (isGroup) {
                response = await window.axios.get(`/api/groups/${chat.id}/messages`);
            } else {
                response = await window.axios.get(`/api/messages/${chat.id}`);
            }
            
            messages = response.data.messages;
            
            // Reiniciar los listeners
            if (window.Echo) {
                window.Echo.leaveAllChannels();
                listenForMessages();
                listenForTyping();
            }
        } catch (error) {
            console.error('Error al cargar mensajes:', error);
        }
    }
    
    function handleTyping() {
        if (!selectedChat || !$user) return;
        
        // Enviar evento de escritura
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }
        
        // Notificar solo una vez cada 2 segundos para no saturar
        typingTimeout = setTimeout(async () => {
            try {
                await window.axios.post(`/api/user/typing/${selectedChat.id}`);
            } catch (error) {
                console.error('Error al enviar estado de escritura:', error);
            }
        }, 2000);
    }
    
    async function sendMessage() {
        if (!newMessage.trim() || !selectedChat) return;
        
        try {
            let response;
            
            if (selectedChat.isGroup) {
                response = await window.axios.post(`/api/groups/${selectedChat.id}/messages`, {
                    content: newMessage
                });
            } else {
                response = await window.axios.post(`/api/messages/${selectedChat.id}`, {
                    content: newMessage
                });
            }
            
            // Añadir el mensaje a la lista
            messages = [...messages, response.data.message];
            newMessage = '';
        } catch (error) {
            console.error('Error al enviar mensaje:', error);
        }
    }
    
    async function createGroup() {
        if (!groupName.trim() || selectedMembers.length === 0) return;
        
        try {
            const response = await window.axios.post('/api/groups', {
                name: groupName,
                members: selectedMembers
            });
            
            groups = [...groups, response.data.group];
            groupName = '';
            selectedMembers = [];
            showCreateGroup = false;
        } catch (error) {
            console.error('Error al crear grupo:', error);
        }
    }
    
    async function addFriend() {
        if (!friendEmail.trim()) return;
        
        addFriendError = '';
        addFriendSuccess = '';
        
        try {
            const response = await window.axios.post('/api/friends/request', {
                email: friendEmail
            });
            
            addFriendSuccess = 'Solicitud de amistad enviada';
            friendEmail = '';
            setTimeout(() => {
                addFriendSuccess = '';
                showAddFriend = false;
            }, 2000);
        } catch (error) {
            if (error.response && error.response.data && error.response.data.message) {
                addFriendError = error.response.data.message;
            } else {
                addFriendError = 'Error al enviar solicitud de amistad';
            }
        }
    }
    
    async function acceptFriendRequest(userId) {
        try {
            await window.axios.post(`/api/friends/accept/${userId}`);
            
            // Recargar amigos y solicitudes
            await loadFriends();
            await loadFriendRequests();
        } catch (error) {
            console.error('Error al aceptar solicitud de amistad:', error);
        }
    }
    
    async function rejectFriendRequest(userId) {
        try {
            await window.axios.post(`/api/friends/reject/${userId}`);
            
            // Recargar solicitudes
            await loadFriendRequests();
        } catch (error) {
            console.error('Error al rechazar solicitud de amistad:', error);
        }
    }
    
    async function handleLogout() {
        await logout();
        navigate('/login');
    }
</script>

<div class="chat-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info">
            <h3>{$user ? $user.name : 'Usuario'}</h3>
            <div class="user-actions">
                <button class="btn-icon" on:click={() => showFriendRequests = true}>
                    <span class="icon">👥</span>
                    {#if friendRequests.length > 0}
                        <span class="badge">{friendRequests.length}</span>
                    {/if}
                </button>
                <button class="btn-icon" on:click={() => showAddFriend = true}>
                    <span class="icon">➕</span>
                </button>
                <button class="btn-icon" on:click={handleLogout}>
                    <span class="icon">🚪</span>
                </button>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h4>Chats</h4>
            </div>
            <ul class="contact-list">
                <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
                <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
                {#each contacts as contact}
                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                    <!-- svelte-ignore a11y-click-events-have-key-events -->
                    <li 
                        class="contact-item"
                        class:active={selectedChat && !selectedChat.isGroup && selectedChat.id === contact.id}
                        on:click={() => selectChat(contact, false)}
                    >
                        <div class="contact-info">
                            <span class="contact-name">{contact.name}</span>
                            <span class="contact-status" class:online={contact.status === 'online'}>
                                {contact.status === 'online' ? '🟢' : '⚪'}
                            </span>
                        </div>
                    </li>
                {/each}
                
                {#if contacts.length === 0}
                    <li class="empty-list">No tienes amigos aún</li>
                {/if}
            </ul>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h4>Grupos</h4>
                <button class="btn-sm" on:click={() => showCreateGroup = true}>Nuevo</button>
            </div>
            <ul class="group-list">
                <!-- svelte-ignore a11y_click_events_have_key_events -->
                <!-- svelte-ignore a11y-click-events-have-key-events -->
                {#each groups as group}
                    <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
                    <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
                    <li 
                        class="group-item"
                        class:active={selectedChat && selectedChat.isGroup && selectedChat.id === group.id}
                        on:click={() => selectChat(group, true)}
                    >
                        <span class="group-name">{group.name}</span>
                    </li>
                {/each}
                
                {#if groups.length === 0}
                    <li class="empty-list">No tienes grupos aún</li>
                {/if}
            </ul>
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="chat-area">
        {#if selectedChat}
            <div class="chat-header">
                <h3>{selectedChat.name || selectedChat.email}</h3>
                <div class="chat-status">
                    {#if !selectedChat.isGroup && typingUsers[selectedChat.id]}
                        <span class="typing-indicator">escribiendo...</span>
                    {/if}
                </div>
            </div>
            
            <div class="messages">
                {#each messages as message}
                    <div class="message-item" class:sent={message.sender_id === $user.id} class:received={message.sender_id !== $user.id}>
                        <div class="message-content">{message.content}</div>
                        <div class="message-time">{new Date(message.created_at).toLocaleTimeString()}</div>
                    </div>
                {/each}
                
                {#if messages.length === 0}
                    <div class="empty-chat">No hay mensajes aún</div>
                {/if}
            </div>
            
            <div class="message-input">
                <input 
                    type="text" 
                    placeholder="Escribe un mensaje..." 
                    bind:value={newMessage} 
                    on:keyup={handleTyping}
                    on:keypress={(e) => e.key === 'Enter' && sendMessage()}
                />
                <button class="btn-send" on:click={sendMessage}>Enviar</button>
            </div>
        {:else}
            <div class="no-chat-selected">
                <p>Selecciona un chat para comenzar a conversar</p>
            </div>
        {/if}
    </div>
    
    <!-- Modal para crear grupo -->
    {#if showCreateGroup}
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Crear nuevo grupo</h3>
                    <button class="btn-close" on:click={() => showCreateGroup = false}>×</button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="group-name" class="form-label">Nombre del grupo</label>
                        <input 
                            type="text" 
                            id="group-name" 
                            class="form-control" 
                            placeholder="Nombre del grupo" 
                            bind:value={groupName} 
                            required
                        />
                    </div>
                    
                    <div class="form-group">
                        <!-- svelte-ignore a11y_label_has_associated_control -->
                        <!-- svelte-ignore a11y-label-has-associated-control -->
                        <label class="form-label">Seleccionar miembros</label>
                        <div class="members-list">
                            {#each contacts as contact}
                                <div class="member-item">
                                    <label>
                                        <input 
                                            type="checkbox" 
                                            value={contact.id} 
                                            bind:group={selectedMembers} 
                                        />
                                        {contact.name}
                                    </label>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="btn" on:click={createGroup}>Crear grupo</button>
                    <button class="btn btn-cancel" on:click={() => showCreateGroup = false}>Cancelar</button>
                </div>
            </div>
        </div>
    {/if}
    
    <!-- Modal para agregar amigo -->
    {#if showAddFriend}
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Agregar amigo</h3>
                    <button class="btn-close" on:click={() => showAddFriend = false}>×</button>
                </div>
                
                <div class="modal-body">
                    {#if addFriendError}
                        <div class="alert alert-danger">{addFriendError}</div>
                    {/if}
                    
                    {#if addFriendSuccess}
                        <div class="alert alert-success">{addFriendSuccess}</div>
                    {/if}
                    
                    <div class="form-group">
                        <label for="friend-email" class="form-label">Correo electrónico</label>
                        <input 
                            type="email" 
                            id="friend-email" 
                            class="form-control" 
                            placeholder="correo@ejemplo.com" 
                            bind:value={friendEmail} 
                            required
                        />
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="btn" on:click={addFriend}>Enviar solicitud</button>
                    <button class="btn btn-cancel" on:click={() => showAddFriend = false}>Cancelar</button>
                </div>
            </div>
        </div>
    {/if}
    
    <!-- Modal para solicitudes de amistad -->
    {#if showFriendRequests}
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Solicitudes de amistad</h3>
                    <button class="btn-close" on:click={() => showFriendRequests = false}>×</button>
                </div>
                
                <div class="modal-body">
                    {#if friendRequests.length > 0}
                        <ul class="requests-list">
                            {#each friendRequests as request}
                                <li class="request-item">
                                    <div class="request-info">
                                        <span class="request-name">{request.user.name}</span>
                                        <span class="request-email">{request.user.email}</span>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn-sm btn-accept" on:click={() => acceptFriendRequest(request.user.id)}>Aceptar</button>
                                        <button class="btn-sm btn-reject" on:click={() => rejectFriendRequest(request.user.id)}>Rechazar</button>
                                    </div>
                                </li>
                            {/each}
                        </ul>
                    {:else}
                        <p class="empty-list">No tienes solicitudes de amistad pendientes</p>
                    {/if}
                </div>
            </div>
        </div>
    {/if}
</div>

<style>
    .chat-container {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }
    
    /* Sidebar styles */
    .sidebar {
        width: 280px;
        background-color: #f5f5f5;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    .user-info {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .user-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        position: relative;
    }
    
    .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .section {
        margin-bottom: 10px;
    }
    
    .section-header {
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-sm {
        padding: 3px 8px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.8rem;
    }
    
    .contact-list, .group-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-item, .group-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    
    .contact-item:hover, .group-item:hover {
        background-color: #e8e8e8;
    }
    
    .contact-item.active, .group-item.active {
        background-color: #e3f2fd;
    }
    
    .contact-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .contact-status.online {
        color: #2ecc71;
    }
    
    .empty-list {
        padding: 10px 15px;
        color: #999;
        font-style: italic;
    }
    
    /* Chat area styles */
    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f9f9f9;
    }
    
    .typing-indicator {
        font-size: 0.8rem;
        color: #666;
        font-style: italic;
    }
    
    .messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    .message-item {
        max-width: 70%;
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
    }
    
    .message-item.sent {
        align-self: flex-end;
        background-color: #dcf8c6;
    }
    
    .message-item.received {
        align-self: flex-start;
        background-color: #f1f0f0;
    }
    
    .message-time {
        font-size: 0.7rem;
        color: #888;
        text-align: right;
        margin-top: 4px;
    }
    
    .empty-chat {
        align-self: center;
        color: #999;
        margin-top: 50px;
    }
    
    .message-input {
        padding: 15px;
        border-top: 1px solid #ddd;
        display: flex;
        background-color: #f9f9f9;
    }
    
    .message-input input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 20px;
        margin-right: 10px;
    }
    
    .btn-send {
        padding: 8px 16px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
    }
    
    .no-chat-selected {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #999;
    }
    
    /* Modal styles */
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
        width: 100%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .modal-footer {
        padding: 15px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    .btn-cancel {
        background-color: #e74c3c;
    }
    
    .members-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
    }
    
    .member-item {
        margin-bottom: 5px;
    }
    
    .requests-list {
        list-style: none;
        padding: 0;
    }
    
    .request-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .request-info {
        display: flex;
        flex-direction: column;
    }
    
    .request-email {
        font-size: 0.8rem;
        color: #999;
    }
    
    .request-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-accept {
        background-color: #2ecc71;
    }
    
    .btn-reject {
        background-color: #e74c3c;
    }
</style>