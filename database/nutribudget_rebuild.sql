CREATE DATABASE IF NOT EXISTS nutribudget CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE nutribudget;

CREATE TABLE `user` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  recovery_code VARCHAR(255) DEFAULT NULL,
  failed_login_attempts INT NOT NULL DEFAULT 0,
  locked_until INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE profile (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  weight DECIMAL(8,2) DEFAULT NULL,
  height DECIMAL(8,2) DEFAULT NULL,
  goal VARCHAR(255) DEFAULT NULL,
  disease VARCHAR(255) DEFAULT NULL,
  allergy VARCHAR(255) DEFAULT NULL,
  budget DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE category (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  calories INT NOT NULL DEFAULT 0,
  protein DECIMAL(8,2) NOT NULL DEFAULT 0,
  carbs DECIMAL(8,2) NOT NULL DEFAULT 0,
  fat DECIMAL(8,2) NOT NULL DEFAULT 0,
  price DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(255) DEFAULT NULL,
  category_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exercise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  qr_token VARCHAR(64) DEFAULT NULL UNIQUE,
  youtube_url VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_exercise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  exercise_id INT NOT NULL,
  duration_min INT NOT NULL DEFAULT 0,
  date_done DATE NOT NULL,
  CONSTRAINT fk_user_exercise_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_exercise_exercise FOREIGN KEY (exercise_id) REFERENCES exercise(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE objective (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  exercise_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  target_duration_min INT NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  CONSTRAINT fk_objective_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_objective_exercise FOREIGN KEY (exercise_id) REFERENCES exercise(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE custom_meal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_custom_meal_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE meal_ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meal_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity_g DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_meal_ingredient_meal FOREIGN KEY (meal_id) REFERENCES custom_meal(id) ON DELETE CASCADE,
  CONSTRAINT fk_meal_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity_g DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_user_ingredient_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE support_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  type VARCHAR(80) NOT NULL,
  issue_title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_request_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE support_response (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  admin_id INT NOT NULL,
  message TEXT NOT NULL,
  responded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_response_request FOREIGN KEY (request_id) REFERENCES support_request(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_response_admin FOREIGN KEY (admin_id) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user` (name, email, password, role) VALUES
('Ranim Selmi', 'ranimselmi2005@gmail.com', '$2y$10$8q.Dbb.IMZbKLW7EMjPGGexY.h1YaNwzBnOiaQ0i7eXqbCRk/Mj8m', 'user'),
('Admin', 'admin@nutribudget.test', '$2y$10$8q.Dbb.IMZbKLW7EMjPGGexY.h1YaNwzBnOiaQ0i7eXqbCRk/Mj8m', 'admin');

INSERT INTO category (name) VALUES
('Protein'), ('Carbs'), ('Vegetables'), ('Dairy'), ('Fruit');

INSERT INTO ingredient (name, calories, protein, carbs, fat, price) VALUES
('Chicken Breast', 165, 31, 0, 3.6, 4.50),
('Rice', 130, 2.7, 28, 0.3, 1.20),
('Eggs', 155, 13, 1.1, 11, 2.00),
('Potatoes', 77, 2, 17, 0.1, 1.00),
('Broccoli', 34, 2.8, 7, 0.4, 1.80);

INSERT INTO exercise (name, qr_token, youtube_url) VALUES
('Walking', '1e1768d55606e9f54e363e02739f3d6a', NULL),
('Running', '5eae375c182490edce0e0e3630834b02', NULL),
('Cycling', 'a8029f9caa88e4ed0d4abf1588721e20', 'https://www.youtube.com/watch?v=8LZ5wZgW5lU'),
('Strength Training', '610992b653c425b9e4ca1a28f9d4f07d', NULL),
('Yoga', 'f8cf4f089e021cdfa52473c372449551', 'https://www.youtube.com/watch?v=v7AYKMP6rOE');
