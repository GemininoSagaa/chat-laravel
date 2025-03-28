<script>
    export let isTyping = false;
    export let name = '';
</script>

{#if isTyping}
    <div class="typing-indicator">
        <span>{name || 'Alguien'} está escribiendo...</span>
    </div>
{/if}

<style>
    .typing-indicator {
        font-size: 0.8rem;
        color: #666;
        font-style: italic;
        padding: 5px;
    }
</style>