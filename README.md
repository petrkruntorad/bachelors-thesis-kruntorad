[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
# Bachelor's thesis - Petr Kruntorád

This repository contains a bachelor's thesis focused on remote measuring of temperatures with the usage of Raspberry Pi. Web administration is using template AdminLTE with Symfony framework used for backend.

Used technologies:
- Symfony 5.3.15
- Python 3.9
- PHP 7.4
- Raspberry Pi 3B+
- Tester Sensors 
  - DS18B20

Used Template:
- [AdminLTE](https://github.com/ColorlibHQ/AdminLTE)

## Project init
Installs dependencies
```
composer install
```
Creates database connection (optional)
```
php bin/console d:d:c
```
Updates database schema (optional)
```
php bin/console d:s:u --force
```
### Or
Import temperature-measurement.sql

## License
[MIT](https://opensource.org/licenses/MIT)

## Author
Petr Kruntorád

[LinkedIn](www.linkedin.com/in/petr-kruntorad)
