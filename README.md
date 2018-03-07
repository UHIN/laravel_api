# laravel_api
Devs: To create a new release for composer you need to make your commit, 
add a tag (new version number), and then push the commit and the tag. 

In PHPStorm: Commit, click on log, right click on commit and add tag, push (check push tags).



For development: 

In a service using this library you will make two changes to the composer.json to do development.

First change the version of the require for this library to dev-master

Second add this block of code to your composer.json (editing the path):

"repositories": [
        {
            "type": "path",
            "url": "/Users/rmclelland/Projects/laravel_api"
        }
    ]
