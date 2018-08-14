# symfony4-user
skeleton for projects that require user registration and authentication with Symfony 4
checkout the branch "oauth" for google and facebook sign-in!

## Features

* User registration (with ReCaptcha) and authentication
* User edit
* Password reset (with ReCaptcha)
* Optional double opt-in
* Automatic login after user activation and password reset
* Bootstrap 4 theme

## Usage

Set environment variables in .env; you'll need a db, a mailer and recaptcha keys. Then run

	$ php bin/console doctrine:database:create
	$ php bin/console doctrine:migrations:migrate
