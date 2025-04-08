-- Crear extensiones útiles
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Crear esquema personalizado si es necesario
CREATE SCHEMA IF NOT EXISTS cannabica;

-- Configurar el esquema por defecto
SET search_path TO cannabica, public;

-- Otorgar permisos
GRANT ALL ON SCHEMA cannabica TO cannabica;
GRANT ALL ON ALL TABLES IN SCHEMA cannabica TO cannabica;
GRANT ALL ON ALL SEQUENCES IN SCHEMA cannabica TO cannabica; 