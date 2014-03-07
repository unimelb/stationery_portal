-- add user_department 2014-02-28
CREATE TABLE user_department (
username VARCHAR(20) NOT NULL,
department_id BIGINT UNSIGNED NOT NULL,

FOREIGN KEY (username) REFERENCES user(username) ON DELETE CASCADE,
FOREIGN KEY (department_id) REFERENCES department(department_id) ON DELETE CASCADE
);