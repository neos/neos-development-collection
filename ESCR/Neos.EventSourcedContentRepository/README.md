# Event Sourced Content Repository

This repository is a **read-only subsplit** of a package that is part of the [Content Repository Development Collection](https://github.com/neos/contentrepository-development-collection)

## !!! Not ready for production !!!

Please note that this repository is not yet ready for productive use and we will still change APIs without notice.
If you dare to use it anyways, you can install this package via composer:

```json
{
    # ...
    "require": {
        "neos/event-sourced-content-repository": "dev-master"
        # ...
    },
    "repositories": {
        # ...
        "event-sourced-content-repository": {
            "type": "git",
            "url": "git@github.com:neos/event-sourced-content-repository.git"
        },
    },
}

```


Contribute
----------

If you want to contribute to the Event Sourced Content Repository, please have a look at
https://github.com/neos/contentrepository-development-collection - it is the repository
used for development and all pull requests should go into it.
