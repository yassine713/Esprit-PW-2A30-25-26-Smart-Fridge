CREATE DATABASE IF NOT EXISTS nutribudget;
USE nutribudget;

CREATE TABLE `user` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user'
);

CREATE TABLE profile (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  weight DECIMAL(8,2) NULL,
  height DECIMAL(8,2) NULL,
  goal VARCHAR(255) NULL,
  disease VARCHAR(255) NULL,
  allergy VARCHAR(255) NULL,
  budget DECIMAL(8,2) NULL,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  calories DECIMAL(8,2) NULL,
  protein DECIMAL(8,2) NULL,
  carbs DECIMAL(8,2) NULL,
  fat DECIMAL(8,2) NULL,
  price DECIMAL(8,2) NULL
);

CREATE TABLE custom_meal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  type VARCHAR(100) NOT NULL,
  CONSTRAINT fk_custom_meal_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE meal_ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meal_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity_g DECIMAL(8,2) NOT NULL,
  CONSTRAINT fk_meal_ingredient_meal FOREIGN KEY (meal_id) REFERENCES custom_meal(id) ON DELETE CASCADE,
  CONSTRAINT fk_meal_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient(id) ON DELETE CASCADE
);

CREATE TABLE user_ingredient (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ingredient_id INT NOT NULL,
  quantity_g DECIMAL(8,2) NOT NULL,
  CONSTRAINT fk_user_ingredient_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_ingredient_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredient(id) ON DELETE CASCADE
);

CREATE TABLE support_request (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  type VARCHAR(50) NOT NULL,
  issue_title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_request_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE support_response (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  admin_id INT NOT NULL,
  message TEXT NOT NULL,
  responded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_response_request FOREIGN KEY (request_id) REFERENCES support_request(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_response_admin FOREIGN KEY (admin_id) REFERENCES `user`(id) ON DELETE CASCADE
);

CREATE TABLE exercise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL
);

CREATE TABLE user_exercise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  exercise_id INT NOT NULL,
  duration_min INT NOT NULL,
  date_done DATE NOT NULL,
  CONSTRAINT fk_user_exercise_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_exercise_exercise FOREIGN KEY (exercise_id) REFERENCES exercise(id) ON DELETE CASCADE
);

CREATE TABLE product (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  stock INT NOT NULL,
  image_url VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE category (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL
);

CREATE TABLE product_category (
  product_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (product_id, category_id),
  CONSTRAINT fk_pc_product FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
);

INSERT INTO `user` (name, email, password, role)
VALUES ('Admin', 'admin@nutribudget.com', '$2y$10$u8Tq3aYk1t3LQmT0rT9Xle/5ihF5yC6wZ2f88t8s1tqP8L7u6M5aW', 'admin');

INSERT INTO exercise (name) VALUES
('Running'),('Cycling'),('Swimming'),('Push-ups'),('Yoga');
