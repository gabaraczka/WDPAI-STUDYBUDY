FROM postgres

ENV POSTGRES_PASSWORD=docker
ENV POSTGRES_DB=db
ENV POSTGRES_USER=docker

COPY init.sql /docker-entrypoint-initdb.d/