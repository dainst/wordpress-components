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
3. If you want to reset everything run `docker-compose down -v` to delete all containers (and subsequently the existing
 Wordpress instance).

## Adding new plugins to the Wordpress instance

Put your desired plugins into directory `./plugins/`. The plugin sub directories like "wordpress-storytelling"
will be shared with your running Docker engine.

All local changes to the files in e.g. `./plugins/wordpress-storytelling` are directly visible within your running Wordpress
container.

## Adding new themes to the Wordpress instance

Put your desired themes into directory `./themes/`. The theme sub directories like "shap-theme"
will be shared with your running Docker engine.

All local changes to the files in e.g. `./themes/shap-theme` are directly visible within your running Wordpress
container.

## Updating the shap-theme template

The shap-theme template lives in [a separate repository](https://github.com/dainst/wordpress-shap-theme) and is included in this
repository as a submodule.

When checking out the main repository for the first time make sure to initialize
the submodules by running:

    git submodule init
    git submodule update

In order to update the template to the newest revision run:

    cd themes/shap-theme/
    git pull
    cd ../..

To build und publish the new docker image in dockerhub run:

     docker build --pull --tag dainst/wordpress-components:latest .
     docker push dainst/wordpress-components:latest

When successful the service shap-cms can be updated on the docker swarm, i.e.
by using the update function inside portainer and checking "pull latest image version".
