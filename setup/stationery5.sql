-- add job 2014-04-04
CREATE TABLE job (
job_id SERIAL PRIMARY KEY,
username VARCHAR(20) NOT NULL,
template_id BIGINT UNSIGNED NULL,
chili_id CHAR(36) NULL,
quantity BIGINT UNSIGNED NULL,
themis_code CHAR(27) NULL,
instructions TEXT NULL,
address_id BIGINT UNSIGNED NULL,
ordered DATETIME NULL,
FOREIGN KEY (username) REFERENCES user(username) ON DELETE CASCADE,
FOREIGN KEY (address_id) REFERENCES address(address_id) ON DELETE SET NULL
);
