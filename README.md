### Introduction ###
![dynamophp-logo.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/DynamoPHP-logo-letreiro.png)

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

***PS: I strongly suggest that you use it on a Linux environment**

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
```shell
sudo chmod -Rf u=rwx,g=rx,o=rx [path/to/your/application/root]
```

If you're setting up a DEV environment on your local machine, for example, you'd rather provide ownership for both: the www-data user and your user as well:
1. First the ownership:
```shell
sudo chown -Rf www-data:[your user group name] [path/to/your/application/root]
```

2. And then the permissions:
```shell
sudo chmod -Rf u=rwx,g=rwx,o=rx [path/to/your/application/root]
```

The differences between the two setups are slight: in the second, we provide ownership to www-data user and to all users in the group of your personal user, as well, and an additional *write* permission to users belonging to that group.
### Nginx Server Block ###
This framework was designed to work with Nginx Web Server, which is a much more performative and modern option. It doesn't mean that it won't work in older solutions as Apache, for example, but it was never tested. For more information about Nginx, visit the official website (https://www.nginx.com/resources/wiki/).

Nginx works with *Server Blocks*, which are files with settings about the web server, similar to the *virtual hosts* from Apache. If you don't know how to setup an *Nginx Server Block* file, take a look and follow this [tutorial](https://www.digitalocean.com/community/tutorials/how-to-set-up-nginx-server-blocks-virtual-hosts-on-ubuntu-16-04) before continue.

Below is an example of a typical **DynamoPHP** *Nginx Server Block* file setup:

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
6. In *location ~ \\.php$* we're doing settings about your local PHP installation
7. Finally in *location ~ \\.git* we're denying all requests directed to *.git* location

That's it! If you're interested to learn further about setting up Nginx Server Blocks visit the Nginx Official Documentation (https://nginx.org/en/docs/)

### The I/O cycle ###
A web application functions mainly through request/response cycles("I/O" refers to "Input"/"output"): the *client* makes a request, sending data to the *cloud server*, the server process that data and responds to the *client* with more data, which can be, HTML, XML, JSON or even just status information.

**DynamoPHP** works on *server-side*, processing the requests, basically in this order: 
1. Server receives the request and calls System(the entry point class of Dynamo)
2. System sets the configs and calls the proper Rest Service
3. Rest Service resolves the request input data and call the Services
4. Services do all the work, as updating data on the database, for instance, and responds to the Rest Service
5. Rest Service builds a *response* object and send it back to the *client*

**See a diagram of this cycle below:**

![dynamophp-cycle.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/DynamoPHP-I_O-cycle-526X850.png)

### Interface ###
Your application will be basically built within three main folders under "/application"
![dynamophp-app-dirtree.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing)

Under "/routes" you create your *Rest Services* which are pretty much your router and API layer:
![dynamophp-rest-service.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing)

Under "/services" you create your *Services* on which all the magic happens. All business rules, data processing, database operations and so on, goes within the *Services*:
![dynamophp-service.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing)

Under "/templates" goes your HTML. These templates can, then, be rendered in *Services* and *Rest services*
![dynamophp-template.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing)
![dynamophp-render-template.png](https://drive.google.com/file/d/1boSMXF5OwB48H3C0ikqyhfKHDbiRbHkB/view?usp=sharing)

***PS: For more information visit the [Official Documentation](http://docs.dynamophp.org)**

### Conclusion ###
**DynamoPHP** requires only basic software setup: a web server, a database server and the PHP itself, its syntax has nothing different from the PHP proper syntax, no "special comments" that are something more than comments, no other engines other than its own to make it work fully.

Visit the [Official Documentation](http://docs.dynamophp.org) to learn more about it and build rich and modern web applications, in whatever architecture you need.

This framework is part of the gigantic Technology Open Source community of the world. It's free to download, use, modify and distribute. For more info about its licensing, see the *MIT LICENSE.pdf* file. If you are a PHP enthusiastic and liked this work, help us enlarge the community and add new features to this framework, joining us. Send me an email and I'll add you to DynamoPHP's Slack Channel.

Ah, if you liked the work, don't forget to star it and follow this repository to be up to date with our new features. Thx!
### Authors ###
* Gabriel Valentoni Guelfi(first author and founder)
  > Email: gabriel.valguelfi@gmail.com

  > Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)

### Acknowledgments ###
* [João Paulo Varandas](https://www.linkedin.com/in/joaovarandas/), my former boss and the author of **inPaaS**, a Low-code platform written in Java. Much of the coding interface of **DynamoPHP** is similar to **inPaaS**. Thank you for the huge amount of knowledge.
* [João Ricardo Escribano](https://www.linkedin.com/in/joaoescribano/) my friend and another technology monster who taught me and encouraged me much. Thank you for your patience and for have showed me the world of software engineering.
* [Thiago Valentoni Guelfi](https://www.linkedin.com/in/thiago-valentoni-guelfi-198a4174/) my brother who began much earlier than myself and encouraged me to create a framework of my own, by creating his own (https://github.com/Thiagoguelfi2012/openmvc-php)
* [Ronny Amarante](https://www.linkedin.com/in/ronnyamarante/) tech leader of **inPaaS** team, also have taught me much of what I know today. Thank you!
* [Fulvius Titanero Guelfi](https://www.linkedin.com/in/fulviusguelfi/) my uncle who opened my mind many times to new programming and technology paradigms. Thank you!
* To the wide community of devs around the world who posted unaccountable amounts of knowledge for free throughout the entire network, many thanks.