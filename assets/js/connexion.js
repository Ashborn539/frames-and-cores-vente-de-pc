$(function () {
    var $form = $('#form-connexion');
    var $mail = $('#mail');
    var $mdp = $('#mdp');
    var $btnSubmit = $('#btn-connexion');
    var $feedback = $('#form-feedback');

    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    function marquerValide($input) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $input.next('.field-msg').removeClass('error').empty();
        return true;
    }

    function marquerInvalide($input, message) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $input.next('.field-msg').addClass('error').text(message);
        return false;
    }

    function validerEmail() {
        var val = $mail.val().trim();
        if (val === '') return marquerInvalide($mail, 'L’adresse e-mail est obligatoire.');
        if (!emailRegex.test(val)) return marquerInvalide($mail, 'Format d’adresse e-mail invalide.');
        return marquerValide($mail);
    }

    function validerMdp() {
        var val = $mdp.val();
        if (val === '') return marquerInvalide($mdp, 'Le mot de passe est obligatoire.');
        return marquerValide($mdp);
    }

    $mail.on('input blur change', validerEmail);
    $mdp.on('input blur change', validerMdp);

    $form.on('submit', function (e) {
        e.preventDefault();

        if (!validerEmail() || !validerMdp()) {
            $feedback.removeClass('form-success').addClass('form-error').text('Veuillez remplir correctement les identifiants.').show();
            return;
        }

        $feedback.hide();
        $btnSubmit.prop('disabled', true).text('Connexion en cours...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize() + '&ajax=1',
            dataType: 'json',
            success: function (res) {
                if (res && (res.success || res.succes)) {
                    $feedback.removeClass('form-error').addClass('form-success').text(res.message || 'Connexion réussie !').show();
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    $feedback.removeClass('form-success').addClass('form-error').text((res && res.message) ? res.message : 'Identifiants incorrects.').show();
                    $btnSubmit.prop('disabled', false).text('Se connecter');
                }
            },
            error: function (xhr) {
                var msg = 'Une erreur est survenue lors de la connexion.';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.message) msg = res.message;
                } catch (_) {}
                $feedback.removeClass('form-success').addClass('form-error').text(msg).show();
                $btnSubmit.prop('disabled', false).text('Se connecter');
            }
        });
    });
});
