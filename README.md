### Introduction ###
![splitphp-logo.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/SPLIT_PHP-logo-full.png)

***Knowing PHP should be enough!***

**SPLIT PHP** is born from the perception that the current tools available as frameworks in the marketplace for PHP developers are hyper-complex, heavy-weighted and old-styled. This one is not MVC, but built on top of SOA (Service Oriented Architecture), which makes it more reusable, straightforward, RESTful friendly and an easy-to-go for making micro-services systems. It is designed to be simple and lean, with a low learning curve. Its mission is to be a light tool with simplified interface. Knowledge required to understand and maintain an application written with this framework is basic PHP and OOP and the only dependency to run it is PHP itself.

---

### What's New ###

> Version: 1.4.10

> Release Date: 2024-07-26

> Last Update: 2024-07-26

* Added on composer. (Yes, it's finally here! xD)

* A built-in scafolding command "generate" was added in this version.

* DAO now has a "clearPersistence()" method to manually clear the data persistence.

* Added some Utils methods to work with string conversion.

* Dao::bindParams() method now accepts a second parameter for a placeholder name.

* Added the option of use "IN/NOT IN" in database operations.

* Expanded System::errorLog() method to include further, more detailed information.

* Fixed many minor bugs related to: NULL values handling and database access.

* Fixed SQL injection vulnerabilty. 

**Read more in the *Release Notes.txt* file.**

---

### Requirements ###
* PHP 7.4+ and its libs (Yes, that's all! ;P )

---

### The Concept ###
Why **"SPLIT"**? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it, if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on: **"Simplicity"**, **"Purity"**, **"Lightness"**, **"Intuitiveness"** and **"Target Mindness"**.

* **S**implicity: An engineer shall solve a problem using the simplest way possible. If there is a **simple solution that works, this is the right solution!** When it depends on tons of configurations and different files just to have an endpoint that prints out "Hello World!" on the screen, something is wrong.

* **P**urity: No tons of vendors and **no new vendor-specific syntaxes**, only the plain and good PHP and Object-Oriented Programming. A framework is intended to be a facilitator, a toolbox for a specific technology, so **the dependencies of the framework, shall be, ideally, only the technology itself**.

* **L**ightness: Related to the 2 concepts above, a **fast and light** software tool creates **cheaper and better quality** systems and avoids lots of headaches and money losses.

* **I**tuitiveness: A developer should only **have difficulties learning** how to use a lib or framework if this developer **isn't acquainted with the very technology** on which the lib or framework is based. Take the colossally successful example of *JQuery*. A dev who knows javascript, understands *JQuery* in a matter of hours. This is tightly related to the **"Purity"** concept. If a PHP senior has to practice for weeks before becoming really comfortable using a specific PHP framework or lib, again: something is definitely not right.

* **T**arget Mindness: A framework exists as a facilitator, so allows the engineer to not have to worry about technical issues and to be able to **focus only** on building **the solutions that address the business issues** for which the system is being created.

---

### Service Oriented Architecture (SOA) ###
A service is basically an encapsulated piece of functionality, which is **accessible from any part within the application**. So all services are accessible to one another from within the system, but not directly from the client, who only have access to an API Layer, which acts as a "gatekeeper" to the application. The result is a pool of reusable services which can be accessed from everywhere inside the application, but with controlled external access.
![splitphp-soa-diagram.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/introduction-soa-1.png)

The **SPLIT PHP Framework** represents its API Layer as ***WebServices***, where the applications's endpoints are defined. From within an endpoint the *WebService* can **call services** and/or **respond to the client**. In summary, to create an API using SPLIT PHP's SOA, the dev will **create the services**, which are classes that perform the actual operations, then **register endpoints** on a *WebService*. **Simple as that!**

---

### Getting Started ###
1. [Download](https://github.com/gabriel-guelfi/splitphp/archive/refs/tags/v1.2.12.zip) the framework source on the path of your application and unzip it. (the Composer solution is currently being studied)
2. Run **`php console setup`**, which will generate a "/config.ini" file, with some auto-settings.
3. Setup */config.ini* file according to your application's needs.
4. Turn on your app running the command: **`php console server:start`**.
5. Access http://localhost:8000 in the browser and you shall see the **SPLIT PHP** Welcome Page.

**Read more about it all at the [Official Documentation](http://splitphp.org/docs#getstarted)**.

*PS: The PHP's built-in server is meant to run only on dev environment. To run it properly on production, refer to the [documentation](http://splitphp.org/docs#deploy), at the section "Deploy".*

---

### Basic Usage ###
Your application will be basically built within three main folders under *"/application/"*

![splitphp-app-dirtree.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/splitphp-application-dirtree.png)

* **Under *"/application/routes/"* you create your *WebServices*, your API Layer:**

![splitphp-rest-service.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/splitphp-restservice-example.png)
> An endpoint is composed by 3 parts:
> 1. A HTTP verb ("GET")
> 2. The route ("/home")
> 3. The handler function, which executes when the endpoint is accessed at the route.
> 
> **In the example above, you could access that "/home" endpoint at "http://localhost:8000/site/home"**

* **Under *"/application/services/"* you create your *Services* on which all the magic happens. All business rules, data processing, database operations and so on, goes in the *Services*:**

![splitphp-service.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/splitphp-service-example.png)

* **Under *"/application/templates/"* goes your *Views*, your HTML templates.**

![splitphp-template.png](https://split-php-framework.s3.sa-east-1.amazonaws.com/splitphp-template-example.png)

> Note that this is the template rendered in the *WebService* in the first example, and this $data variable has the same
> name passed when it was being rendered. In these 3 examples, if you access "http://localhost:8000/site/home" you shall see 
> a web page with the contents of this HTML template.

**For more information visit the [Official Documentation](http://splitphp.org/docs)**

---

### Conclusion ###
**SPLIT PHP** requires only PHP to run, its syntax has nothing different from the PHP proper syntax, no "special annotations" that are something more than comments, no other engines other than its own to make it work fully. A dev who knows basic PHP and Object-Oriented Programming, shall not have problems learning how to use it confidently in a short period and the applications created with it are light and fast.

Visit the **[Official Website](http://splitphp.org)** to learn more about it and build rich and modern web applications in PHP.

This framework is part of the gigantic Technology Open Source community of the world. It's free to download, use, modify and distribute. For more info about its licensing, see the *MIT LICENSE.pdf* file. 

If you are a PHP enthusiastic and liked this work, help us enlarge the community and add new features to this framework, joining us. Send me an email and I'll add you to the SPLIT PHP's Slack Channel.

Ah, if you liked the work, don't forget to **star it and follow** this repository to be up to date with our new features. Thx!

---
### Authors ###
* Gabriel Valentoni Guelfi(first author and founder)
  > Email: gabriel.valguelfi@gmail.com

  > Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)

---

### Acknowledgments ###
* [João Paulo Varandas](https://www.linkedin.com/in/joaovarandas/), my former boss and the author of **[inPaaS](https://www.inpaas.com/)**, a Low-code platform written in Java. Much of the coding interface of **SPLIT PHP** is similar to **inPaaS**. Thank you for the huge amount of knowledge.
* [João Ricardo Escribano](https://www.linkedin.com/in/joaoescribano/) my friend and another technology monster who taught me and encouraged me much. Thank you for your patience and for have showed me the world of software engineering.
* [Thiago Valentoni Guelfi](https://www.linkedin.com/in/thiago-valentoni-guelfi-198a4174/) my brother who began much earlier than myself and encouraged me to create a framework of my own, by creating his own (https://github.com/Thiagoguelfi2012/openmvc-php)
* [Ronny Amarante](https://www.linkedin.com/in/ronnyamarante/) tech leader of **inPaaS** team, also have taught me much of what I know today. Thank you!
* [Fulvius Titanero Guelfi](https://www.linkedin.com/in/fulviusguelfi/) my uncle who opened my mind many times to new programming and technology paradigms. Thank you!
* To the wide community of devs around the world who posted unaccountable amounts of knowledge for free throughout the entire network, many thanks.

---