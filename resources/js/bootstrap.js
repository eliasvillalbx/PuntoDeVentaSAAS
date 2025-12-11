import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// conversationId = ID numérico de la conversación actual
function listenConversation(conversationId, onNewMessage) {
    if (!window.Echo) return;

    window.Echo
        .private(`conversations.${conversationId}`)
        .listen('.MessageSent', (event) => {
            // event contiene lo que devolvimos en broadcastWith()
            // Llamas tu callback para pintar el mensaje en la UI
            if (typeof onNewMessage === 'function') {
                onNewMessage(event);
            }
        });
}
