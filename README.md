# Escort
##### An admin menu of extreme super awesomeness.

## Installation

Install escort within the 'modules' folder at webroot.

Make a copy of dev/config/example.config.json and set your local development
settings here. Add this file to your .gitignore file to prevent breaking of
team-members' dev setup.

    cp example.config.local.json config.json

Add to gitignore, second line if module/contrib or module/custom directories used

    modules/escort/dev/config/config.json
    modules/*/dev/config/config.json

Run the following from the command line from the escort/dev directory to have gulp
compile and watch for changes to both .scss files and .js files found within
the /dev folder.

    gulp

