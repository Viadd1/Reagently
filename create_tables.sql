CREATE TABLE reagents (
    reagent_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    supplier VARCHAR(255),
    catalog_number VARCHAR(100),
    category VARCHAR(100),
    storage_condition VARCHAR(100),
    total_quantity DECIMAL(10, 2),
    available_quantity DECIMAL(10, 2)
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

    lot_number VARCHAR(100),
    current_quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20),

    received_date DATE,
    expiry_date DATE,

    FOREIGN KEY (reagent_id)
        REFERENCES reagents(reagent_id),

    FOREIGN KEY (location_id)
        REFERENCES locations(location_id)
);


CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    lab VARCHAR(100),
    role VARCHAR(100),
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
);


CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    volume DECIMAL(10, 2),
    date DATE,
    user_id INT NOT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
);