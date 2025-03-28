<script>
    export let newMessage = '';
    export let sendMessage;
    export let handleTyping;
</script>

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

<style>
    .message-input {
        padding: 15px;
        border-top: 1px solid #ddd;
        display: flex;
        background-color: #f9f9f9;
    }
    
    input {
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
</style>