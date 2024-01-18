
# Obtain the application

In order to obtain the application, you have to clone the git repository using the command `git clone https://gitlab-ce.iut.u-bordeaux.fr/calmuller/s3.a.01-equipe2.git`

# Download the necessary dependencies

In order to be able to use the application, there are multiple needed dependencies. First of all you need to have PHP installed. If you don't follow the instructions here to download it manually. Otherwise you can also use these commands on these distros :

| Arch / Arch Based Distros | Ubuntu / Debian / Linux Mint / Elementary OS           |
| ------------------------- | ------------------------------------------------------ |
| yay php                   | sudo apt install php-common libapache2-mod-php php-cli |

You can check the installed version and whether it installed correctly by typing `php -v`.

You are also going to need **composer**, a package manager for PHP.
To check if it is already installed, and/or it's version you can use `composer -v`. If it isn't, install it by following the instructions [[https://getcomposer.org/download/|here]].
Along with composer you need to be able to use **npm**. To check if it is already installed, and/or it's version you can use `npm -v`. If it isn't, install it by following the instructions [[https://www.npmjs.com/get-npm|here]].

You are free to download it manually from the link provided above if you wish to do so.

It would probably wise to also [[https://symfony.com/download|download the Symfony CLI]] 

## Prepare the application for deployment

Now that you've cloned the application, go into it's folder and execute `setup.sh`. If you have properly downloaded the necessary dependencies, you will only need to edit the **.env** file and enter your correct credentials to connect to your database.

Once you're done, execute `prod.sh` to start the application !
