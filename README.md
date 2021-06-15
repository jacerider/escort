# Escort
##### An admin menu of extreme super awesomeness.

## How To Use

Escort does not use the Drupal menu system. Escort has its own menu system 
which is geared towards making the administration area easier-to-use for site 
administrators. The administration and configuration is located at:

    admin/config/user-interface/escort

There, you can enable menu regions on any side of the admin area. Also, when 
hovering over an icon, you can edit/disable/delete items and click and hold 
the "Drag" text to move the items to another menu or order them differently in 
the same menu. The blue "+" (plus) icon allows you add escort widgets of a 
variety of types. Changes to the menu item order/placement take place 
immediately via AJAX.

## Development Setup

First, you will need to install [NodeJS](https://nodejs.org/en/download/package-manager/).

Run the following from the command line within the /dev folder:

    npm install

Make a copy of dev/config/example.config.js and set your local development
settings here. Add this file to your .gitignore file to prevent breaking of
team-members' dev setup.

    cp example.config.js config.js

Run the following from the command line from the module directory to have gulp
compile and watch for changes to both .scss files and .js files found within
the /dev folder.

    gulp

## Extending Escort

TODO: More about adding your own escort plugins here.