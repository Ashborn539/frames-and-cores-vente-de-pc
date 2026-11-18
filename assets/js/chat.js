$(function () {
    const $toggleBtn = $('#chat-toggle-btn');
    const $closeBtn = $('#chat-close-btn');
    const $chatBox = $('#chat-box');
    const $messagesContainer = $('#chat-messages-container');
    const $chatForm = $('#chat-form');
    const $chatInput = $('#chat-input');
    const $errorAlert = $('#chat-error-alert');
    const $durationBadge = $('#chat-duration-badge');

    let isChatOpen = false;
    let pollInterval = null;
    let lastMessagesJson = '';
    let isFirstLoad = true;

    // Bascule de l'affichage du tchat
    const toggleChat = () => {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            $chatBox.removeClass('chat-box-hidden').attr('aria-hidden', 'false');
            isFirstLoad = true;
            fetchMessages(true);
            if (!pollInterval) {
                pollInterval = setInterval(() => fetchMessages(false), 2500);
            }
            $chatInput.trigger('focus');
        } else {
            $chatBox.addClass('chat-box-hidden').attr('aria-hidden', 'true');
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
    };

    $toggleBtn.on('click', toggleChat);
    $closeBtn.on('click', toggleChat);

    // Empeche la molette du tchat de faire defiler la page
    $chatBox.on('wheel', function (e) {
        e.stopPropagation();
    });

    function fetchMessages(forceScroll = false) {
        $.getJSON('includes/php/chat_fetch.php')
            .done(function (data) {
                if (!data || !data.success) return;

                if (data.lifetime_minutes && $durationBadge.length) {
                    $durationBadge.text(data.lifetime_minutes + ' min');
                }

                const currentJson = JSON.stringify(data.messages);
                if (currentJson === lastMessagesJson && !forceScroll) {
                    return;
                }

                const el = $messagesContainer[0];
                const isNearBottom = el ? (el.scrollHeight - el.scrollTop - el.clientHeight <= 60) : true;
                lastMessagesJson = currentJson;

                if (!data.messages || data.messages.length === 0) {
                    const lifetime = data.lifetime_minutes || 10;
                    $messagesContainer.html('<p class="chat-empty">Aucun message récent (les messages expirent après ' + lifetime + ' min).</p>');
                    return;
                }

                $messagesContainer.empty();
                $.each(data.messages, function (_, msg) {
                    const $item = $('<div>')
                        .addClass('chat-msg-item' + (msg.is_me ? ' chat-msg-mine' : ''))
                        .text(msg.text);
                    $messagesContainer.append($item);
                });

                if (forceScroll || isFirstLoad || isNearBottom) {
                    if (el) $messagesContainer.scrollTop(el.scrollHeight);
                    isFirstLoad = false;
                }
            })
            .fail(function (xhr, status, err) {
                console.error('Erreur chat_fetch:', err);
            });
    }

    // Envoi asynchrone du formulaire
    $chatForm.on('submit', function (e) {
        e.preventDefault();
        const message = $.trim($chatInput.val());
        if (!message) return;

        $errorAlert.addClass('chat-alert-hidden').empty();

        $.ajax({
            url: 'includes/php/chat_send.php',
            method: 'POST',
            data: { message: message },
            dataType: 'json'
        })
        .done(function () {
            $chatInput.val('');
            fetchMessages(true);
        })
        .fail(function (xhr) {
            const res = xhr.responseJSON || {};
            const msg = res.message || "Erreur lors de l'envoi du message.";
            $errorAlert.text(msg).removeClass('chat-alert-hidden');
        });
    });
});
