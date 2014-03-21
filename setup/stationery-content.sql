-- department
INSERT into department VALUES(DEFAULT,'Faculty of Architecture, Building and Planning','ABP');
INSERT into department VALUES(DEFAULT,'Faculty of Arts',NULL);
INSERT into department VALUES(DEFAULT,'Faculty of Business and Economics',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Graduate School of Education',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne School of Engineering',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne School of Land and Environment',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Law School',NULL);
INSERT into department VALUES(DEFAULT,'Faculty of Medicine, Dentistry and Health Sciences',NULL);
INSERT into department VALUES(DEFAULT,'Faculty of Science',NULL);
INSERT into department VALUES(DEFAULT,'Faculty of Veterinary Science',NULL);
INSERT into department VALUES(DEFAULT,'Faculty of Victorian College of the Arts and Melbourne Conservatorium of Music',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Energy',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Materials',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Sustainable Society',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Neuroscience',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Broadband Enabled Society',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Research Institute - Social Equity Institute',NULL);
INSERT into department VALUES(DEFAULT,'Melbourne Institute of Applied Economic and Social Research',NULL);
INSERT into department VALUES(DEFAULT,'Bio21 Molecular Science and Biotechnology Institute',NULL);
INSERT into department VALUES(DEFAULT,'The Nossal Institute for Global Health',NULL);
INSERT into department VALUES(DEFAULT,'Centre for the Study of Higher Education','CSHE');
-- <2014-03-21 Fri>
-- categories
INSERT into category VALUES(DEFAULT, 'Business Card');
INSERT into category VALUES(DEFAULT, 'Letterhead');
INSERT into category VALUES(DEFAULT, 'With Compliments');
-- templates
INSERT into template VALUES(DEFAULT, 
'a0fab416-cd5f-4240-91a1-500649f63f41', 
'01.UoM',
'01.UoM_BC_SS',
1,
NULL);
INSERT into template_price VALUES(1,200,110.0000);
INSERT into template_price VALUES(1,400,150.0000);
INSERT into template_price VALUES(1,600,190.0000);
INSERT into template_price VALUES(1,800,225.0000);
INSERT into template_price VALUES(1,1000,265.0000);
INSERT into template_price VALUES(1,1200,305.0000);
INSERT into template_price VALUES(1,1400,340.0000);