<script>
    export let contacts = [];
    export let selectedChat = null;
    export let onSelectChat;
    export let onShowAddFriend;
</script>

<div class="section">
    <div class="section-header">
        <h4>Chats</h4>
        <button class="btn-icon" on:click={onShowAddFriend}>
            <span class="icon">➕</span>
        </button>
    </div>
    <ul class="contact-list">
        {#each contacts as contact}
            <!-- svelte-ignore a11y-click-events-have-key-events -->
            <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
            <li 
                class="contact-item"
                class:active={selectedChat && !selectedChat.isGroup && selectedChat.id === contact.id}
                on:click={() => onSelectChat(contact, false)}
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

<style>
    .section {
        margin-bottom: 10px;
    }
    
    .section-header {
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    .contact-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    
    .contact-item:hover {
        background-color: #e8e8e8;
    }
    
    .contact-item.active {
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
</style>