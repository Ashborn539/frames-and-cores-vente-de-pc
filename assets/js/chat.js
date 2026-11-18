document.addEventListener('DOMContentLoaded', () => {
    const boutonBasculer = document.getElementById('chat-toggle-btn');
    const boutonFermer = document.getElementById('chat-close-btn');
    const boiteChat = document.getElementById('chat-box');
    const conteneurMessages = document.getElementById('chat-messages-container');
    const formulaireChat = document.getElementById('chat-form');
    const champSaisieChat = document.getElementById('chat-input');
    const alerteErreur = document.getElementById('chat-error-alert');

    let estChatOuvert = false;
    let intervalleActualisation = null;
    let dernierJsonMessages = '';
    let estPremierChargement = true;

    // Ouverture et fermeture du tchat
    const basculerChat = () => {
        estChatOuvert = !estChatOuvert;
        if (estChatOuvert) {
            boiteChat.classList.remove('chat-box-hidden');
            boiteChat.setAttribute('aria-hidden', 'false');
            estPremierChargement = true;
            recupererMessages(true);
            if (!intervalleActualisation) {
                intervalleActualisation = setInterval(() => recupererMessages(false), 2500);
            }
            if (champSaisieChat) champSaisieChat.focus();
        } else {
            boiteChat.classList.add('chat-box-hidden');
            boiteChat.setAttribute('aria-hidden', 'true');
            if (intervalleActualisation) {
                clearInterval(intervalleActualisation);
                intervalleActualisation = null;
            }
        }
    };

    if (boutonBasculer) boutonBasculer.addEventListener('click', basculerChat);
    if (boutonFermer) boutonFermer.addEventListener('click', basculerChat);

    // Isole le defilement pour eviter de faire bouger la page
    if (boiteChat) {
        boiteChat.addEventListener('wheel', (evenement) => {
            evenement.stopPropagation();
        }, { passive: true });
    }

    function recupererMessages(forcerDefilement = false) {
        fetch('includes/php/chat_fetch.php')
            .then(reponse => reponse.json())
            .then(donnees => {
                if (!donnees.success) return;

                if (donnees.lifetime_minutes) {
                    const badgeDuree = document.getElementById('chat-duration-badge');
                    if (badgeDuree) {
                        badgeDuree.textContent = `${donnees.lifetime_minutes} min`;
                    }
                }

                // Evite de reconstruire le DOM si les messages n'ont pas change
                const jsonActuel = JSON.stringify(donnees.messages);
                if (jsonActuel === dernierJsonMessages && !forcerDefilement) {
                    return;
                }

                // On verifie si l'utilisateur etait en train de lire plus haut
                const estProcheDuBas = conteneurMessages.scrollHeight - conteneurMessages.scrollTop - conteneurMessages.clientHeight <= 60;
                dernierJsonMessages = jsonActuel;

                if (donnees.messages.length === 0) {
                    const dureeDeVie = donnees.lifetime_minutes || 10;
                    conteneurMessages.innerHTML = `<p class="chat-empty">Aucun message récent (les messages expirent après ${dureeDeVie} min).</p>`;
                    return;
                }

                conteneurMessages.innerHTML = '';
                donnees.messages.forEach(message => {
                    const elementMessage = document.createElement('div');
                    elementMessage.className = 'chat-msg-item' + (message.is_me ? ' chat-msg-mine' : '');
                    elementMessage.textContent = message.text;
                    conteneurMessages.appendChild(elementMessage);
                });

                // On descend auto uniquement si on est deja en bas ou a l'envoi
                if (forcerDefilement || estPremierChargement || estProcheDuBas) {
                    conteneurMessages.scrollTop = conteneurMessages.scrollHeight;
                    estPremierChargement = false;
                }
            })
            .catch(erreur => console.error('Erreur chat_fetch:', erreur));
    }

    // Envoi du message
    if (formulaireChat) {
        formulaireChat.addEventListener('submit', (evenement) => {
            evenement.preventDefault();
            const message = champSaisieChat.value.trim();
            if (!message) return;

            alerteErreur.classList.add('chat-alert-hidden');
            alerteErreur.textContent = '';

            const donneesFormulaire = new URLSearchParams();
            donneesFormulaire.append('message', message);

            fetch('includes/php/chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: donneesFormulaire.toString()
            })
                .then(async (reponse) => {
                    const donnees = await reponse.json();
                    if (!reponse.ok || !donnees.success) {
                        throw donnees;
                    }
                    return donnees;
                })
                .then(() => {
                    champSaisieChat.value = '';
                    recupererMessages(true);
                })
                .catch(erreur => {
                    alerteErreur.textContent = erreur.message || "Erreur lors de l'envoi du message.";
                    alerteErreur.classList.remove('chat-alert-hidden');
                });
        });
    }
});
