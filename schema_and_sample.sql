-- Create database
CREATE DATABASE IF NOT EXISTS animal_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE animal_tracker;


-- Areas table
CREATE TABLE IF NOT EXISTS areas (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(150) NOT NULL,
description TEXT DEFAULT NULL
);


-- Animals table
CREATE TABLE IF NOT EXISTS animals (
id INT AUTO_INCREMENT PRIMARY KEY,
area_id INT NOT NULL,
common_name VARCHAR(150) NOT NULL,
species VARCHAR(150) DEFAULT NULL,
count_est INT DEFAULT 0,
average_age_years DECIMAL(4,1) DEFAULT NULL,
notes TEXT DEFAULT NULL,
last_seen DATE DEFAULT NULL,
FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE
);


-- Sample areas
INSERT INTO areas (name, description) VALUES
('Forest Edge', 'North-east forest fringe'),
('River Bank', 'Along the main river'),
('Grassland Plain', 'Open grasslands near village');


-- Sample animals
INSERT INTO animals (area_id, common_name, species, count_est, average_age_years, notes, last_seen) VALUES
(1, 'Spotted Deer', 'Axis axis', 23, 4.2, 'Often seen in early morning', '2025-10-20'),
(1, 'Indian Hare', 'Lepus nigricollis', 45, 2.1, 'Small groups', '2025-09-30'),
(2, 'River Otter', 'Lutra lutra', 7, 3.5, 'Occasional sightings', '2025-08-12'),
(2, 'Kingfisher', 'Alcedo atthis', 12, 1.3, 'Near fishing spots', '2025-10-28'),
(3, 'Grey Partridge', 'Perdix perdix', 30, 2.8, '', '2025-07-05');