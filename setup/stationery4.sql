-- add template 2014-03-21
CREATE TABLE template (
template_id SERIAL PRIMARY KEY,
chili_id CHAR(36) NOT NULL,
short_name VARCHAR(40) NULL,
full_name VARCHAR(100) NULL,
category_id BIGINT UNSIGNED NULL,
department_id BIGINT UNSIGNED NULL,

FOREIGN KEY(category_id) REFERENCES category(category_id) ON DELETE SET NULL,
FOREIGN KEY(department_id) REFERENCES department(department_id) ON DELETE SET NULL
);
CREATE TABLE template_price (
template_id BIGINT UNSIGNED NOT NULL,
quantity BIGINT UNSIGNED NOT NULL,
price_AUD NUMERIC(10,4) NOT NULL,    

FOREIGN KEY(template_id) REFERENCES template(template_id) ON DELETE CASCADE
);