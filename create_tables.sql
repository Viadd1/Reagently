-- Company records and status (PENDING / ACTIVE / SUSPENDED)
CREATE TABLE companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    lab VARCHAR(100),
    firstname VARCHAR(100),
    lastname VARCHAR(100),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved BOOL DEFAULT 0,
    company_id INT DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64),
    token_expires_at DATETIME,

    INDEX idx_users_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    company_id INT NOT NULL,

    INDEX idx_projects_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


CREATE TABLE project_members (
    project_id INT NOT NULL,
    user_id INT NOT NULL,

    PRIMARY KEY (project_id, user_id),
    INDEX idx_project_members_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;


CREATE TABLE reagents (
    reagent_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    catalog_number VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    unit VARCHAR(20) NOT NULL,
    storage_condition VARCHAR(100),
    is_deleted BOOL NOT NULL DEFAULT 0,
    company_id INT NOT NULL,

    UNIQUE (company_id, catalog_number),
    INDEX idx_reagents_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


CREATE TABLE locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    room VARCHAR(100) NOT NULL,
    storage_unit VARCHAR(100),
    shelf VARCHAR(100),
    box_name VARCHAR(100),
    company_id INT NOT NULL,

    INDEX idx_locations_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


CREATE TABLE reagent_lots (
    lot_id INT PRIMARY KEY AUTO_INCREMENT,
    reagent_id INT NOT NULL,
    location_id INT NOT NULL,
    supplier VARCHAR(255),
    lot_number VARCHAR(100),
    current_quantity DECIMAL(10,2) NOT NULL,
    received_date DATE,
    expiry_date DATE,

    INDEX idx_reagent_lots_reagent (reagent_id),
    INDEX idx_reagent_lots_location (location_id),
    FOREIGN KEY (reagent_id) REFERENCES reagents(reagent_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
) ENGINE=InnoDB;


CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    lot_id INT NOT NULL,
    quantity_reserved DECIMAL(10,2) NOT NULL,
    needed_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reservations_membership (project_id, user_id),
    INDEX idx_reservations_lot (lot_id),
    FOREIGN KEY (project_id, user_id)
        REFERENCES project_members(project_id, user_id),
    FOREIGN KEY (lot_id) REFERENCES reagent_lots(lot_id)
) ENGINE=InnoDB;


CREATE TABLE inventory_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    lot_id INT NOT NULL,
    user_id INT NOT NULL,
    reservation_id INT,
    transaction_type VARCHAR(30) NOT NULL,
    quantity_change DECIMAL(10,2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_transactions_lot (lot_id),
    INDEX idx_transactions_user (user_id),
    INDEX idx_transactions_reservation (reservation_id),
    FOREIGN KEY (lot_id) REFERENCES reagent_lots(lot_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
) ENGINE=InnoDB;


CREATE TABLE reagent_nicknames (
    nickname_id INT PRIMARY KEY AUTO_INCREMENT,
    reagent_id INT NOT NULL,
    nickname VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (reagent_id, nickname),
    INDEX idx_reagent_nicknames_creator (created_by),
    FOREIGN KEY (reagent_id) REFERENCES reagents(reagent_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;


CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    company_id INT NOT NULL,

    INDEX idx_announcements_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;


CREATE TABLE order_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reagent_id INT,
    reagent_name VARCHAR(255) NOT NULL,
    request_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_orders_user (user_id),
    INDEX idx_orders_reagent (reagent_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reagent_id) REFERENCES reagents(reagent_id)
) ENGINE=InnoDB;


-- Included for the application's forgot/reset-password workflow.
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_resets_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;