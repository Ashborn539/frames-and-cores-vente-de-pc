document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chat-toggle-btn');
    const closeBtn = document.getElementById('chat-close-btn');
    const chatBox = document.getElementById('chat-box');
    const messagesContainer = document.getElementById('chat-messages-container');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const errorAlert = document.getElementById('chat-error-alert');

    let isChatOpen = false;
    let pollInterval = null;
    let lastMessagesJson = '';
    let isFirstLoad = true;

    // Ouverture et fermeture du tchat
    const toggleChat = () => {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            chatBox.classList.remove('chat-box-hidden');
            chatBox.setAttribute('aria-hidden', 'false');
            isFirstLoad = true;
            fetchMessages(true);
            if (!pollInterval) {
                pollInterval = setInterval(() => fetchMessages(false), 2500);
            }
            if (chatInput) chatInput.focus();
        } else {
            chatBox.classList.add('chat-box-hidden');
            chatBox.setAttribute('aria-hidden', 'true');
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
    };

    if (toggleBtn) toggleBtn.addEventListener('click', toggleChat);
    if (closeBtn) closeBtn.addEventListener('click', toggleChat);

    // Isole le defilement pour eviter de faire bouger la page
    if (chatBox) {
        chatBox.addEventListener('wheel', (e) => {
            e.stopPropagation();
        }, { passive: true });
    }

    function fetchMessages(forceScroll = false) {
        fetch('includes/php/chat_fetch.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (data.lifetime_minutes) {
                    const durationBadge = document.getElementById('chat-duration-badge');
                    if (durationBadge) {
                        durationBadge.textContent = `${data.lifetime_minutes} min`;
                    }
                }

                // Evite de reconstruire le DOM si les messages n'ont pas change
                const currentJson = JSON.stringify(data.messages);
                if (currentJson === lastMessagesJson && !forceScroll) {
                    return;
                }

                // On verifie si l'utilisateur etait en train de lire plus haut
                const isNearBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight <= 60;
                lastMessagesJson = currentJson;

                if (data.messages.length === 0) {
                    const lifetime = data.lifetime_minutes || 10;
                    messagesContainer.innerHTML = `<p class="chat-empty">Aucun message récent (les messages expirent après ${lifetime} min).</p>`;
                    return;
                }

                messagesContainer.innerHTML = '';
                data.messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'chat-msg-item' + (msg.is_me ? ' chat-msg-mine' : '');
                    div.textContent = msg.text;
                    messagesContainer.appendChild(div);
                });

                // On descend auto uniquement si on est deja en bas ou a l'envoi
                if (forceScroll || isFirstLoad || isNearBottom) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    isFirstLoad = false;
                }
            })
            .catch(err => console.error('Erreur chat_fetch:', err));
    }

    // Envoi du message
    if (chatForm) {
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (!message) return;

            errorAlert.classList.add('chat-alert-hidden');
            errorAlert.textContent = '';

            const formData = new URLSearchParams();
            formData.append('message', message);

            fetch('includes/php/chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw data;
                    }
                    return data;
                })
                .then(() => {
                    chatInput.value = '';
                    fetchMessages(true);
                })
                .catch(err => {
                    errorAlert.textContent = err.message || "Erreur lors de l'envoi du message.";
                    errorAlert.classList.remove('chat-alert-hidden');
                });
        });
    }
});
