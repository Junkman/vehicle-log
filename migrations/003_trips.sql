CREATE TABLE IF NOT EXISTS trips (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT UNSIGNED NOT NULL,
 driver_id INT UNSIGNED NOT NULL,
 trip_description VARCHAR(255) NOT NULL DEFAULT '',
 start_odometer INT UNSIGNED NOT NULL,
 start_at DATETIME NOT NULL,
 end_odometer INT UNSIGNED NULL,
 end_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
 FOREIGN KEY (driver_id) REFERENCES drivers(id),
 INDEX (vehicle_id, end_at),
 INDEX (driver_id, start_at)
) ENGINE=InnoDB;
