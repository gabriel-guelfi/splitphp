### Introduction ###
![dynamophp-logo.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing) **DynamoPHP Framework**

***Knowing the language should be enough!***

**DynamoPHP** is a modern Service oriented, RESTful friendly framework. It is designed to be simple and lean, with a low learning curve. Its mission is to be a light tool with simplified interface. Knowledge required to understand and maintain an application written with this framework is basic PHP and OOP. No more installing and configuring thousands of softwares and learning how to turn comments into alien executable code just to begin to work. You don't have to be a "framework developer", being a PHP programmer should be enough. ;)

### What's New ###
* Version: 1.1.3
* Date: 2021-08-28

This release 1.1.3 has some improvements with database operations, included a feature to perform CURLs easier and have some fixes. 
See the *Release Notes.txt* file for further details.

### Requirements ###
* PHP 7+
* Nginx Web Server
* MySQL/MariaDB

**PS: I strongly suggest that you use it on a Linux environment**

### Get Started ###
1. Clone or download the framework source on the path of your application
2. Rename the file *_config.ini* to "config.ini"
3. Setup *config.ini* file according to your application's needs
4. Give the right permissions and ownerships to the Nginx user (See the section about it below)
5. Setup your Nginx Server Block (See the section about it below)
6. Access in the browser the URL set on the Server Block and you shall see the **DynamoPHP** Welcome Page.

### Permissions & Ownership ###
**DynamoPHP** writes automatic content on the directories within your application, like specific log files and database metadata cache files, so you must provide the right permissions and ownership to the Nginx user, which is named "www-data":

1. First the ownership:
```shell
sudo chown -Rf www-data:www-data [path/to/your/application/root]
```

2. And then the permissions:
`sudo chmod -Rf u=rwx,g=rx,o=rx [path/to/your/application/root]`

If you're setting up a DEV environment on your local machine, for example, you'd rather provide ownership for both: the www-data user and your user as well:
1. First the ownership:
`sudo chown -Rf www-data:[your user group name] [path/to/your/application/root]`

2. And then the permissions:
`sudo chmod -Rf u=rwx,g=rwx,o=rx [path/to/your/application/root]`

The differences between the two setups are slight: in the second, we provide ownership to www-data user and to all users in the group of your personal user, as well, and an additional *write* permission to users belonging to that group.
### Nginx Server Block ###
This framework was designed to work with Nginx Web Server, which is a much more performative and modern option. It doesn't mean that it won't work in older solutions as Apache, for example, but it was never tested. For more information about Nginx, visit the official website (https://www.nginx.com/resources/wiki/).

Nginx works with *Server Blocks*, which are files with settings about the web server, similar to the *virtual hosts* from Apache. If you don't know how to setup an *Nginx Server Block* file, take a look and follow this [tutorial](https://www.digitalocean.com/community/tutorials/how-to-set-up-nginx-server-blocks-virtual-hosts-on-ubuntu-16-04) before continue.

Below is an example of a *Nginx Server Block* file:

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
    # This line below is a default path to PHP in linux systems. If your PHP is installed in another location, change it to your actual fpm path.
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
In this file above, we do the following:
1. First we say to Nginx which port it should listen to, in this case port 80 (for SSL listen to 443)
2. Then we set the nameserver, which is the domain of your application
3. Define the path of the directory on which your application is placed (Don't forget the "/public" in the end)
4. In the *location /* section we say to Nginx directs the request to the input point of the framework
5. In *location /resources* we are reserving this route to serve your static files
6. In *location ~ \.php$* we're doing settings about your local PHP installation
7. Finally in *location ~ \.git* we're denying all requests directed to *.git* location

That's it! If you're interested to learn further about setting up Nginx Server Blocks visit the Nginx Official Documentation (https://nginx.org/en/docs/)

### Authoring ###
* Skype: gabriel-guelfi
* Email: gabriel.valguelfi@gmail.com
* Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)
