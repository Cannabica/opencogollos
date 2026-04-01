FROM postgres:15

ENV POSTGRES_DB=${DB_DATABASE:-cannabica}
ENV POSTGRES_USER=${DB_USERNAME:-cannabica}
ENV POSTGRES_PASSWORD=${DB_PASSWORD:-cannabica_dev}

# Copiar scripts de inicialización personalizados
COPY ./docker/db/init-scripts/ /docker-entrypoint-initdb.d/

# Configuraciones adicionales de PostgreSQL
COPY ./docker/db/postgresql.conf /etc/postgresql/postgresql.conf
COPY ./docker/db/pg_hba.conf /etc/postgresql/pg_hba.conf

EXPOSE 5432

CMD ["postgres", "-c", "config_file=/etc/postgresql/postgresql.conf"] 