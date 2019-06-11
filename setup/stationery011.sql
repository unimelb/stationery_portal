-- stationery11.sql
-- department_view
create or replace view department_view as
select department_id as id, name, acronym from department
order by acronym, id;
-- category_view
create or replace view category_view as
select category_id as id, description from category
order by id;
-- revised template_view
create or replace view template_view as
select t.template_id as id, t.short_name, c.description as category, '' as department from template t, category c
where t.category_id = c.category_id
and t.department_id IS NULL
UNION
select t.template_id as id, t.short_name, c.description as category, d.acronym as department 
from template t, category c, department d 
where t.category_id = c.category_id and t.department_id = d.department_id
order by department, id;
-- 
create or replace view template_price_view as
SELECT category_id, quantity, price_AUD FROM template_price
order by quantity;