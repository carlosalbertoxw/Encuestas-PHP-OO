# Encuestas-PHP-OO

## What is?
Web application that allows you to create polls and make responses to polls

## Why was it done?
I made this project for curiosity and fun

## How it was made?
PHP OO with Bootstrap, JS and CSS

## How to run it?
- Before running the code please do the following:
- Make sure you have mod_rewrite enabled on the server.
- Make sure the directory permissions are 775 or (rwxrwxr-x) and the file permissions are 664 or (rw-rw-r).
- Go to File /.htaccess
  - Configure route of: ErrorDocument 404
  - Configure route of: ErrorDocument 403
- Go to File /application/config/constants.php
  - Set up: RELEASED
  - Set up: TRANSFER_PROTOCOL
  - Set up: WEB_PATH
  - Set up: WEB_SITE_NAME
- Create a database called: application
- Execute inside the database previously created the sql script that is in the file: Dump20190420.sql
- Go to File /application/db/connection.php
  - Configure database connection parameters in the open_connection method
- Run the application
