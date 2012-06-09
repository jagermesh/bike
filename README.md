BIKE
====

Bike is a lightweight MySQL admin tool. List of features is small but it must be enough for simple management tasks.

# Installation

## How to get it

Clone GitHub repository or download zip from <a href="https://github.com/jagermesh/bike/downloads">downloads</a> section. 

## Going to use Bike for WordPress, Joomla, Drupal management?

If you are using some popular CMS (WordPress, Joomla, Drupal) - just unpack package into subfolder of your current CMS installation. That's it. Bike will identify what kind of CMS you are using and will take connection parameters from regarding config files.

## No CMS?

If you are not using any CMS - create file <code>/config.db.php</code> in root Bike's folder and put following connection parameters into it.

```
<?php

br()
  ->config()
    ->set( 'db'
         , array( 'engine'   => 'mysql'
                , 'hostname' => 'localhost'
                , 'name'     => '[dbname]' 
                , 'username' => '[dbuser]'
                , 'password' => '[dbpassword]'
                ));
```

## Saved queries

Bike is not creating any tables in your database so if you are going to save your code snippets and use them later or share with other users - please adjust permissions for folder <code>/user</code> in root Bike's folder. It must be writeable for your webserver.

# What can I do with Bike

Basically Bike is an SQL runner, so, actually, you can do pretty much if you are familiar with SQL.

Also there are some nice features included
- 40 recent queries
- Named saved queries
- Queries library
- Nice AJAX UI :)

# Demo

Check it <a href="http://www.itera-research.com/demo/bike/">here</a>.

# Security & Restricted access

On current stage I don't care about login screen. Put Bike into folder with name like "tASTDKUWYVEjhas" or just use Apache httpauth as workaround.

# Terms & Conditions

You can use Bike. Anywhere. For any purposes. And I'm not responsible for anything.

# Contacts

Have a question? Mail me to <a href="mailto:jagermesh@gmail.com">jagermesh@gmail.com</a>
