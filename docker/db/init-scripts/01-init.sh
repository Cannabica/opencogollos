#!/bin/bash
set -e

# Load environment variables (they should be available from the container environment)
DB_USER=${POSTGRES_USER}
DB_NAME=${POSTGRES_DB}

echo "Initializing database: $DB_NAME with user: $DB_USER"

psql -v ON_ERROR_STOP=1 --username "$DB_USER" --dbname "$DB_NAME" <<-EOSQL
    -- Create useful extensions
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
    CREATE EXTENSION IF NOT EXISTS "pgcrypto";

    -- Set default search path to public
    ALTER DATABASE "$DB_NAME" SET search_path TO public;

    -- Grant permissions on public schema
    GRANT ALL ON SCHEMA public TO "$DB_USER";
    GRANT ALL ON ALL TABLES IN SCHEMA public TO "$DB_USER";
    GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO "$DB_USER";
EOSQL

echo "Database initialization completed successfully."
