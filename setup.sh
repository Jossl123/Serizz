#!/bin/bash

cd app/ || { echo "Repository app does not exist. Are you in the correct folder ?"; exit 1; }

if [ -f .env ]
then 

    echo "Already existing environment file"; 

else

    touch .env;

    echo "APP_ENV=dev" > .env;
    echo "APP_SECRET=30b8d1c83771d3c9d6f32e77af433faf" > .env;
    echo "# DATABASE_URL='sqlite:///%kernel.project_dir%/var/data.db'" > .env;
    echo "# DATABASE_URL='mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4'" > .env;
    echo "# DATABASE_URL='mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=10.11.2-MariaDB&charset=utf8mb4'" > .env;
    echo "# DATABASE_URL='mysql://username:password@host/database_name'" > .env;
    echo "MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0" > .env;

fi

if [ -f .env ] 
then
    echo ".env created properly";
else 
    echo "An error occurred when creating the .env file";
fi

echo "WARNING : Edit the .env file and add your database connection before proceeding."

while IFS= read -n1 -r -p "Is the file edited with correct credentials ? [y]es|[n]o " && [[ $REPLY != y ]]; do
  case $REPLY in
    n) echo "Waiting for user edit...";;
    *) echo "Answer with [y] or [n]";;
  esac
done

composer install
npm install

read -p "Do you want to start the prod now ? y/N " answer

if [ $answer == 'y' ]
then
    if [ -f prod.sh ]
    then
        /bin/bash prod.sh;
    else
        echo "File prod.sh missing. Check the repository ?";
    fi
fi
