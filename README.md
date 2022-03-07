# Bachelor's thesis - Petr Kruntorád

This repository contains a bachelor's thesis focused on remote measuring of temperatures with the usage of Raspberry Pi. Web administration is using template AdminLTE with Symfony framework used for backend.

Used technologies:
- Symfony 5.3.15
- Python 3.9
- Raspberry Pi 3B+
- Tester Sensors 
  - DS18B20
Project init
Installs dependencies
```
composer install
```
Creates database connection
```
php bin/console d:d:c
```
Updates database schema
```
php bin/console d:s:u --force
```
