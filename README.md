# Wordpress Components

This repository contains all Wordpress themes and plugins developed at the DAI. If those themes and plugins reside in a
separate repository, they are added as [git-submodules](https://git-scm.com/docs/git-submodule). For development, an 
automated setup using [Docker](https://www.docker.com/) and [docker-compose](https://docs.docker.com/compose/) is 
provided.

## Prerequisites

* [Docker](https://www.docker.com/)
* [docker-compose](https://docs.docker.com/compose/)

## First start

1. Run `cp .env_template .env`, adjust Wordpress' database password as required.
2. Run docker-compose: `docker-compose up`. A fresh Wordpress instance should be started at 
[localhost:81](http://localhost:81), already containing all DAI plugins and templates.

## Afterwards

Some useful _docker-compose_ commands:
1. Hit `CTRL+C` to stop the running containers, or run `docker-compose stop` in a new terminal window within the 
repository's root directory.
2. To restart stopped containers run `docker-compose start` within the repository's root directory.
3. If you want to reset everything run `docker-compose down -v` to delete all containers (and subsequently the existing Wordpress instance). 

## Adding new templates or plugins to the Wordpress instance

All templates and plugins should be mounted as volumes by editing the _docker-compose.yml_. The storytelling 
plugin is mounted as follows:

```
(..)
    volumes:
    - ./plugins/storytelling:/var/www/html/wp-content/plugins/eagle-storytelling-application
(..)
```

The first path up to the `:` is the path to the plugin within the repository's directory structure. The second part 
declares where Docker is supposed to mount all contents of that directory within the Wordpress container.

All changes you make to the files in `./plugins/storytelling` are directly visible within your running Wordpress 
container.
