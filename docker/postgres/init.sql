-- Crear la base de datos water
CREATE DATABASE water;

-- Crear el usuario admin con contraseña 12345
CREATE USER admin WITH ENCRYPTED PASSWORD '12345';

-- Dar permisos al usuario admin sobre la base de datos water
GRANT ALL PRIVILEGES ON DATABASE water TO admin;

-- Cambiar al usuario admin como propietario de la base de datos
ALTER DATABASE water OWNER TO admin;