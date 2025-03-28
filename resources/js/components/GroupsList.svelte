<script>
    export let groups = [];
    export let selectedChat = null;
    export let onSelectChat;
    export let onShowCreateGroup;
</script>

<div class="section">
    <div class="section-header">
        <h4>Grupos</h4>
        <button class="btn-sm" on:click={onShowCreateGroup}>Nuevo</button>
    </div>
    <ul class="group-list">
        {#each groups as group}
            <!-- svelte-ignore a11y-click-events-have-key-events -->
            <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
            <li 
                class="group-item"
                class:active={selectedChat && selectedChat.isGroup && selectedChat.id === group.id}
                on:click={() => onSelectChat(group, true)}
            >
                <span class="group-name">{group.name}</span>
            </li>
        {/each}
        
        {#if groups.length === 0}
            <li class="empty-list">No tienes grupos aún</li>
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
    
    .btn-sm {
        padding: 3px 8px;
        background-color: #3490dc;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.8rem;
    }
    
    .group-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .group-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    
    .group-item:hover {
        background-color: #e8e8e8;
    }
    
    .group-item.active {
        background-color: #e3f2fd;
    }
    
    .empty-list {
        padding: 10px 15px;
        color: #999;
        font-style: italic;
    }
</style>