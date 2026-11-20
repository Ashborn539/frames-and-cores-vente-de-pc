$(function () {
    var $form = $('#form-inscription');
    var $nom = $('#nom');
    var $prenom = $('#prenom');
    var $adresse = $('#adresse');
    var $num = $('#num');
    var $mail = $('#mail');
    var $mdp1 = $('#mdp1');
    var $mdp2 = $('#mdp2');
    var $btnSubmit = $('#btn-submit');
    var $feedback = $('#form-feedback');

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

    function marquerValide($input, key) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $input.next('.field-msg').removeClass('error').empty();
        validity[key] = true;
        actualiserBouton();
        return true;
    }

    function marquerInvalide($input, key, message) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $input.next('.field-msg').addClass('error').text(message);
        validity[key] = false;
        actualiserBouton();
        return false;
    }

    function actualiserBouton() {
        var tousValides = validity.nom &&
                          validity.prenom &&
                          validity.adresse &&
                          validity.num &&
                          validity.mail &&
                          validity.mdp1 &&
                          validity.mdp2;

        $btnSubmit.prop('disabled', !tousValides);
    }

    function validerNom() {
        var val = $nom.val().trim();
        return (val === '') ? marquerInvalide($nom, 'nom', 'Le nom ne peut pas être vide.') : marquerValide($nom, 'nom');
    }

    function validerPrenom() {
        var val = $prenom.val().trim();
        return (val === '') ? marquerInvalide($prenom, 'prenom', 'Le prénom ne peut pas être vide.') : marquerValide($prenom, 'prenom');
    }

    function validerAdresse() {
        var val = $adresse.val().trim();
        return (val === '') ? marquerInvalide($adresse, 'adresse', 'L’adresse ne peut pas être vide.') : marquerValide($adresse, 'adresse');
    }

    function validerNum() {
        var val = $num.val().trim();
        return (val === '') ? marquerInvalide($num, 'num', 'Le numéro de téléphone ne peut pas être vide.') : marquerValide($num, 'num');
    }

    function validerEmail(immediat) {
        var val = $mail.val().trim();
        clearTimeout(emailTimer);
        if (emailXhr) emailXhr.abort();

        if (val === '') return marquerInvalide($mail, 'mail', 'L’adresse e-mail ne peut pas être vide.');
        if (!emailRegex.test(val)) return marquerInvalide($mail, 'mail', 'Format d’adresse e-mail invalide.');

        var appelAjax = function () {
            emailXhr = $.getJSON('./includes/php/verifier_email.php', { mail: val })
                .done(function (res) {
                    if (res && res.existe) {
                        marquerInvalide($mail, 'mail', 'Cette adresse email existe déjà.');
                    } else {
                        marquerValide($mail, 'mail');
                    }
                })
                .fail(function (_, status) {
                    if (status !== 'abort') {
                        marquerInvalide($mail, 'mail', 'Erreur lors de la vérification de l’email.');
                    }
                });
        };

        if (immediat) {
            appelAjax();
        } else {
            emailTimer = setTimeout(appelAjax, 300);
        }
    }

    function validerMdp1() {
        var val = $mdp1.val();
        var formatRegex = /^(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).+$/;

        if (val === '') return marquerInvalide($mdp1, 'mdp1', 'Le mot de passe ne peut pas être vide.');
        if (!formatRegex.test(val)) return marquerInvalide($mdp1, 'mdp1', 'Il faut au moins 1 lettre, 1 chiffre et 1 caractère spécial.');

        marquerValide($mdp1, 'mdp1');
        if ($mdp2.val() !== '') {
            validerMdp2();
        }
        return true;
    }

    function validerMdp2() {
        var val1 = $mdp1.val();
        var val2 = $mdp2.val();

        if (val2 === '') return marquerInvalide($mdp2, 'mdp2', 'Veuillez confirmer votre mot de passe.');
        if (val2 !== val1) return marquerInvalide($mdp2, 'mdp2', 'Les mots de passe ne correspondent pas.');

        return marquerValide($mdp2, 'mdp2');
    }

    $nom.on('input blur change', validerNom);
    $prenom.on('input blur change', validerPrenom);
    $adresse.on('input blur change', validerAdresse);
    $num.on('input blur change', validerNum);

    $mail.on('input change', function () { validerEmail(false); });
    $mail.on('blur', function () { validerEmail(true); });

    $mdp1.on('input blur change', validerMdp1);
    $mdp2.on('input blur change', validerMdp2);

    $form.on('submit', function (e) {
        e.preventDefault();

        validerNom();
        validerPrenom();
        validerAdresse();
        validerNum();
        validerMdp1();
        validerMdp2();
        validerEmail(true);

        var tousValides = validity.nom &&
                          validity.prenom &&
                          validity.adresse &&
                          validity.num &&
                          validity.mail &&
                          validity.mdp1 &&
                          validity.mdp2;

        if (!tousValides) {
            $feedback.removeClass('form-success').addClass('form-error').text('Veuillez corriger les champs invalides avant de soumettre.').show();
            return false;
        }

        $feedback.hide();
        $btnSubmit.prop('disabled', true).text('Création en cours...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize() + '&ajax=1',
            dataType: 'json',
            success: function (res) {
                if (res && (res.success || res.succes)) {
                    $feedback.removeClass('form-error').addClass('form-success').text(res.message || 'Votre compte a été créé avec succès !').show();
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    $feedback.removeClass('form-success').addClass('form-error').text((res && res.message) ? res.message : 'Erreur lors de la création du compte.').show();
                    $btnSubmit.prop('disabled', false).text('Confirmer');
                }
            },
            error: function (xhr) {
                var msg = 'Une erreur est survenue lors de la communication avec le serveur.';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.message) msg = res.message;
                } catch (_) {}
                $feedback.removeClass('form-success').addClass('form-error').text(msg).show();
                $btnSubmit.prop('disabled', false).text('Confirmer');
            }
        });
    });
});
