-- add new entity printer
CREATE TABLE printer (
       printer_id SERIAL PRIMARY KEY,
       name VARCHAR(100) NULL,
       email VARCHAR(90) NULL	
);
-- printer_view
create or replace view printer_view as
select printer_id as id, name, email from printer
order by id;
-- add printer_id as foreign key to category or category_view?
ALTER TABLE category
ADD COLUMN printer_id BIGINT UNSIGNED NULL
AFTER is_active;
ALTER TABLE category
ADD CONSTRAINT
FOREIGN KEY(printer_id) REFERENCES printer(printer_id) ON DELETE SET NULL;
-- category_view, revised
-- requires one printer to be defined
create or replace view category_view as
select c.category_id as id, c.description as description, IFNULL((SELECT name from printer where printer_id = c.printer_id),'None') as printer from category c, printer p
where (c.printer_id = p.printer_id) OR c.printer_id IS NULL
order by c.category_id;
