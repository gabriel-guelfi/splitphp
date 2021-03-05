# Criarium E-Commerce #

- Current Version: 1.0.0

- Published: No

- License: Private / Patented



## Server Requirements ##

- O.S.: Linux Ubuntu/18.04.4

- Web Server: Nginx/1.14.0

- PHP/7.2.24

- Mysql/14.14 Distrib 5.7.30



## Libraries & Frameworks ##

- Dynamo PHP Framework/0.9

- AngularJS/1.7.9

- Jquery/3.3.1

- Bootstrap/4.1.0

- Font Awesome/5.0.13 - free



## Server Host Config ##

```conf

server {
  listen 80;
  listen [::]:80;

  server_name lojaexemplocriarium.com.br;

  root /var/www/html/criarium/public;
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



## Local Develop Environment Sync ##

```shell

sudo rsync -rv /home/gabriel/Develop/criarium/. --exclude=.git --exclude=.gitignore --exclude=README.md /var/www/html/criarium/; sudo chown -Rf www-data:www-data /var/www/html/criarium/application; sudo chmod -Rf 755 /var/www/html/criarium/*; sudo chmod -Rf 777 /var/www/html/criarium/public/resources/upload/*;

```



#### Contact me: ####
* Skype: gabriel-guelfi
* Email: gabriel.valguelfi@gmail.com
* Website: [gabrielguelfi.com.br](http://gabrielguelfi.com.br)
* Blog: [Develog](http://blog.gabrielguelfi.com.br)
* Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)