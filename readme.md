# Frames & cores

Frames & cores est un site e-commerce de matériel informatique réalisé en PHP dans un cadre pédagogique.
Il permet de consulter des articles, d'afficher leur détail, de gérer un panier et de se connecter à un compte utilisateur.

## Fonctionnalités

- Affichage du catalogue d'articles disponibles depuis une base de données MySQL
- Consultation de la fiche détaillée d'un produit
- Réservation temporaire d'articles et gestion du panier avec compte à rebours dynamique en temps réel
- Tchat général en direct sous forme de bulle flottante accessible sur les pages clés
- Modération automatique des messages par apprentissage automatique (TF-IDF et Perceptron)
- Rétention temporaire et purge automatique des messages expirés (durée paramétrable)
- Validation interactive des formulaires (retours visuels vert/rouge et contrôle de mot de passe complexe)
- Vérification asynchrone de l'unicité de l'e-mail en temps réel (AJAX)
- Inscription et connexion sans rechargement de page avec redirection temporisée (AJAX)
- Validation et finalisation des commandes avec historique d'achats du client
- Protection contre les failles CSRF et requêtes préparées sécurisées avec PDO
- Formulaire de contact

## Installation

1. Placer le projet dans le dossier `www` de WampServer (ex: `C:\wamp64\www\TyldenHounsa\frames-and-cores-vente-de-pc`).
2. Démarrer Apache et MySQL depuis WampServer.
3. Créer la base de données MySQL `computer_database` puis importer le fichier `database/computer_database.sql`.
4. Vérifier les identifiants de connexion dans `includes/php/bd.php`.
5. Ouvrir le projet dans le navigateur à l'adresse suivante : `http://localhost/TyldenHounsa/frames-and-cores-vente-de-pc/`.

## Organisation du projet

- `index.php` : catalogue d'accueil et présentation des produits
- `article.php` : fiche détaillée d'un article et ajout avec réservation
- `panier.php` : panier d'achats avec compte à rebours avant expiration
- `acheter.php` : validation transactionnelle de la commande
- `historique.php` : consultation de l'historique des commandes du client connecté
- `compte.php` : espace client sécurisé
- `connexion.php` : formulaire de connexion (interactif et asynchrone via AJAX)
- `nouveau.php` : création de compte avec validation dynamique des champs et AJAX
- `contact.html` : formulaire de contact
- `ARCHITECTURE.md` : documentation technique et diagrammes d'architecture
- `database/` : script SQL de création et d'initialisation de la base de données (dont `chat_messages`)
- `assets/css/` : feuille de style globale, thème sombre et doré du tchat
- `assets/js/` : scripts JavaScript et jQuery (gestion AJAX du tchat, formulaires, compte à rebours)
- `includes/php/` : logique serveur (connexion PDO, CSRF, gestion du stock, endpoints AJAX et modération du tchat)
- `includes/data/` : dictionnaire des poids du modèle de modération (`model_weights.json`)
- `includes/filter/` : scripts d'entraînement Python du modèle Perceptron

## Crédits et Droits d'auteur

Ce projet a été réalisé dans un cadre strictement pédagogique et n'a aucun but commercial.

Les images de produits utilisées pour illustrer le site proviennent du site de [La Fnac](https://fnac.com).

Elles restent la propriété exclusive de leurs auteurs respectifs.
