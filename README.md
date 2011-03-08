Phork Framework
===============

by Phork Labs
<http://phorklabs.com/>


Introduction
------------

Phork is a lightweight Open Source PHP 5 framework that provides a solid foundation for developing robust applications. Phork is an object oriented MVC (Model View Controller) framework and includes an Active Record ORM with automatic relationship loading, as well as the ability to register events to run before and after loading, saving and deleting records. Phork also includes an API framework, URL routing, page caching, template caching, a flexible debugging system, optional language translations, and several useful utilities and extensions.


Quick Start
-----------

1.  Download and unzip the Phork package.

2.  Determine if you want to start with the standard base or the lite base. [Standard vs. Lite](http://phork.org/manual/install/instructions/#sitetype)

3.  Recursively copy either phork/sites/lite/ or phork/sites/standard/ to phork/sites/public/.

4.  Copy the example configuration files from phork/sites/public/config/example/ to phork/sites/public/config/live/ (exclude the config folder).

5.  Edit the configuration files in phork/sites/public/config/live/.

6.  Point your server's document root to /path/to/phork/sites/public/htdocs/.


Manual
------

Check out the [Phork manual](http://phork.org/manual) for more detailed instructions, options, examples, and a breakdown of all the classes.


License
-------

Licensed under The MIT License
<http://www.opensource.org/licenses/mit-license.php>