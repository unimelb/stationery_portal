CREATE TABLE permission_group (
group_id SERIAL PRIMARY KEY,
name VARCHAR(50) NOT NULL,
description VARCHAR(50) NULL   
);
INSERT INTO permission_group values(1, 'admin', 'stationery administration access');
CREATE TABLE user_group (
username VARCHAR(20) NOT NULL,
group_id BIGINT UNSIGNED NOT NULL,
FOREIGN KEY (username) REFERENCES user(username) ON DELETE CASCADE,
FOREIGN KEY (group_id) REFERENCES permission_group(group_id) ON DELETE CASCADE
);