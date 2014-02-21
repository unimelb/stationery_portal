-- setup
  CREATE DATABASE stationery;
  INSERT INTO mysql.user (User,Host,Password) VALUES('chili_user','localhost',PASSWORD('7Fy5Z3aDhP#acgWqkXnT'));
  FLUSH PRIVILEGES;
  GRANT ALL PRIVILEGES ON stationery.* to chili_user@localhost;
  FLUSH PRIVILEGES;