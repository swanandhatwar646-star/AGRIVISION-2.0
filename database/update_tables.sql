-- Create missing tables for AGRIVISION
USE agrivision_db;

-- Create sensor_readings table
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    soil_moisture DECIMAL(5, 2),
    temperature DECIMAL(5, 2),
    humidity DECIMAL(5, 2),
    ph_level DECIMAL(4, 2),
    nutrient_level DECIMAL(5, 2),
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES field_zones(id) ON DELETE CASCADE
);

-- Create scientific_reports table
CREATE TABLE IF NOT EXISTS scientific_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50),
    summary TEXT,
    content TEXT,
    author VARCHAR(100),
    file_path VARCHAR(255),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create farmer_reports table
CREATE TABLE IF NOT EXISTS farmer_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_id INT,
    report_type VARCHAR(50),
    date_range VARCHAR(20),
    report_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL
);

-- Add name column to field_zones if it doesn't exist
ALTER TABLE field_zones ADD COLUMN IF NOT EXISTS name VARCHAR(50) NOT NULL AFTER zone_name;
