-- stationery12.sql
ALTER TABLE template_price
ADD COLUMN handling_cost DECIMAL(10, 4) DEFAULT 10
AFTER price_AUD;
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