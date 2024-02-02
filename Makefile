.PHONY: build up start stop exec clean

I = frontend
CONTAINER_NAME = my-docker-container
COMPOSE_FILE = docker-compose.yml
DOCKER_EXEC = docker-compose exec $(I)

build:
	docker-compose build $(I)

up:
	docker-compose -f $(COMPOSE_FILE) up

start:
	docker-compose -f $(COMPOSE_FILE) up -d

stop:
	docker-compose -f $(COMPOSE_FILE) down

exec:
	docker exec -it $(CONTAINER_NAME) /bin/bash

clean:
	docker-compose -f $(COMPOSE_FILE) down
	docker rmi $(IMAGE_NAME) || true

clear-sessions:
	sudo rm -rf crm/base/crm/backend/runtime/sessions/*
	sudo rm -rf crm/base/crm/frontend/runtime/sessions/*

chmod:
	sudo chmod 0777 ./ -R

restart-apache:
	docker-compose exec $(I) service apache2 reload

copy_and_restart:
	$(DOCKER_EXEC) cp /usr/local/etc/php/conf.d/xdebug-new.ini /usr/local/etc/php/conf.d/xdebug.ini
	make restart-apache;

off-debug:
	 $(DOCKER_EXEC) cp /usr/local/etc/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug-new.ini; \
     $(DOCKER_EXEC) sed -i 's#^xdebug.start_with_request=yes#xdebug.start_with_request=no#' /usr/local/etc/php/conf.d/xdebug-new.ini; \
     make copy_and_restart

on-debug:
	 $(DOCKER_EXEC) cp /usr/local/etc/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug-new.ini; \
     $(DOCKER_EXEC) sed -i 's#^xdebug.start_with_request=no#xdebug.start_with_request=yes#' /usr/local/etc/php/conf.d/xdebug-new.ini; \
     make copy_and_restart

migrate:
	 $(DOCKER_EXEC) php yii migrate/up --interactive

mc:
	 $(DOCKER_EXEC) php yii migrate/create $(name)
md:
	 $(DOCKER_EXEC) php yii migrate/down
ci:
	 $(DOCKER_EXEC) composer install

cd:
	 $(DOCKER_EXEC) composer remove $(d)
cr:
	$(DOCKER_EXEC) composer require $(r)

commit:
	cd crm/base/crm/common/modules/key${m} \
	&& git pull \
	&& git checkout -b task-$(t) \
	&& git add . \
	&& git commit -m "changes" \
	&& git push --set-upstream origin task-$(t) \
	&& git checkout dev \
	&& git branch -d task-$(t)

commit-t:
	cd crm/base/crm/common/templates/cofoTemplate \
	&& git pull \
	&& git checkout -b task-$(t) \
	&& git add . \
	&& git commit -m "changes" \
	&& git push --set-upstream origin task-$(t) \
	&& git checkout dev \
    && git branch -d task-$(t)

chmod-migrations:
	sudo chmod 0777 app/console/migrations/*