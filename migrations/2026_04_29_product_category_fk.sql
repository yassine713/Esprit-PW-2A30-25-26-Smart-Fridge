ALTER TABLE product
  ADD COLUMN category_id INT NULL AFTER image_url;

UPDATE product p
LEFT JOIN (
  SELECT product_id, MIN(category_id) AS category_id
  FROM product_category
  GROUP BY product_id
) pc ON pc.product_id = p.id
SET p.category_id = pc.category_id;

ALTER TABLE product
  ADD CONSTRAINT fk_product_category
  FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL;

DROP TABLE product_category;
