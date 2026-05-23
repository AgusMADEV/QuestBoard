-- Crea tabla de credenciales para portal admin separado
CREATE TABLE IF NOT EXISTS admin_portal_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario inicial (cambiar contraseña apenas inicies)
INSERT INTO admin_portal_users (username, password_hash, is_active)
SELECT 'owner', '$2y$10$Yu8Y04tBmeASE5qIDQS.9.BkMcIrDA/hGCbTfim3wtjUri3I72PVq', 1
WHERE NOT EXISTS (
    SELECT 1 FROM admin_portal_users WHERE username = 'owner'
);
