$(function () {
    var $form = $('#form-inscription');
    var $nom = $('#nom, #n, [name="n"]').first();
    var $prenom = $('#prenom, #p, [name="p"]').first();
    var $adresse = $('#adresse, #adr, [name="adr"]').first();
    var $num = $('#num, [name="num"]').first();
    var $mail = $('#mail, [name="mail"]').first();
    var $mdp1 = $('#mdp1, [name="mdp1"]').first();
    var $mdp2 = $('#mdp2, [name="mdp2"]').first();
    var $btnSubmit = $('#btn-submit, input[type="submit"], button[type="submit"]').last();
    var $feedback = $('#form-feedback');

    // Récupère ou crée dynamiquement le conteneur du message d'erreur sous le champ
    function getMsg($input) {
        var $msg = $input.next('.field-msg, .erreur');
        if ($msg.length === 0) {
            var id = $input.attr('id') || $input.attr('name');
            $msg = $('#msg-' + id);
        }
        if ($msg.length === 0) {
            $msg = $('<span class="field-msg erreur"></span>');
            $input.after($msg);
        }
        return $msg;
    }

    var validity = {
        nom: false,
        prenom: false,
        adresse: false,
        num: false,
        mail: false,
        mdp1: false,
        mdp2: false
    };

    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    var emailTimer = null;
    var emailXhr = null;

    /**
     * Valide le champ et applique le style vert
     */
    function marquerValide($input, key) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $input.css({
            'background-color': '#ccffcc',
            'border': '2px solid #28a745',
            'outline': 'none'
        });

        var $msg = getMsg($input);
        $msg.removeClass('error').empty().hide();

        validity[key] = true;
        actualiserBouton();
        return true;
    }

    /**
     * Invalide le champ et affiche l'erreur
     */
    function marquerInvalide($input, key, message) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $input.css({
            'background-color': '#ffcccc',
            'border': '2px solid #dc3545',
            'outline': 'none'
        });

        var $msg = getMsg($input);
        $msg.removeClass('success').addClass('error').text(message).css({
            'color': '#dc3545',
            'display': 'block',
            'font-size': '0.85rem',
            'margin-top': '4px',
            'font-weight': '600'
        }).show();

        validity[key] = false;
        actualiserBouton();
        return false;
    }

    /**
     * Met à jour l'état du bouton de confirmation.
     * Tant qu'un champ n'est pas valide, le formulaire ne peut pas être soumis.
     */
    function actualiserBouton() {
        var tousValides = validity.nom &&
                          validity.prenom &&
                          validity.adresse &&
                          validity.num &&
                          validity.mail &&
                          validity.mdp1 &&
                          validity.mdp2;

        $btnSubmit.prop('disabled', !tousValides);
        if (tousValides) {
            $btnSubmit.css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        } else {
            $btnSubmit.css({
                'opacity': '0.6',
                'cursor': 'not-allowed'
            });
        }
    }

    function validerNom() {
        var val = $nom.val().trim();
        if (val === '') {
            return marquerInvalide($nom, 'nom', 'Le nom ne peut pas être vide.');
        }
        return marquerValide($nom, 'nom');
    }

    function validerPrenom() {
        var val = $prenom.val().trim();
        if (val === '') {
            return marquerInvalide($prenom, 'prenom', 'Le prénom ne peut pas être vide.');
        }
        return marquerValide($prenom, 'prenom');
    }

    function validerAdresse() {
        var val = $adresse.val().trim();
        if (val === '') {
            return marquerInvalide($adresse, 'adresse', 'L’adresse ne peut pas être vide.');
        }
        return marquerValide($adresse, 'adresse');
    }

    function validerNum() {
        var val = $num.val().trim();
        if (val === '') {
            return marquerInvalide($num, 'num', 'Le numéro de téléphone ne peut pas être vide.');
        }
        return marquerValide($num, 'num');
    }

    /**
     * Vérification du format et de l'unicité de l'e-mail
     */
    function validerEmail(immediat) {
        var val = $mail.val().trim();

        if (emailTimer) {
            clearTimeout(emailTimer);
            emailTimer = null;
        }

        if (emailXhr) {
            emailXhr.abort();
            emailXhr = null;
        }

        if (val === '') {
            return marquerInvalide($mail, 'mail', 'L’adresse e-mail ne peut pas être vide.');
        }

        if (!emailRegex.test(val)) {
            return marquerInvalide($mail, 'mail', 'Format d’adresse e-mail invalide.');
        }

        function appelAjaxEmail() {
            emailXhr = $.ajax({
                url: './includes/php/verifier_email.php',
                type: 'GET',
                data: { mail: val },
                dataType: 'json',
                success: function (response) {
                    var existe = false;
                    if (typeof response === 'boolean') {
                        existe = response;
                    } else if (typeof response === 'string') {
                        existe = (response.trim().toLowerCase() === 'true' || response.trim() === '1');
                    } else if (response && typeof response === 'object') {
                        existe = Boolean(response.existe !== undefined ? response.existe : (response.exists !== undefined ? response.exists : response.result));
                    }

                    if (existe) {
                        marquerInvalide($mail, 'mail', 'Cette adresse email existe déjà.');
                    } else {
                        marquerValide($mail, 'mail');
                    }
                },
                error: function (xhr, status) {
                    if (status !== 'abort') {
                        marquerInvalide($mail, 'mail', 'Erreur lors de la vérification de l’email.');
                    }
                }
            });
        }

        if (immediat) {
            appelAjaxEmail();
        } else {
            emailTimer = setTimeout(appelAjaxEmail, 300);
        }
    }

    /**
     * Vérification du mot de passe (lettre, chiffre et caractère spécial)
     */
    function validerMdp1() {
        var val = $mdp1.val();
        var formatRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).+$/;

        if (val === '') {
            return marquerInvalide($mdp1, 'mdp1', 'Le mot de passe ne peut pas être vide.');
        }

        if (!formatRegex.test(val)) {
            return marquerInvalide($mdp1, 'mdp1', 'Il faut au moins 1 lettre, 1 chiffre et 1 caractère spécial.');
        }

        marquerValide($mdp1, 'mdp1');

        if ($mdp2.val() !== '') {
            validerMdp2();
        }
        return true;
    }

    /**
     * Confirmation identique au premier mot de passe
     */
    function validerMdp2() {
        var val1 = $mdp1.val();
        var val2 = $mdp2.val();

        if (val2 === '') {
            return marquerInvalide($mdp2, 'mdp2', 'Veuillez confirmer votre mot de passe.');
        }

        if (val2 !== val1) {
            return marquerInvalide($mdp2, 'mdp2', 'Les mots de passe ne correspondent pas.');
        }

        return marquerValide($mdp2, 'mdp2');
    }

    // Déclencheurs après chaque complétion d'un champ
    $nom.on('input blur change', validerNom);
    $prenom.on('input blur change', validerPrenom);
    $adresse.on('input blur change', validerAdresse);
    $num.on('input blur change', validerNum);

    $mail.on('input change', function () {
        validerEmail(false);
    });
    $mail.on('blur', function () {
        validerEmail(true);
    });

    $mdp1.on('input blur change', validerMdp1);
    $mdp2.on('input blur change', validerMdp2);

    /**
     * Soumission du formulaire sans rechargement de page
     */
    $form.on('submit', function (e) {
        e.preventDefault();

        // Validation forcée de tous les champs
        var vNom = validerNom();
        var vPrenom = validerPrenom();
        var vAdresse = validerAdresse();
        var vNum = validerNum();
        var vMdp1 = validerMdp1();
        var vMdp2 = validerMdp2();

        validerEmail(true);

        var tousValides = validity.nom &&
                          validity.prenom &&
                          validity.adresse &&
                          validity.num &&
                          validity.mail &&
                          validity.mdp1 &&
                          validity.mdp2;

        if (!tousValides) {
            $feedback
                .removeClass('form-success')
                .addClass('form-error')
                .text('Veuillez corriger les champs invalides avant de soumettre.')
                .show();
            return false;
        }

        $feedback.hide();
        $btnSubmit.prop('disabled', true).text('Création en cours...');

        var formData = $form.serialize() + '&ajax=1';

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response && (response.success || response.succes)) {
                    $feedback
                        .removeClass('form-error')
                        .addClass('form-success')
                        .text(response.message || 'Votre compte a été créé avec succès !')
                        .show();

                    // Redirection vers l'accueil 1 seconde après
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Erreur lors de la création du compte.';
                    $feedback
                        .removeClass('form-success')
                        .addClass('form-error')
                        .text(errorMsg)
                        .show();
                    $btnSubmit.prop('disabled', false).text('Confirmer');
                }
            },
            error: function (xhr) {
                var errorMsg = 'Une erreur est survenue lors de la communication avec le serveur.';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.message) {
                        errorMsg = res.message;
                    }
                } catch (err) {}

                $feedback
                    .removeClass('form-success')
                    .addClass('form-error')
                    .text(errorMsg)
                    .show();
                $btnSubmit.prop('disabled', false).text('Confirmer');
            }
        });
    });
});
