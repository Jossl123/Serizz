
# Obtenir l'application

Afin de obtenir l'application vous devez cloner le dépôt git à l'aide de la commande `git clone https://gitlab-ce.iut.u-bordeaux.fr/calmuller/s3.a.01-equipe2.git`.

# Installer les dépendances du site

Pour pouvoir installer l'application plusieurs dépendances sont requises : tout d'abord vous devez avoir PHP d'installé. Si ce n'est pas le cas et que vous êtes sous Linux, tapez les commandes :

| Arch / Arch Based Distros | Ubuntu / Debian / Linux Mint / Elementary OS           |
| ------------------------- | ------------------------------------------------------ |
| yay php                   | sudo apt install php-common libapache2-mod-php php-cli |

Vous pouvez également vérifier l'installation et sa version à l'aide de `php -v`.

Vous allez également avoir besoin de **composer** un gestionnaire de paquets PHP.
Pour vérifier si c'est déjà installé et/ou sa version vous pouvez utiliser `composer -v`. Sinon, suivez les instructions [[https://getcomposer.org/download/|ici]] pour l'installer. 
Vous aurez également besoin de **npm**. Pour vérifier si c'est déjà installé et/ou sa version, vous pouvez utiliser `npm -v`. Sinon, suivez les instructions [[https://www.npmjs.com/get-npm|ici]].

Ce serait aussi utile [[https://symfony.com/download|d'installer le CLI de Symfony]].

## Préparer l'application pour déploiement

Maintenant que vous avez cloné l'application rendez-vous dans son dossier puis exécuter le script `setup.sh`. Si vous avez téléchargé toutes les dépendances requises, il ne vous manquera plus qu'à éditer le fichier .env pour vous connecter à votre base de données.

Une fois que vous avez terminé d'éditer le .env vous pouvez lancer le script `prod.sh` pour lancer l'application !
