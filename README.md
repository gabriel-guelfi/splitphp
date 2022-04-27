### Introduction ###
![dynamophp-logo.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/SPLIT_PHP-logo-full.png)

***Knowing PHP should be enough!***

**SPLIT PHP** is born from the perception that the current tools available as frameworks in the marketplace for PHP developers are hyper-complex, heavy-weighted and old-styled. This one is not MVC, but built on top of SOA (Service Oriented Architecture), which makes it more reusable, straightforward, RESTful friendly and an easy-to-go for making micro-services systems. It is designed to be simple and lean, with a low learning curve. Its mission is to be a light tool with simplified interface. Knowledge required to understand and maintain an application written with this framework is basic PHP and OOP and the only dependency to run it is PHP itself.

---

### What's New ###

> Version: 1.2.3

> Date: 2022-04-26

* **DynamoPHP** is now **SPLIT PHP**. See the section "The Concept" to understand why.

* Added an Anti-XSRF-hack system.

* Improved error handling.

* Included very nice features in the *Data Access Object* class.

* Changed *RestServices* in a way to make them much more flexible.

* Reengineered all the structure using *namespaces*, so now services can have the same class name without conflict.

* Configurations is now based on *environment variables*, so you can get rid of the *config.ini* file on production environments.

**Read more in the *Release Notes.txt* file.**

---

