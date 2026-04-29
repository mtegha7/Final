
-- ICONICS DATABASE SCHEMA


-- DROP & CREATE DATABASE (optional for reset)
DROP DATABASE IF EXISTS iconics;
CREATE DATABASE iconics;
USE iconics;


-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','agent','client') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- AGENT PROFILES
CREATE TABLE agent_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    national_id_path TEXT,
    selfie_path TEXT,
    verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    trust_score DECIMAL(3,1) DEFAULT 0.0,
    avg_rating DECIMAL(2,1) DEFAULT 0.0,
    total_reviews INT DEFAULT 0,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- PROPERTIES
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    title VARCHAR(255),
    description TEXT,
    city VARCHAR(100),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    price DECIMAL(12,2),
    property_type VARCHAR(50),
    status ENUM('pending','approved','blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- PROPERTY IMAGES
CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    image_path TEXT,
    image_hash VARCHAR(255),

    FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE
);


-- REVIEWS
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    client_id INT,
    rating INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (client_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- FRAUD LOGS
CREATE TABLE fraud_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT,
    agent_id INT,
    type VARCHAR(50), -- price, gps, duplicate_image
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE,

    FOREIGN KEY (agent_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- MARKET RATES (PRICE ANALYSIS)
CREATE TABLE zone_market_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100),
    property_type VARCHAR(50),
    avg_price DECIMAL(12,2)
);



-- BLANTYRE ZONES (GPS VALIDATION)
CREATE TABLE blantyre_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7)
);


-- INDEXES (PERFORMANCE BOOST)
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_properties_city ON properties(city);
CREATE INDEX idx_properties_agent ON properties(agent_id);
CREATE INDEX idx_reviews_agent ON reviews(agent_id);



-- UPDATE STATEMENTS
ALTER TABLE properties
ADD COLUMN area_name VARCHAR(100);

UPDATE properties SET area_name = city;

ALTER TABLE properties
DROP COLUMN city;

CREATE INDEX idx_area_name ON properties(area_name);


-- agent_profiles update
ALTER TABLE agent_profiles 
ADD COLUMN verification_confidence DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN risk_level ENUM('low','medium','high') DEFAULT 'low';

ALTER TABLE agent_profiles 
MODIFY verification_status ENUM('pending','verified','pending_review') DEFAULT 'pending';

ALTER TABLE agent_profiles 
MODIFY verification_status ENUM('not_submitted','pending_review','verified') DEFAULT 'not_submitted';


ALTER TABLE fraud_logs 
ADD CONSTRAINT fk_fraud_property
FOREIGN KEY (property_id) REFERENCES properties(id)
ON DELETE CASCADE;

-- SAMPLE DATA (OPTIONAL FOR TESTING)


INSERT INTO blantyre_zones (area_name, latitude, longitude) VALUES
('Blantyre CBD', -15.7861, 35.0058),
('Namiwawa', -15.7800, 35.0150),
('Nyambadwe', -15.8000, 35.0200),
('Chinyonga', -15.7700, 35.0000),
('Chapima Heights', -15.8100, 35.0300),
('Chitawira', -15.8200, 35.0400),
('New Naperi', -15.7750, 35.0100),
('Mpemba', -15.9000, 34.9500),
('Chirimba', -15.8400, 35.0600),
('Chileka', -15.6800, 34.9700);