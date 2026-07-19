
-- Tables structure
CREATE TABLE  IF NOT EXISTS users  (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  phone varchar(15) NOT NULL,
  role enum('admin','d_agent','customer') NOT NULL DEFAULT customer,
  verified tinyint(1) NOT NULL DEFAULT 0,
  verification_code varchar(50) DEFAULT NULL,
  status ENUM('pending', 'active', 'deactivated') DEFAULT 'pending',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  login_count INT DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Personal Care', 'Essential personal hygiene and care products', '2025-07-05 09:10:48'),
(2, 'Medicare', 'Basic medical and first-aid supplies', '2025-07-05 09:10:48'),
(3, 'Other Essentials', 'Travel safety and comfort items', '2025-07-05 09:10:48');
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;


-- Products table
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,             -- Product ID
  product_name VARCHAR(150) NOT NULL,            -- Name of product
  brand_name VARCHAR(100) NOT NULL,              -- Brand
  price DECIMAL(10,2) NOT NULL,                  -- Price of product
  category_id INT,                               -- FK to categories
  stock INT NOT NULL DEFAULT 0,                  -- Stock count
  image VARCHAR(255) DEFAULT NULL,               -- Product image path
  description TEXT,                              -- Small description
  status ENUM('active', 'inactive') DEFAULT 'active', -- Availability
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
-- Delivery Agents table
CREATE TABLE `delivery_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `agent_image` varchar(255) NOT NULL,
  `driving_license_no` varchar(50) NOT NULL,
  `driving_license_image` varchar(255) NOT NULL,
  `blood_group` varchar(10) NOT NULL,
  `police_clearance_image` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE delivery_agents ADD COLUMN if not EXISTS user_id INT NULL, 
ADD CONSTRAINT fk_delivery_agents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

SELECT id, email FROM users WHERE role='d_agent';




-- Subcategories table
CREATE TABLE subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,              
    category_id INT NOT NULL,                       -- Parent Category
    name VARCHAR(100) NOT NULL,                     -- Subcategory name
    description TEXT DEFAULT NULL,                  -- Optional description
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Created timestamp
    FOREIGN KEY (category_id) REFERENCES categories(id) 
        ON DELETE CASCADE                           -- Delete subcategories if category deleted
);

-- Personal Care
INSERT INTO subcategories (category_id, name, description) VALUES
(1, 'Oral Care', 'Toothpaste, toothbrush, mouthwash'),
(1, 'Skin Care', 'Soaps, face wash, creams, moisturizers'),
(1, 'Hair Care', 'Shampoo, conditioner, oil'),
(1, 'Feminine Hygiene', 'Sanitary pads, tampons, intimate wash'),
(1, 'Deodorants & Perfumes', 'Body sprays, perfumes, roll-ons');

-- Medicare
INSERT INTO subcategories (category_id, name, description) VALUES
(2, 'First Aid', 'Bandages, antiseptics, cotton'),
(2, 'OTC Medicines', 'Paracetamol, pain relief, cold tablets'),
(2, 'Supplements', ' ORS, immunity boosters');

-- Other Essentials
INSERT INTO subcategories (category_id, name, description) VALUES
(3, 'Travel Kits', 'Masks, sanitizers, travel packs'),
(3, 'Comfort Items', 'Neck pillow, eye mask, earplugs'),
(3, 'Safety Items', 'Pepper spray, whistle, personal alarm');




ALTER TABLE products
    ADD COLUMN subcategory_id INT DEFAULT NULL AFTER category_id,
    ADD CONSTRAINT fk_subcategory 
        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) 
        ON DELETE SET NULL;

-- Personal Care: Oral Care
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Colgate Strong Teeth Toothpaste (25g)', 'Colgate', 25.00, 1, 1, 50, 'images/colgatetp.jpg', 'Strengthens teeth with calcium and minerals, compact pack perfect for travel brushing.', 'active'),
('Dabur Red Herbal Toothpaste (25g)', 'Dabur', 20.00, 1, 1, 50, 'images/daburtp.jpg', 'Cavity protection toothpaste in a small pack for on-the-go use.', 'active'),
('Closeup Red Hot Toothpaste (25g)', 'Closeup', 22.00, 1, 1, 50, 'images/closeuptp.jpg', 'Refreshing gel toothpaste for instant fresh breath while traveling.', 'active'),
('Listerine Mouthwash (30ml)', 'Listerine', 50.00, 1, 1, 50, 'images/mouthwash.jpg', 'Travel-size mouthwash for fresh breath and oral hygiene on the go.', 'active'),
('Travel Toothbrush (Foldable)', 'Local', 30.00, 3, 7, 50, 'images/toothbrush.jpg', 'Compact foldable toothbrush for oral hygiene on the go.', 'active');

-- Personal Care: Skin Care
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Lux Soap Bar (25g)', 'Lux', 15.00, 1, 2, 50, 'images/lux.jpg', 'Gentle scented soap, perfect for single-use travel.', 'active'),
('Dove Cream Beauty Soap (25g)', 'Dove', 18.00, 1, 2, 50, 'images/dove.jpg', 'Moisturizing mini soap bar for soft skin on the go.', 'active'),
('Himalaya Neem Face Wash (25ml)', 'Himalaya', 30.00, 1, 2, 50, 'images/himalayafacewash.jpg', 'Herbal face wash that keeps skin clean and refreshed while traveling.', 'active');

-- Personal Care: Hair Care
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Clinic Plus Shampoo (25ml)', 'Clinic Plus', 20.00, 1, 3, 50, 'images/clinicplusshampoo.jpg', 'Small nourishing shampoo pack convenient for travel.', 'active'),
('Parachute Jasmine Hair Oil (25ml)', 'Parachute', 30.00, 1, 3, 50, 'images/jasminehairoil.jpg', 'Jasmine oil in a handy travel bottle.', 'active'),
('Parachute Coconut Oil (25ml)', 'Parachute', 22.00, 1, 3, 50, 'images/parachute.jpg', 'Pure coconut oil in a compact bottle for hair care on the move.', 'active');
-- Personal Care: Feminine Hygiene
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Whisper Ultra Clean Sanitary Pads (2 pcs)', 'Whisper', 25.00, 1, 4, 50, 'images/wisper.jpg', 'Compact sanitary pads for travel convenience.', 'active'),
('Stayfree Sanitary Pads (6 pcs)', 'Stayfree', 45.00, 1, 4, 50, 'images/stayfree.jpg', 'Travel-size sanitary pads for on-the-go comfort.', 'active'),
('Pee Safe Disposable Period Panties (10 pcs)', 'Pee Safe', 150.00, 1, 4, 50, 'images/periodpanty.jpg', 'Leak-proof disposable period panties for comfort and hygiene during travel.', 'active');

-- Personal Care: Deodorants & Perfumes
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Fogg Men Body Spray (25ml)', 'Fogg', 75.00, 1, 5, 50, 'images/pocketperfume.jpg', 'Travel-size deodorant body spray for men with long-lasting fragrance.', 'active'),
('Denver Hamilton Deo (25ml)', 'Denver', 80.00, 1, 5, 50, 'images/pocketperfumemen.jpg', 'Compact deodorant spray for men with a refreshing masculine scent.', 'active'),
('Engage On Women Pocket Perfume (18ml)', 'Engage', 60.00, 1, 5, 50, 'images/pocketperfumewomen.jpg', 'Pocket-size perfume for women with a fresh floral fragrance.', 'active'),
('Eva Deo Spray for Women (25ml)', 'Eva', 65.00, 1, 5, 50, 'images/pocketperfumewoman.jpg', 'Travel-size deodorant spray for women with gentle skin-friendly freshness.', 'active');

-- Medicare: First Aid
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Dettol Antiseptic Liquid (25ml)', 'Dettol', 35.00, 2, 4, 50, 'images/dettol.jpg', 'Multi-purpose antiseptic for cuts, wounds, and hygiene.', 'active'),
('Hansaplast Bandages (5 pcs)', 'Hansaplast', 30.00, 2, 4, 50, 'images/hansaplast.jpg', 'Adhesive bandages for small cuts and scrapes.', 'active');

-- Medicare: OTC Medicines
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Dolo-650 Paracetamol (10 tabs)', 'Dolo', 15.00, 2, 5, 50, 'images/dolo.jpg', 'Trusted paracetamol strip for fever and body pain.', 'active'),
('Vicks Action 500 Cold Relief (10 tabs)', 'Vicks', 20.00, 2, 5, 50, 'images/vickscold.jpg', 'Relief for cold, cough, and headache in travel.', 'active'),
('Vicks Inhaler (0.5ml)', 'Vicks', 45.00, 2, 5, 50, 'images/vicksinhaler.jpg', 'Pocket-sized nasal inhaler for quick relief from nasal congestion during travel.', 'active'),
('Vicks VapoRub (10g)', 'Vicks', 55.00, 2, 5, 50, 'images/vicksvaporub.jpg', 'Classic balm for cold relief, nasal congestion, and headache in a handy small pack.', 'active'),
('Vicks Roll-On (10ml)', 'Vicks', 60.00, 2, 5, 50, 'images/vicksinhalerrollon.jpg', 'Soothing roll-on for headache and cold relief, easy to carry in travel.', 'active');

-- Medicare: Supplements
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Electral ORS Sachet (21.8g)', 'Electral', 12.00, 2, 6, 50, 'images/ors.jpg', 'Oral rehydration salts to prevent dehydration.', 'active'),
('Glucon-D Instant Energy Powder (25g)', 'Glucon-D', 10.00, 2, 6, 50, 'images/glucon.jpg', 'Instant energy drink mix for fatigue recovery.', 'active');

-- Other Essentials: Travel Kits
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Dettol Hand Sanitizer (25ml)', 'Dettol', 20.00, 3, 7, 50, 'images/dettolsanitizer.jpg', 'Kills 99.9% germs, pocket-friendly bottle.', 'active'),
('3-Ply Disposable Mask (10 pc)', 'Local', 5.00, 3, 7, 50, 'images/facemask.jpg', 'Single-use protective face mask for hygiene.', 'active'),
('Lifebuoy Hand Sanitizer (25ml)', 'Lifebuoy', 22.00, 3, 7, 50, 'images/lifebuoysanitizer.jpg', 'Travel-size sanitizer for hand safety.', 'active'),
('Pee Safe Toilet Seat Cover (10 sheets)', 'Pee Safe', 99.00, 3, 7, 50, 'images/toiletcover.jpg', 'Disposable toilet seat covers for hygiene and safety while traveling.', 'active'),
('Paper Soap Strips (25 sheets)', 'Generic', 20.00, 3, 7, 50, 'images/papersoap.jpg', 'Compact paper soap sheets, dissolve instantly in water for easy handwashing while traveling.', 'active');

-- Other Essentials: Comfort Items
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Travel Neck Pillow', 'Local', 150.00, 3, 8, 50, 'images/neckpillow.jpg', 'Ergonomic neck pillow for comfortable travel.', 'active'),
('Sleep Eye Mask', 'Local', 50.00, 3, 8, 50, 'images/sleepeyemask.jpg', 'Soft eye mask to block light for better sleep.', 'active'),
('Wet Wipes Travel Pack (10 sheets)', 'Fresh Ones', 25.00, 3, 8, 50, 'images/wetwipes.jpg', 'Refreshing wet wipes for instant cleansing and freshness during travel.', 'active'),
('Pocket Tissue Pack (10 sheets)', 'Local', 8.00, 3, 8, 50, 'images/pockettissue.jpg', 'Handy tissues for quick cleaning and freshness.', 'active');

-- Other Essentials: Safety Items
INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) VALUES
('Knighthood Pepper Spray (25ml)', 'Knighthood', 150.00, 3, 9, 50, 'images/pepperspray.jpg', 'Compact defense spray for women’s safety.', 'active'),
('Personal Safety Alarm Keychain (~25g)', 'Local', 120.00, 3, 9, 50, 'images/safetyalarm.jpg', 'Small device that emits loud alarm for safety.', 'active');

--feedbacks table
CREATE TABLE feedbacks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'archived') NOT NULL DEFAULT 'new',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE feedbacks
ADD COLUMN user_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE feedbacks
DROP COLUMN name,
DROP COLUMN email;


-- Complaints table
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `complaint_text` text NOT NULL,
  `status` enum('Pending','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL, -- FK to users
    delivery_agent_id INT NULL, -- FK to users (role = d_agent)
    customer_id INT NULL, -- FK to customers (optional)
    pnr_number VARCHAR(20) NOT NULL,
    coach VARCHAR(10) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status ENUM('Pending', 'Confirmed', 'Assigned', 'Out for Delivery', 'Delivered', 'Cancelled') 
        NOT NULL DEFAULT 'Pending',
    payment_status ENUM('Pending', 'Paid', 'Failed') 
        NOT NULL DEFAULT 'Pending',
    payment_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    assigned_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,

    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE orders
ADD COLUMN order_date DATE NOT NULL
AFTER total_amount;


ALTER TABLE orders
ADD COLUMN delivery_agent_id INT NULL AFTER user_id,
ADD CONSTRAINT fk_orders_delivery_agent
    FOREIGN KEY (delivery_agent_id)
    REFERENCES delivery_agents(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
ALTER TABLE orders
ADD COLUMN assigned_at DATETIME NULL AFTER delivery_agent_id;
ALTER TABLE orders
ADD COLUMN delivered_at DATETIME NULL AFTER assigned_at;
ALTER TABLE orders 
MODIFY order_status ENUM('Pending','Assigned','Confirmed','Out for Delivery','Delivered','Cancelled') 
NOT NULL DEFAULT 'Pending';



ALTER TABLE orders
DROP FOREIGN KEY fk_orders_delivery_agent;

ALTER TABLE orders
ADD CONSTRAINT fk_orders_delivery_agent_fk
FOREIGN KEY (delivery_agent_id) REFERENCES delivery_agents(id)
ON DELETE SET NULL
ON UPDATE CASCADE;
ALTER TABLE orders
DROP FOREIGN KEY fk_orders_delivery_agent_fk;

ALTER TABLE orders
ADD CONSTRAINT fk_orders_delivery_agent
FOREIGN KEY (delivery_agent_id) 
REFERENCES users(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Order Items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL, -- FK to orders
    product_id INT NOT NULL, -- FK to products
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL, -- price at purchase time
    subtotal DECIMAL(10, 2) NOT NULL, -- qty * price

    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

-- Trains table 
CREATE TABLE `trains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `train_number` varchar(10) NOT NULL,
  `train_name` varchar(255) NOT NULL,
  `source_name` varchar(100) NOT NULL,
  `source_code` varchar(10) NOT NULL,
  `destination_name` varchar(100) NOT NULL,
  `destination_code` varchar(10) NOT NULL,
  `scheduled_arrival` time NOT NULL,
  `scheduled_departure` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `train_number` (`train_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE trains
  ADD COLUMN platform VARCHAR(10) NULL AFTER scheduled_departure,
  ADD COLUMN avg_delay_minutes INT NULL AFTER platform,
  ADD COLUMN notes VARCHAR(255) NULL AFTER avg_delay_minutes,
  ADD COLUMN source VARCHAR(255) NULL AFTER notes,
  ADD COLUMN last_checked DATE NULL AFTER source;
INSERT INTO trains 
(train_number, train_name, source_name, source_code, destination_name, destination_code, scheduled_arrival, scheduled_departure, platform, avg_delay_minutes, notes, source, last_checked)
VALUES
('16604','MAVELI EXPRESS','Mangalore','MAQ','Trivandrum','TVC','04:55:00','05:00:00','1',NULL,'Scheduled stop at Kannur (platform from live status)','etrain.info / indiarailinfo','2025-09-17'),
('16308','ALLEPPEY EXPRESS','Kannur','CAN','Alleppey','ALLP','05:00:00','05:10:00','2',NULL,'Early morning halt shown on station timetable','etrain.info / goibibo','2025-09-17'),
('16527','YESVANTPUR - KANNUR EXPRESS','Yesvantpur','YPR','Kannur','CAN','09:45:00','09:50:00','3',NULL,'Arrival time at Kannur per timetable listings','prokerala / ixigo','2025-09-17'),
('16511','KSR BENGALURU - KANNUR EXPRESS','Bengaluru','SBC','Kannur','CAN','10:55:00','11:00:00','3',NULL,'Scheduled arrival at Kannur','ixigo / makemytrip','2025-09-17'),
('16305','ERNAKULAM - KANNUR INTERCITY','Ernakulam','ERS','Kannur','CAN','11:45:00','11:50:00','2',NULL,'Intercity arrival time from timetable','prokerala / railyatri','2025-09-17'),
('16512','KANNUR - KSR BENGALURU EXPRESS','Kannur','CAN','Bengaluru','SBC','17:00:00','17:05:00','4',NULL,'Departure time from Kannur','goibibo / easemytrip','2025-09-17'),
('16306','KANNUR - ERNAKULAM EXPRESS','Kannur','CAN','Ernakulam','ERS','14:45:00','14:50:00','4',NULL,'Scheduled departure at Kannur','easemytrip / railyatri','2025-09-17'),
('12081','TVC JAN SHATABDI','Kannur','CAN','Trivandrum','TVC','04:45:00','04:50:00','1',NULL,'Jan Shatabdi timing listed for Kannur','easemytrip / makemytrip','2025-09-17'),
('16607','KANNUR - COIMBATORE EXPRESS','Kannur','CAN','Coimbatore','CBE','05:55:00','06:00:00','2',NULL,'Arrival/stop shown on indiarailinfo','indiarailinfo','2025-09-17'),
('16333','VRL TVC EXPRESS','Veraval','VRL','Trivandrum','TVC','17:07:00','17:10:00','3',NULL,'Arrival/departure snippet from timetable','goibibo / ixigo','2025-09-17'),
('22637','WEST COAST EXPRESS','Chennai','MAS','Mangalore','MAQ',NULL,NULL,'2',NULL,'Halting train at CAN; times vary','ixigo / makemytrip','2025-09-17'),
('12790','MRDW - KCG EXPRESS','Murdeshwar','MRDW','Kacheguda','KCG',NULL,NULL,'4',NULL,'Passing via Kannur','ixigo','2025-09-17'),
('16518','BANGALORE EXPRESS','Bangalore','SBC','Kannur','CAN',NULL,NULL,'3',NULL,'Serving Kannur (schedule pages)','makemytrip / ixigo','2025-09-17'),
('06306','KANNUR - ERNAKULAM JN SPECIAL','Kannur','CAN','Ernakulam Jn','ERS','18:55:00','19:00:00','2',NULL,'Special service entry','makemytrip / railyatri','2025-09-17'),
('56718','TIRUNELVELI - NAGERCOIL PASSENGER','Tirunelveli','TEN','Nagercoil','NCJ','19:55:00','20:00:00','4',NULL,'Local passenger service','prokerala / railyatri','2025-09-17');
UPDATE trains SET avg_delay_minutes = 15 WHERE train_number = '16604'; -- MAVELI EXPRESS
UPDATE trains SET avg_delay_minutes = 12 WHERE train_number = '16308'; -- ALLEPPEY EXPRESS
UPDATE trains SET avg_delay_minutes = 18 WHERE train_number = '16527'; -- YESVANTPUR - KANNUR EXPRESS
UPDATE trains SET avg_delay_minutes = 20 WHERE train_number = '16511'; -- SBC - KANNUR EXPRESS
UPDATE trains SET avg_delay_minutes = 8  WHERE train_number = '16305'; -- ERS - KANNUR INTERCITY
UPDATE trains SET avg_delay_minutes = 22 WHERE train_number = '16512'; -- KANNUR - SBC EXPRESS
UPDATE trains SET avg_delay_minutes = 10 WHERE train_number = '16306'; -- KANNUR - ERS EXPRESS
UPDATE trains SET avg_delay_minutes = 7  WHERE train_number = '12081'; -- TVC JAN SHATABDI
UPDATE trains SET avg_delay_minutes = 14 WHERE train_number = '16607'; -- KANNUR - COIMBATORE EXPRESS
UPDATE trains SET avg_delay_minutes = 16 WHERE train_number = '16333'; -- VRL TVC EXPRESS
UPDATE trains SET avg_delay_minutes = 19 WHERE train_number = '22637'; -- WEST COAST EXPRESS
UPDATE trains SET avg_delay_minutes = 15 WHERE train_number = '12790'; -- MRDW - KCG EXPRESS
UPDATE trains SET avg_delay_minutes = 13 WHERE train_number = '16518'; -- BANGALORE EXPRESS
UPDATE trains SET avg_delay_minutes = 17 WHERE train_number = '06306'; -- KANNUR - ERNAKULAM SPECIAL
UPDATE trains SET avg_delay_minutes = 20 WHERE train_number = '56718'; -- TIRUNELVELI - NAGERCOIL PASS
DELETE FROM trains 
WHERE train_number IN (12790, 16518, 56718);
ALTER TABLE trains
DROP COLUMN source,
DROP COLUMN notes;
INSERT INTO trains 
(train_number, train_name, source_name, source_code, destination_name, destination_code, scheduled_arrival, scheduled_departure, platform, avg_delay_minutes, last_checked)
VALUES
(16346, 'Netravati Express', 'Lokmanya Tilak Terminus', 'LTT', 'Thiruvananthapuram Central', 'TVC', '08:10:00', '08:15:00', 2, 15, NOW()),
(16649, 'Parasuram Express', 'Mangalore Central', 'MAQ', 'Nagercoil Junction', 'NCJ', '10:25:00', '10:30:00', 1, 10, NOW()),
(12685, 'Chennai – Mangalore SF Express', 'Chennai Central', 'MAS', 'Mangalore Central', 'MAQ', '05:20:00', '05:25:00', 1, 20, NOW());
SELECT train_number, train_name, source_name, source_code, destination_name, destination_code, scheduled_arrival, scheduled_departure, platform, avg_delay_minutes, last_checked
FROM trains
ORDER BY train_number;

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL COMMENT 'Rating from 1 to 5',
  `review_text` text DEFAULT NULL,
  `admin_reply_text` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_rating` (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`rating` >= 1 and `rating` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

--cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
ALTER TABLE cart ADD COLUMN status ENUM('cart','wishlist') DEFAULT 'cart';

ALTER TABLE orders 
ADD CONSTRAINT fk_orders_to_users_link 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;
ALTER TABLE orders DROP FOREIGN KEY `orders_ibfk_1`;

ALTER TABLE orders DROP COLUMN user_id;
DELETE FROM orders 
WHERE user_id NOT IN (SELECT id FROM users);
ALTER TABLE orders ADD CONSTRAINT fk_orders_user_final 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;

CREATE TABLE `refunds` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `refund_amount` DECIMAL(10,2) NOT NULL,
  `refund_type` ENUM('User Cancelled','Agent Cancelled') NOT NULL,
  `refund_reason` VARCHAR(255) DEFAULT NULL,
  `refund_status` ENUM('Pending','Processed','Rejected') DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_refunds_order` (`order_id`),
  CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
