# Simple PHP libraries bundle to create simple API

This project includes the following libraries (and their dependencies) each with their license:
- https://github.com/firebase/php-jwt
- https://github.com/envms/fluentpdo
- https://github.com/juliomatcom/one-php-microframework
- https://github.com/vlucas/phpdotenv
- https://github.com/vlucas/valitron
- https://github.com/sendgrid/sendgrid-php
- https://github.com/Intervention/image
- https://github.com/katzgrau/KLogger

## Installation

run composer install

cp .env.example .env

edit the new .env file, variable names are pretty clear on what they represent

## Usage

Please check the documentation for each of the above libraries used

Inside existing index.php you can find examples of usage, authentication, validations, responses, querying the DB.
In this file you can also specify the time zone for manipulating date/time variables.
At need this can be moved as a setting in the .env file
