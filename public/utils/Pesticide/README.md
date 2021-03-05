# Pesticide v1.0 Stable #

It is a come-in-handy open source tool for debugging purposes. When you call, it reunite all the request and execution info, shows it centralized in a friendly Graphic Interface screen, then stops the script till that very moment.

Pesticide - PHP Debugger Tool is an Open Source, Free software. And is under the terms and conditions of GNU/GPL license. You not only have permission to distribute it, but is also encouraged to. But read the license, before. A copy of the GNU/GPL license is attached with the source code, within the root folder.

**Features:**

- An info header, filled with data of the current request URI, date and time and moment of execution;

- An easy-to-use complete data dumper;

- Current session and request info;

- Print custom messages(perfect for print Exception's messages);

- A complete execution history, with the info of each function/method, called in sequence, and the arguments passed to each of them;

- Everything organized in one single page;

- You can create your own themes with few lines of css; 



**Next versions steps:**

- Listen sections of the code, to register methods/functions called between "tracking()"" and "endtracking()";

- Instantiated classes depuration;

- Exceptions throwing history;

- Print specific debug data separately;



### How do I Get Started? ###

Simply put "pesticide" folder in any directory within your project, include Pesticide class, then instantiate its object, passing the URL, in which you placed the folder, to the constructor method. You can also pass the name of your theme, in the second constructor's argument:
```php
<?php
include '/path/to/pesticide/class.pesticide.php';

$pesticide = new Pesticide("/path/to"); // for default theme.
?>
```
Or, for custom theme:
```php
<?php
include '/path/to/pesticide/class.pesticide.php';

$pesticide = new Pesticide("/path/to","custom");
?>
```
No further configs needed.



### Dumping data: ###

Simply call the method "dump()", passing the variable and the name you want it to be called:
```php
<?php
$pesticide->dump($_SESSION, "My Session");
?>
```
It will create a drop-down nav, with the data dumped.
Example:
```php
<?php
// Dumping session data:

session_start();

include $_SERVER["DOCUMENT_ROOT"]."/my-site/pesticide/class.pesticide.php";
$pesticide = new Pesticide("/my-site");

$_SESSION["example"] = array();

for($i = 0; $i < 5; $i++){
	$_SESSION["example"][] = $i + 1;
}

$pesticide->dump($_SESSION, "My Session");
?>
```
The script above will print something like:
![Screenshot from 2017-03-22 14-06-01.png](https://bitbucket.org/repo/ypKoa47/images/2294351145-Screenshot%20from%202017-03-22%2014-06-01.png)




### Debugging my script: ###

Simply call method "debug()". You can pass a custom message and/or some data you want printed:
```php
<?php
$pesticide->debug(array("message1"), array("data"=>"printable data 1");
?>
```
Example:
```php
<?php
// Trying to connect to a database:

try{
    $cnn = new PDO('mysql:host=' . $dbcredentials->host . ';dbname=' . $dbcredentials->name, $dbcredentials->user, $dbcredentials->pass);
} catch(PDOException $dbex){
    $pesticide->debug(array($dbex->getMessage()), array($dbcredentials));
}
?>
```
The script above will shows a page with complete info about your execution and error, with a complete backtrace history and other info like current session, request and at what moment did the script stopped.

Hope it to be useful to you as it is to me! =)



## Customization ##

### Creating Pesticide themes: ###

Create a CSS file, similar to this shown below, inside "pesticide/style/themes/" directory:
```css
#debug-contents{
    color:#333;
    background-color: #f0f0f0;
}
.pesticide-dump{
    border: 0.25px solid #777;
    border-radius:5px;
    background-color:#F5F1C8;
}
.pesticide-dump:hover{
    background-color:#f0f0f0;
}
.pesticide-obs{
    color:#990000;
}
.blank-screen{
    border: 1px black solid;
    background-color:white;
    padding:5px;
}
```
This one above is the built-in default theme. Change the style rules in it, like borders and colors, as you like. Then save it with any name. 

Now you ready to go: just indicate the name you saved this CSS file you created, in the Pesticide's constructor method, as mentioned up there, on "Get Started" section of this documentation:
```php
<?php
include '/path/to/pesticide/class.pesticide.php';

$pesticide = new Pesticide("/path/to","custom-theme-name"); // CSS file's name, in this case would be "cutom-theme-name.css"
?>
```
Done!



### Who am i? ###

My name is Gabriel Valentoni Guelfi. I'm an I.T. professional, specialized in PHP and web development. And a technology enthusiastic.

#### Contact me: ####
* Skype: gabriel-guelfi
* Email: gabriel.valguelfi@gmail.com
* Website: [gabrielguelfi.com.br](http://gabrielguelfi.com.br)
* Blog: [Develog](http://blog.gabrielguelfi.com.br)
* Linkedin: [Gabriel Guelfi](https://br.linkedin.com/in/gabriel-valentoni-guelfi-30ba8b4b)
