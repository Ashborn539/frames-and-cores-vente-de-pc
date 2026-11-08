$('.reservation-countdown').each(function () {
    var $compteur = $(this);

    var dateTexte = $compteur.data('expires-at');
    var dateFin = new Date(dateTexte.replace(' ', 'T') + 'Z').getTime();

    var stockVide = ($compteur.data('stock-empty') === true);

    function actualiserChrono() {
        var maintenant = new Date().getTime();
        var tempsRestant = dateFin - maintenant;

        if (tempsRestant <= 0) {
            $compteur.text("Réservation expirée.");
            clearInterval(monChrono); 
            location.reload();     
            return;
        }

        var totalSecondes = Math.floor(tempsRestant / 1000);
        var minutes = Math.floor(totalSecondes / 60);
        var secondes = totalSecondes % 60;

        if (secondes < 10) {
            secondes = "0" + secondes;
        }

        var message = "Réservation valable encore : ";
        if (stockVide) {
            message = "Derniers exemplaires : ";
        }

        $compteur.text(message + minutes + ":" + secondes);
    }

    actualiserChrono();

    var monChrono = setInterval(actualiserChrono, 1000);
});