### Requirements ###
* PHP 7+ (Yes, that's it! ;P )

---

### The Concept ###
Why **"SPLIT"**? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it, if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on: "Simplicity", "Purity", "Lightness", "Intuitiveness" and "Target Mindness".

* **S**implicity: An engineer shall solve a problem using the simplest way possible. If there is a **simple solution that works, this is the right solution!** When it depends on tons of configurations and different files just to have an endpoint that prints out "Hello World!" on the screen, something is wrong.
* **P**urity: No tons of vendors and **no new vendor-specific syntaxes**, only the plain and good PHP and Object-Oriented Programming. A framework is intended to be a facilitator, a toolbox for a specific technology, so **the dependencies of the framework, shall be, ideally, only the technology itself**.
* **L**ightness: Related to the 2 concepts above, a **fast and light** software tool creates **cheaper and better quality** systems and avoids lots of headaches and money losses.
* **I**tuitiveness: A developer should only **have difficulties learning** how to use a lib or framework if this developer **isn't acquainted with the very technology** on which the lib or framework is based. Take the colossally successful example of *JQuery*. A dev who knows javascript, understands *JQuery* in a matter of hours. This is tightly related to the **"Purity"** concept. If a PHP senior has to practice for weeks before becoming really comfortable using a specific PHP framework or lib, again: something is definitely not right.
* **T**arget Mindness: A framework exists as a facilitator, so the engineer don't have to worry about technical issues and be able to **focus only** on building **the solutions that address the business issues** for which the system is being created.

---

### Get Started ###
1. Download the framework source on the path of your application using this [link](https://github.com/gabriel-guelfi/DynamoPHP/archive/refs/tags/v1.2.zip) 
2. Rename the file */example.config.ini* to "/config.ini"
3. Setup */config.ini* file according to your application's needs
4. From */public*, run PHP built-in server at "localhost": `php -S localhost`
5. Access http://localhost/ in the browser and you shall see the **SPLIT PHP** Welcome Page.

**Read more about it at the [Official Documentation](http://splitphp.org/docs#getstarted)**

---

### The Request/Response cycle ###
A web application functions mainly through request/response cycles: the *client* makes a request, sending data to the *cloud server*, the server process that data and responds to the *client* with more data, which can be, HTML, XML, JSON or even just status information.

**DynamoPHP** works on *server-side*, processing the requests, basically in this order: 
1. Server receives the request and calls System(the entry point class of Dynamo)
2. System sets the configs and calls the proper Rest Service
3. Rest Service resolves the request input data and call the Services
4. Services do all the work, as updating data on the database, for instance, and responds to the Rest Service
5. Rest Service builds a *response* object and send it back to the *client*

**See a diagram of this cycle below:**

![dynamophp-cycle.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/DynamoPHP-I_O-cycle-526X850.png)

---

### Interface ###
Your application will be basically built within three main folders under "/application"
![dynamophp-app-dirtree.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/Dynamo-application-dirtree.png)

Under "/routes" you create your *Rest Services* which are pretty much your router and API layer:
![dynamophp-rest-service.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/dynamo-restservice-example.png)
In the example above, you could access that "/home" endpoint at "your-application-domain.com/site/home"

Under "/services" you create your *Services* on which all the magic happens. All business rules, data processing, database operations and so on, goes within the *Services*:
![dynamophp-service.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/dynamo-service-example.png)

Under "/templates" goes your HTML templates.
![dynamophp-template.png](https://dynamophp-resources.s3.sa-east-1.amazonaws.com/img-docs/dynamo-template-example.png)
Then, you can render it in *Services* and *Rest services*. (See the Rest Service example above)

***PS: For more information visit the [Official Documentation](http://docs.dynamophp.org)**

---

### Presets ###
There are some presets, which consist of packages of *Services*, *Rest Services* and some *SQL*. They are already pre-built functionalities often used in systems, that you can import inside your application without having to develop'em every time. Here are some that I created myself:

* [IAM](https://github.com/gabriel-guelfi/IAM)
* [Utils Service Pack](https://github.com/gabriel-guelfi/Utils-Service-Pack)
* [Log Web Viewer](https://github.com/gabriel-guelfi/Log-Webviewer)
* [Application Modules](https://github.com/gabriel-guelfi/Application-Modules)

If you create some cool abstract functionality with **DynamoPHP**, share it with the community.

---

### Conclusion ###
**DynamoPHP** requires only basic software setup: a web server, a database server and the PHP itself, its syntax has nothing different from the PHP proper syntax, no "special comments" that are something more than comments, no other engines other than its own to make it work fully.

Visit the [Official Documentation](http://docs.dynamophp.org) to learn more about it and build rich and modern web applications, in whatever architecture you need.

This framework is part of the gigantic Technology Open Source community of the world. It's free to download, use, modify and distribute. For more info about its licensing, see the *MIT LICENSE.pdf* file. If you are a PHP enthusiastic and liked this work, help us enlarge the community and add new features to this framework, joining us. Send me an email and I'll add you to DynamoPHP's Slack Channel.

Ah, if you liked the work, don't forget to star it and follow this repository to be up to date with our new features. Thx!

---
### Authors ###
* Gabriel Valentoni Guelfi(first author and founder)
  > Email: gabriel.valguelfi@gmail.com

  > Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)

---

### Acknowledgments ###
* [João Paulo Varandas](https://www.linkedin.com/in/joaovarandas/), my former boss and the author of **[inPaaS](https://www.inpaas.com/)**, a Low-code platform written in Java. Much of the coding interface of **DynamoPHP** is similar to **inPaaS**. Thank you for the huge amount of knowledge.
* [João Ricardo Escribano](https://www.linkedin.com/in/joaoescribano/) my friend and another technology monster who taught me and encouraged me much. Thank you for your patience and for have showed me the world of software engineering.
* [Thiago Valentoni Guelfi](https://www.linkedin.com/in/thiago-valentoni-guelfi-198a4174/) my brother who began much earlier than myself and encouraged me to create a framework of my own, by creating his own (https://github.com/Thiagoguelfi2012/openmvc-php)
* [Ronny Amarante](https://www.linkedin.com/in/ronnyamarante/) tech leader of **inPaaS** team, also have taught me much of what I know today. Thank you!
* [Fulvius Titanero Guelfi](https://www.linkedin.com/in/fulviusguelfi/) my uncle who opened my mind many times to new programming and technology paradigms. Thank you!
* To the wide community of devs around the world who posted unaccountable amounts of knowledge for free throughout the entire network, many thanks.

---