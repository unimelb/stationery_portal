-- stationery015.sql
-- an amalgamation of view definitions from sql 012-014
-- necessary because the view permissions are user-specific
-- revise template_price_view
create or replace view template_price_view as
SELECT category_id, quantity, price_AUD, handling_cost
FROM template_price
ORDER by quantity;
-- customer quantity/price view
create or replace view customer_price_view as
SELECT category_id, quantity, price_AUD + handling_cost as sell_price
from template_price
ORDER BY quantity;
-- printer_view
create or replace view printer_view as
select printer_id as id, name, email from printer
order by id;
-- category_view, revised
-- requires one printer to be defined
create or replace view category_view as
select c.category_id as id, c.description as description, IFNULL((SELECT name from printer where printer_id = c.printer_id),'None') as printer from category c, printer p
where (c.printer_id = p.printer_id) OR c.printer_id IS NULL
order by c.category_id;
