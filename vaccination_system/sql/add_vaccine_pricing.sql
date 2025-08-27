-- Add government_price column to vaccines table
ALTER TABLE vaccines ADD COLUMN government_price DECIMAL(10,2) DEFAULT 0.00 AFTER manufacturer;

-- Add hospital_price column to hospital_vaccines table
ALTER TABLE hospital_vaccines ADD COLUMN hospital_price DECIMAL(10,2) DEFAULT 0.00 AFTER quantity;

-- Update existing vaccines with sample government prices
UPDATE vaccines SET government_price = 
    CASE 
        WHEN name LIKE '%BCG%' THEN 50.00
        WHEN name LIKE '%OPV%' THEN 25.00
        WHEN name LIKE '%Hepatitis%' THEN 75.00
        WHEN name LIKE '%Pentavalent%' THEN 100.00
        WHEN name LIKE '%Measles%' THEN 60.00
        WHEN name LIKE '%DPT%' THEN 80.00
        WHEN name LIKE '%Vitamin%' THEN 20.00
        ELSE 50.00
    END;

-- Set hospital prices to match government prices initially
UPDATE hospital_vaccines hv
JOIN vaccines v ON hv.vaccine_id = v.id
SET hv.hospital_price = v.government_price;

-- Create vaccine_price_history table to track price changes
CREATE TABLE IF NOT EXISTS vaccine_price_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    old_price DECIMAL(10,2) NOT NULL,
    new_price DECIMAL(10,2) NOT NULL,
    changed_by VARCHAR(100) NOT NULL,
    change_reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE
);
