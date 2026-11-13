$(function () {
    var $form = $('#form-connexion');
    var $mail = $('#mail, [name="mail"]').first();
    var $mdp = $('#mdp, [name="mdp"]').first();
    var $btnSubmit = $('#btn-connexion, input[type="submit"], button[type="submit"]').last();
    var $feedback = $('#form-feedback');

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

    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    function marquerValide($input) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $input.css({
            'background-color': '#ccffcc',
            'border': '2px solid #28a745',
            'outline': 'none'
        });
        var $msg = getMsg($input);
        $msg.removeClass('error').empty().hide();
        return true;
    }

    function marquerInvalide($input, message) {
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
        return false;
    }

    function validerEmail() {
        var val = $mail.val().trim();
        if (val === '') {
            return marquerInvalide($mail, 'L’adresse e-mail est obligatoire.');
        }
        if (!emailRegex.test(val)) {
            return marquerInvalide($mail, 'Format d’adresse e-mail invalide.');
        }
        return marquerValide($mail);
    }

    function validerMdp() {
        var val = $mdp.val();
        if (val === '') {
            return marquerInvalide($mdp, 'Le mot de passe est obligatoire.');
        }
        return marquerValide($mdp);
    }

    $mail.on('input blur change', validerEmail);
    $mdp.on('input blur change', validerMdp);

    /**
     * Connexion utilisateur via AJAX
     */
    $form.on('submit', function (e) {
        e.preventDefault();

        var isEmailOk = validerEmail();
        var isMdpOk = validerMdp();

        if (!isEmailOk || !isMdpOk) {
            $feedback
                .removeClass('form-success')
                .addClass('form-error')
                .text('Veuillez remplir correctement les identifiants.')
                .show();
            return;
        }

        $feedback.hide();
        $btnSubmit.prop('disabled', true).text('Connexion en cours...');

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
                        .text(response.message || 'Connexion réussie !')
                        .show();

                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Adresse e-mail ou mot de passe incorrect.';
                    $feedback
                        .removeClass('form-success')
                        .addClass('form-error')
                        .text(errorMsg)
                        .show();
                    $btnSubmit.prop('disabled', false).text('Se connecter');
                }
            },
            error: function (xhr) {
                var errorMsg = 'Une erreur est survenue lors de la connexion.';
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
                $btnSubmit.prop('disabled', false).text('Se connecter');
            }
        });
    });
});
