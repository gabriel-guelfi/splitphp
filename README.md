![dynamophp-logo.png](http://docs.dynamophp.org/wp-content/uploads/2021/03/dynamo-logo-medium.png)

Dynamo is a Micro-service architecture, RESTful friendly micro framework. It is made to be simple and lean, with a low learning curve. Its mission is to be a ready-to-work tool with simplified installation and interface. Knowledge required to understand and maintain an application written with this framework is basic PHP and OOP. No more installing and configuring thousands of softwares just to begin to code. 

A documentation is being built. You can refer to what is already in place in [here](http://docs.dynamophp.org)

## Server Host Config (Nginx Server Block) ##

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


#### Contact me: ####
* Skype: gabriel-guelfi
* Email: gabriel.valguelfi@gmail.com
* Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)
