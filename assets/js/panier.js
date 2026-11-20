$('.reservation-countdown').each(function () {
    var $compteur = $(this);
    var dateTexte = $compteur.data('expires-at');
    var dateFin = new Date(dateTexte.replace(' ', 'T') + 'Z').getTime();
    var stockVide = ($compteur.data('stock-empty') === true);

    function actualiserChrono() {
        var tempsRestant = dateFin - Date.now();

        if (tempsRestant <= 0) {
            $compteur.text("Réservation expirée.");
            clearInterval(monChrono);
            location.reload();
            return;
        }

        var totalSecondes = Math.floor(tempsRestant / 1000);
        var minutes = Math.floor(totalSecondes / 60);
        var secondes = totalSecondes % 60;
        var prefix = stockVide ? "Derniers exemplaires : " : "Réservation valable encore : ";

        $compteur.text(prefix + minutes + ":" + (secondes < 10 ? "0" : "") + secondes);
    }

    actualiserChrono();
    var monChrono = setInterval(actualiserChrono, 1000);
});
