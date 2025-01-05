-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS artikel_slike;
DROP TABLE IF EXISTS artikel_ocene;
DROP TABLE IF EXISTS postavka_narocila;
DROP TABLE IF EXISTS narocilo_artikel;
DROP TABLE IF EXISTS narocilo;
DROP TABLE IF EXISTS artikel;
DROP TABLE IF EXISTS stranka;
DROP TABLE IF EXISTS prodajalec;
DROP TABLE IF EXISTS administrator;
DROP TABLE IF EXISTS kosarica;
SET FOREIGN_KEY_CHECKS = 1;

-- Create database
CREATE DATABASE IF NOT EXISTS eprodajalna CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eprodajalna;

-- Create table for administrators
CREATE TABLE administrator (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ime VARCHAR(50) NOT NULL,
    priimek VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    geslo VARCHAR(255) NOT NULL,
    cert_subject_dn VARCHAR(255) UNIQUE,
    aktiven BOOLEAN DEFAULT TRUE,
    cert_serial VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for sellers (prodajalec)
CREATE TABLE prodajalec (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ime VARCHAR(50) NOT NULL,
    priimek VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    geslo VARCHAR(255) NOT NULL,
    aktiven BOOLEAN DEFAULT TRUE,
    cert_serial VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for customers (stranka)
CREATE TABLE stranka (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ime VARCHAR(50) NOT NULL,
    priimek VARCHAR(50) NOT NULL,
    ulica VARCHAR(100) NOT NULL,
    hisna_stevilka VARCHAR(10) NOT NULL,
    posta VARCHAR(50) NOT NULL,
    postna_stevilka VARCHAR(4) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    geslo VARCHAR(255) NOT NULL,
    aktiven BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for articles (artikel)
CREATE TABLE artikel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    naziv VARCHAR(100) NOT NULL,
    opis TEXT,
    cena DECIMAL(10, 2) NOT NULL,
    glavna_slika VARCHAR(255) NULL,
    aktiven BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for article images
CREATE TABLE artikel_slike (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artikel_id INT NOT NULL,
    pot VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE
);

-- Table for article ratings
CREATE TABLE artikel_ocene (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artikel_id INT NOT NULL,
    stranka_id INT NOT NULL,
    ocena INT NOT NULL CHECK (ocena BETWEEN 1 AND 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id) ON DELETE CASCADE,
    FOREIGN KEY (stranka_id) REFERENCES stranka(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE narocilo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stranka_id INT NOT NULL,
    prodajalec_id INT,
    status ENUM('oddano', 'potrjeno', 'preklicano', 'stornirano') NOT NULL DEFAULT 'oddano',
    datum_oddaje TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datum_spremembe TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    skupna_cena DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (stranka_id) REFERENCES stranka(id),
    FOREIGN KEY (prodajalec_id) REFERENCES prodajalec(id)
);

-- Create order items table
CREATE TABLE narocilo_artikel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    narocilo_id INT NOT NULL,
    artikel_id INT NOT NULL,
    kolicina INT NOT NULL,
    cena_na_kos DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (narocilo_id) REFERENCES narocilo(id) ON DELETE CASCADE,
    FOREIGN KEY (artikel_id) REFERENCES artikel(id)
);

-- Create shopping cart table
CREATE TABLE kosarica (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stranka_id INT NOT NULL,
    artikel_id INT NOT NULL,
    kolicina INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stranka_id) REFERENCES stranka(id),
    FOREIGN KEY (artikel_id) REFERENCES artikel(id)
);

-- Dodaj FULLTEXT indeks za iskanje
ALTER TABLE artikel ADD FULLTEXT(naziv, opis);

-- Add email validation triggers
DELIMITER //

CREATE TRIGGER check_admin_email_before_insert 
BEFORE INSERT ON administrator
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for administrator';
    END IF;
END//

CREATE TRIGGER check_admin_email_before_update
BEFORE UPDATE ON administrator
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for administrator';
    END IF;
END//

CREATE TRIGGER check_prodajalec_email_before_insert 
BEFORE INSERT ON prodajalec
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for seller';
    END IF;
END//

CREATE TRIGGER check_prodajalec_email_before_update
BEFORE UPDATE ON prodajalec
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for seller';
    END IF;
END//

CREATE TRIGGER check_stranka_email_before_insert 
BEFORE INSERT ON stranka
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for customer';
    END IF;
    IF NEW.postna_stevilka NOT REGEXP '^[0-9]{4}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid postal code format';
    END IF;
END//

CREATE TRIGGER check_stranka_email_before_update
BEFORE UPDATE ON stranka
FOR EACH ROW
BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format for customer';
    END IF;
    IF NEW.postna_stevilka NOT REGEXP '^[0-9]{4}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid postal code format';
    END IF;
END//

DELIMITER ;

-- Insert sample data
-- Administrator
INSERT INTO administrator (ime, priimek, email, geslo, cert_serial) VALUES
('Admin', 'Administrator', 'admin@eprodajalna.si', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '33CDB97124B7647C'); -- geslo: password

-- Sellers
INSERT INTO prodajalec (ime, priimek, email, geslo, cert_serial) VALUES
('Janez', 'Prodajalec', 'janez@eprodajalna.si', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0C365D737CE4AD61'), -- geslo: password
('Marija', 'Trgovec', 'marija@eprodajalna.si', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '25E92830DDDB9F3B'); -- geslo: password

-- Customers
INSERT INTO stranka (ime, priimek, ulica, hisna_stevilka, posta, postna_stevilka, email, geslo) VALUES
('Miha', 'Kupec', 'Slovenska cesta', '1', 'Ljubljana', '1000', 'miha@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- geslo: password
('Ana', 'Novak', 'Mariborska ulica', '15', 'Maribor', '2000', 'ana@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- geslo: password

-- Products
INSERT INTO artikel (naziv, opis, cena, glavna_slika) VALUES
('Grafična kartica RTX 3060', 'NVIDIA GeForce RTX 3060 12GB GDDR6', 399.99, 'nvidiartx3060.jpg'),
('Procesor AMD Ryzen 5 5600X', '6-jedrni procesor, 3.7GHz (4.6GHz boost)', 299.99, 'amdRyzen55600x.webp'),
('Monitor Dell S2721DGF', '27" 1440p 165Hz Gaming Monitor', 449.99, 'dells2721dgf.jpeg'),
('Tipkovnica Logitech G Pro X', 'Mehanska gaming tipkovnica', 129.99, 'logitechTipkovnica.jpg'),
('Miška Logitech G Pro X Superlight', 'Brezžična gaming miška', 149.99, 'gproxSuperlight.jpg'),
('SSD Samsung 970 EVO Plus', '1TB NVMe M.2 SSD', 159.99, 'SSD970.jpeg'),
('RAM Corsair Vengeance', '32GB (2x16GB) DDR4-3600', 179.99, 'RAM.png'),
('Ohišje NZXT H510', 'ATX Mid Tower Gaming ohišje', 89.99, 'NZXT.png'),
('Napajalnik Corsair RM750x', '750W 80+ Gold modularni', 129.99, 'napajalnikCorsair.webp'),
('Matična plošča ASUS ROG B550-F', 'AMD B550 ATX Gaming', 189.99, 'ASUSmaticna.jpg');


