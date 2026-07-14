INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('companies', 'companies', 1, '\zni\modules\companies\CompaniesModule', 1, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('contracts', 'contracts', 1, '\zni\modules\contracts\ContractsModule', 2, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('reports', 'reports', 1, '\zni\modules\reports\ReportsModule', 3, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('employees', 'employees', 1, '\zni\modules\employees\EmployeesModule', 4, NULL);


INSERT INTO grps (grp, "name", created) VALUES(3, 'Инвеститор АДМИН', '2025-10-27 21:15:33.000');
INSERT INTO grps (grp, "name", created) VALUES(4, 'Администратор МИР', '2025-10-27 21:14:22.000');
INSERT INTO grps (grp, "name", created) VALUES(5, 'Проверяващ МИР', '2025-10-27 21:14:22.000');
INSERT INTO grps (grp, "name", created) VALUES(6, 'Отговорник МИР', '2025-10-27 21:14:22.000');
INSERT INTO grps (grp, "name", created) VALUES(7, 'Мастър МИР', '2025-10-27 21:14:22.000');
INSERT INTO grps (grp, "name", created) VALUES(8, 'Проверяващ МИР - договори', '2025-10-27 21:14:22.000');

SELECT setval('grps_grp_seq', (SELECT MAX(grp) FROM grps));

