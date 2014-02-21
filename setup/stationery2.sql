  -- unimelb user name
    CREATE TABLE user (
           username VARCHAR(20) PRIMARY KEY,
           given_name VARCHAR(50) NOT NULL,
           family_name VARCHAR(50) NOT NULL,
           phone VARCHAR(15) NULL,
           email VARCHAR(90) NULL,
           position_name VARCHAR(100) NULL 
    );
    CREATE TABLE department (
           department_id SERIAL PRIMARY KEY,
           name VARCHAR(100) NULL,
acronym VARCHAR(20) NULL
    );
    CREATE TABLE category (
           category_id SERIAL PRIMARY KEY,
           description TEXT
    );
    -- 2014-01-24
    CREATE TABLE address (
            address_id SERIAL PRIMARY KEY,
            addressee VARCHAR(100) NULL,
            location VARCHAR(100) NULL,
            -- eg. office, level etc.
            street_number VARCHAR(20) NULL,
            street VARCHAR(100) NULL,
            town VARCHAR(100) NULL,
            postcode VARCHAR(16) NULL,
            country_code VARCHAR(3) DEFAULT 'au' NULL 
            -- default 'au'
    );