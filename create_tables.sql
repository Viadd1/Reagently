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
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64),
    token_expires_at DATETIME
);


CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE project_members (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (project_id, user_id),
    FOREIGN KEY (project_id)
        REFERENCES projects(project_id),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
);


CREATE TABLE reagents (
    reagent_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    catalog_number VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(100),
    unit VARCHAR(20) NOT NULL,
    storage_condition VARCHAR(100),
    is_deleted BOOL NOT NULL DEFAULT 0
);


CREATE TABLE locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    room VARCHAR(100) NOT NULL,
    storage_unit VARCHAR(100),
    shelf VARCHAR(100),
    box_name VARCHAR(100)
);


CREATE TABLE reagent_lots (
    lot_id INT PRIMARY KEY AUTO_INCREMENT,

    reagent_id INT NOT NULL,
    location_id INT NOT NULL,

    supplier VARCHAR(255),
    lot_number VARCHAR(100),
    current_quantity DECIMAL(10,2) NOT NULL CHECK (current_quantity >= 0),
    

    received_date DATE,
    expiry_date DATE,

    FOREIGN KEY (reagent_id)
        REFERENCES reagents(reagent_id),

    FOREIGN KEY (location_id)
        REFERENCES locations(location_id)
);


CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    lot_id INT NOT NULL,
    quantity_reserved DECIMAL(10,2) NOT NULL CHECK (quantity_reserved > 0),
    needed_date DATE NOT NULL,
    status VARCHAR(20)
        NOT NULL
        DEFAULT 'ACTIVE',
    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id, user_id)
        REFERENCES project_members(project_id, user_id),
    FOREIGN KEY (lot_id)
        REFERENCES reagent_lots(lot_id)
);


CREATE TABLE inventory_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    lot_id INT NOT NULL,
    user_id INT NOT NULL,
    reservation_id INT,
    transaction_type VARCHAR(30) NOT NULL,
    quantity_change DECIMAL(10,2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lot_id)
        REFERENCES reagent_lots(lot_id),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id),
    FOREIGN KEY (reservation_id)
        REFERENCES reservations(reservation_id)
);


CREATE TABLE reagent_nicknames (
    nickname_id INT PRIMARY KEY AUTO_INCREMENT,
    reagent_id INT NOT NULL,
    nickname VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (reagent_id, nickname),
    FOREIGN KEY (reagent_id) REFERENCES reagents(reagent_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reagent_id INT,
    reagent_name VARCHAR(255) NOT NULL,
    request_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id),
    FOREIGN KEY (reagent_id)
        REFERENCES reagents(reagent_id)
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);