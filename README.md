# Escort
##### An admin menu of extreme super awesomeness.

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

