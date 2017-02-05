# Simple PHP libraries bundle to create an API

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

run ``composer install``

``cp .env.example .env``

edit the new ``.env`` file, variable names are pretty clear on what they represent

## Usage

Please check the documentation for each of the above libraries used.

Inside existing ``index.php`` you can find examples of usage, authentication, validations, responses, querying the DB.
In this file you can also specify the time zone for manipulating date/time variables.
At need this can be moved as a setting in the ``.env`` file.

## VERY IMPORTANT

**This is just a simple bundle to create an API using other libraries that I found being maintained and reliable at the date of this push.**

**I think is better and I always go in new/fresh projects on using modern frameworks, but at the time of trying this, it was more useful.**

Reasons for taking this decision: creating the API using a modern framework (I thought of [Lumen](https://lumne.laravel.com/docs)  and [Laravel](https://laravel.com/docs) ) would require a lot of model changes, workarounds to make it work easier and maintainable in Laravel style, and for this I consider the fact that the API I had to write was based on an old web application written years ago in plain PHP with no resources (financial and time implied here) to upgrade its code base in such a way that would make the web app and API use a modern framework.
