create or replace view template_view as 
select t.template_id as id, t.short_name, c.description as category, d.acronym as department 
from template t, category c, department d 
where t.category_id = c.category_id and t.department_id = d.department_id
order by d.acronym, t.template_id;
-- need to have 'id' as generic label for view x_view