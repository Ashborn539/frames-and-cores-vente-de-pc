<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/chat_config.php';
$isConnected = !empty($_SESSION['id_client']);
$lifetimeMinutes = getChatMessageLifetimeMinutes();
?>
<div id="chat-widget-container">
    <!-- Boite de dialogue -->
    <div id="chat-box" class="chat-box-hidden" aria-hidden="true">
        <div class="chat-header">
            <div class="chat-header-title">
                <h3>Discussion générale</h3>
                <span class="chat-duration-badge" id="chat-duration-badge"><?php echo $lifetimeMinutes; ?> min</span>
            </div>
            <button id="chat-close-btn" type="button" aria-label="Fermer le tchat">&times;</button>
        </div>

        <div id="chat-messages-container" class="chat-messages">
            <p class="chat-loading">Chargement des messages...</p>
        </div>

        <div id="chat-error-alert" class="chat-alert-hidden"></div>

        <div class="chat-footer">
            <?php if ($isConnected): ?>
                <form id="chat-form" autocomplete="off">
                    <input 
                        type="text" 
                        id="chat-input" 
                        name="message" 
                        placeholder="Écrivez un message..." 
                        maxlength="256" 
                        required
                    />
                    <button type="submit" id="chat-send-btn" aria-label="Envoyer">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </form>
            <?php else: ?>
                <p class="chat-login-notice">
                    <a href="connexion.php">Connectez-vous</a> pour participer au tchat.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bouton d'ouverture du tchat -->
    <button id="chat-toggle-btn" type="button" aria-label="Ouvrir le tchat">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
    </button>
</div>
