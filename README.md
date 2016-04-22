# File Inspector
Search for suspicious files in the specified location, and notification of the administrator. 

```
File inspector
Usage: php inspector.php [OPTIONS]
Required arguments:
	-d	--dir	      Inspection directory
	-f	--filename	Filename filter
	-m	--mtime		  File modification time (in days)
Optional arguments:
	-h	--help		Help info
Examples of usage:
php inspector.php -d /var/www/ -f '*.php' -m 10
php inspector.php -d /var/www/ -f '*.php' -m 0.5
```
