<script>
    export let message;
    export let currentUserId;

    // Determinar si el mensaje fue enviado por el usuario actual
    $: isSent = message.sender_id === currentUserId;
</script>

<div class="message-item" class:sent={isSent} class:received={!isSent}>
    <div class="message-content">{message.content}</div>
    <div class="message-time">{new Date(message.created_at).toLocaleTimeString()}</div>
</div>

<style>
    .message-item {
        max-width: 70%;
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
    }
    
    .sent {
        align-self: flex-end;
        background-color: #dcf8c6;
    }
    
    .received {
        align-self: flex-start;
        background-color: #f1f0f0;
    }
    
    .message-time {
        font-size: 0.7rem;
        color: #888;
        text-align: right;
        margin-top: 4px;
    }
</style>