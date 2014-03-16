wbblite2_to_mybb
================

Converter to move WbbLite2 Forum to Free MyBB Forum directly.

Usage
---

__Please use this script only if you know what you are doing! Make a copy of your Databse first!__

If you require help I am offering a payed service for this converting.

Now the steps:

* PreWork
  * Have a running WbbLite2 instance
  * Have a running (empty ) MyBB instance
  * Copy config.php.demo to config.php
  * Adjust values in config.php to match the forums values
  * Please also set AdminUsers here ( normally UID 1 )
* Import the Data
  * Call run.php ( Personally I would recommend to do this via CLI, if not possible use your browser )
* Work After
  * Enter the new Forum
  * Click "Forgott Password" and send yourself a new Password
  * Enter Forum with your user and 
  * Clear Caches under
    * Tools & Maintenance -> Cache Manager
    * Click everywhere "RebuildCache"
  * ReIndex Posts and Count everything else
    * Recount & Rebuild
  * Set Users to correct Group
  * Allow Groups usage of several Forums


Feature List
---

* Imports
  * Users
  * Boards
  * Threads
  * Posts
* Sets Default User 

Missing Features
---

* Import of Groups
* Userfields
* Avatars

Feature Required?
---

You are missing a feature? Either write it yourself and commit a merge request or pay me for adjusting this script.

Licence Informations
---

Please read LICENSE for more Informations.