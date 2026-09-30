-- Ejecutar una sola vez en la base de datos EFactWeb de producción.
-- Antes: exportar la base completa desde phpMyAdmin.
-- Este script asume que `equipo` está vacío, como confirmó la consulta de preflight.
-- No importa datos de prueba desde efactweb_local.
-- La cuenta `iteracion` existente se conserva como Superadmin sin cambiar su contraseña.

ALTER TABLE `usuario`
    ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'Admin' AFTER `contrasena`,
    ADD COLUMN `id_empresa` INT(11) NULL AFTER `role`,
    ADD UNIQUE KEY `usuario_id_empresa_unique` (`id_empresa`),
    ADD CONSTRAINT `usuario_id_empresa_foreign`
        FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON DELETE CASCADE;

-- Conserva la cuenta y contraseña existentes de EFactWeb y la habilita como Superadmin.
UPDATE `usuario`
SET `role` = 'Superadmin', `id_empresa` = NULL
WHERE `nombre` = 'iteracion';

-- La tabla está vacía; se conservan únicamente las columnas propias del equipo.
ALTER TABLE `equipo`
    DROP PRIMARY KEY,
    DROP INDEX `equipo_movimiento_index`,
    DROP INDEX `equipo_cuil_index`,
    DROP COLUMN `id_movimiento`,
    DROP COLUMN `nombre_cliente`,
    DROP COLUMN `estado`,
    DROP COLUMN `fecha`,
    DROP COLUMN `observacion`,
    DROP COLUMN `cuil`,
    DROP COLUMN `contrasena`,
    ADD PRIMARY KEY (`id`, `id_empresa`),
    ADD CONSTRAINT `equipo_id_empresa_foreign`
        FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON DELETE CASCADE;

CREATE TABLE `movimiento` (
    `id` INT(10) UNSIGNED NOT NULL,
    `id_empresa` INT(11) NOT NULL,
    `id_equipo` INT(11) NOT NULL,
    `nombre_cliente` VARCHAR(150) DEFAULT NULL,
    `estado` VARCHAR(150) DEFAULT NULL,
    `fecha` VARCHAR(50) DEFAULT NULL,
    `observacion` TEXT DEFAULT NULL,
    `cuil` VARCHAR(30) DEFAULT NULL,
    `contrasena` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `movimiento_id_empresa_index` (`id_empresa`),
    KEY `movimiento_id_equipo_index` (`id_equipo`),
    KEY `movimiento_cuil_index` (`cuil`),
    KEY `movimiento_equipo_empresa_index` (`id_equipo`, `id_empresa`),
    CONSTRAINT `movimiento_id_empresa_foreign`
        FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `movimiento_equipo_empresa_foreign`
        FOREIGN KEY (`id_equipo`, `id_empresa`)
        REFERENCES `equipo` (`id`, `id_empresa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas de soporte Laravel. Sesiones y caché están configuradas en archivos,
-- pero se crean para que el historial estándar de migraciones quede consistente.
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` BIGINT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` BIGINT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` SMALLINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED DEFAULT NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT DEFAULT NULL,
    `cancelled_at` INT DEFAULT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(255) NOT NULL,
    `connection` VARCHAR(255) NOT NULL,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
    KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('0001_01_01_000000_create_users_table', 2),
    ('0001_01_01_000001_create_cache_table', 2),
    ('0001_01_01_000002_create_jobs_table', 2),
    ('2026_09_30_131010_create_movimiento_table', 1),
    ('2026_09_30_135003_add_role_and_company_to_usuario_table', 1),
    ('2026_09_30_140128_set_default_role_for_new_usuarios', 1),
    ('2026_09_30_142727_transfer_reparacion_equipment_to_efactweb', 1),
    ('2026_09_30_142728_remove_local_company_foreign_key_from_usuario', 1),
    ('2026_09_30_151829_merge_reparacion_admins_into_efactweb_usuario', 1),
    ('2026_09_30_151830_bootstrap_migration_history_in_efactweb', 1),
    ('2026_09_30_162831_make_equipo_id_company_composite_and_require_movement_equipment', 3);

-- Comprobar que el usuario elegido conserve su contraseña y tenga el rol esperado.
SELECT `id`, `nombre`, `role`, `id_empresa` FROM `usuario` ORDER BY `id`;
SELECT COUNT(*) AS `movimientos_sin_equipo`
FROM `movimiento`
WHERE `id_equipo` IS NULL OR `id_equipo` = 0;
