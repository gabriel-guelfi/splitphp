### Introduction ###
![dynamophp-logo.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing) **DynamoPHP Framework**

***Knowing the language should be enough!***

**DynamoPHP** is a modern Service oriented, RESTful friendly framework. It is designed to be simple and lean, with a low learning curve. Its mission is to be a light tool with simplified interface. Knowledge required to understand and maintain an application written with this framework is basic PHP and OOP. No more installing and configuring thousands of softwares and learning how to turn comments into alien executable code just to begin to work. You don't have to be a "framework developer", being a PHP programmer should be enough. ;)

### What's New ###
* Version: 1.1.0
* Date: 2021-08-28

This release 1.1.0 has some improvements with database operations, included a feature to perform CURLs easier and have some fixes. 
See the *Release Notes.txt* file for further details.

### Requirements ###
* PHP 7+
* Nginx Web Server
* MySQL/MariaDB

**PS: I strongly suggest that you use it on a Linux environment**

### Get Started ###


### Server Host Config (Nginx Server Block) ###

```conf

server {
  listen 80;
  listen [::]:80 ipv6only=on;

  server_name example.com.br;

  root /path/to/your/app/root/public;
  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
    fastcgi_param QUERY_STRING $query_string;
  }

  location /resources {
    try_files $uri $uri/ =404;
  }

  location ~ \.php$ {
    try_files $uri /index.php =404;
    # This line below is a default path to PHP in linux systems. If your PHP is installed in another location, change it to your actual path.
    fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
  }
  location ~ \.git {
    deny all;
  }
}
```


### Authoring ###
* Skype: gabriel-guelfi
* Email: gabriel.valguelfi@gmail.com
* Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)
