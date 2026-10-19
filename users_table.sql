CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    firstname VARCHAR(100) DEFAULT NULL,
    lastname VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) NOT NULL,
    username VARCHAR(100) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    ended_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY sessions_token_hash_unique (session_token_hash),
    KEY sessions_user_active_index (user_id, ended_at, expires_at),
    CONSTRAINT sessions_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    loan_id INT DEFAULT NULL,
    action VARCHAR(20) NOT NULL,
    reference_no VARCHAR(100) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY transactions_user_created_index (user_id, created_at),
    KEY transactions_loan_index (loan_id),
    CONSTRAINT transactions_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
