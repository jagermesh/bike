BIKE
====

Bike is a lightweight MySQL admin tool. List of features is small but it must be enough for simple management tasks.

# Installation

First of all you need PHP 5.3. Sorry, older versions are not supported and not going to be supported :(

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

# What is Library section?

In library I put some useful SQL snippets. You can put your own, it's in file <code>/library/library.json</code> - just an array in JSON format:

```
{ "Show tables list":{"name":"Show tables list","sql":"SHOW TABLES"}
, "Show processes list":{"name":"Show processes list","sql":"SHOW PROCESSLIST"}
, "Show tables status":{"name":"Show tables status","sql":"SHOW TABLE STATUS"}
, "Show table structure":{"name":"Show table structure","sql":"DESC {{Table name}}"}
, "Show table CREATE statement":{"name":"Show table CREATE statement","sql":"SHOW CREATE TABLE {{Table name}}"}
}
```

I'll render nice input dialog for placeholders, enclosed in {{ }}.

# Demo

Check it <a target="_blank" href="http://www.itera-research.com/demo/bike/">here</a>.

# Packed version

Don't want upload 300 files? Use packed version - upload 3 files from <a href="https://github.com/jagermesh/bike/tree/master/packed-version">/packed-version</a> to any writeable folder on your server and go there by browser - you'll get working instance of this nice MySQL management tool.

# Security & Restricted access

On current stage I don't care about login functionality. Put Bike into folder with name like "tASTDKUWYVEjhas" or just use Apache httpauth as workaround.

# Terms & Conditions

You can use Bike. Anywhere. For any purposes. And I'm not responsible for anything.

# Contacts

Have a question? Mail me to <a href="mailto:jagermesh@gmail.com">jagermesh@gmail.com</a>
