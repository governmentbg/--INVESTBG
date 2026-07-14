INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('payments', 'payments', 1, '\zni\modules\payments\PaymentModule', 9, NULL);


INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('contractsReports', 'contractsReports', 1, '\zni\modules\reportsAll\contracts\ReportsContractsModule', 20, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('certificatesReports', 'certificatesReports', 1, '\zni\modules\reportsAll\certificates\ReportsCertificatesModule', 21, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('economicActivitiesReports', 'economicActivitiesReports', 1, '\zni\modules\reportsAll\economicActivities\ReportsEconomicActivitiesModule', 21, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('amountsPaidToInvestors', 'amountsPaidToInvestors', 1, '\zni\modules\reportsAll\amountsPaidToInvestors\ReportsAmountsPaidToInvestorsModule', 23, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('contractBanks', 'contractBanks', 1, '\zni\modules\reportsAll\contractBanks\ReportsContractBanksModule', 24, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('contractOrders', 'contractOrders', 1, '\zni\modules\reportsAll\contractOrders\ReportsContractOrdersModule', 25, NULL);
INSERT INTO modules ("name", slug, loaded, classname, pos, settings) VALUES('paymentRequest', 'paymentRequest', 1, '\zni\modules\reportsAll\paymentRequest\ReportsPaymentRequestModule', 26, NULL);

INSERT INTO users (usr, "name", mail, tfa, disabled, avatar, avatar_data, "data", push, sessions) VALUES(2, 'ИО', 'adminio@ltd.com', 0, 0, NULL, NULL, '[]', NULL, '{"b9a2a9db3fe42bfe6f67d1a5d81d35b3":{"cr":1769434622,"up":1769434622,"ip":"10.240.11.192","ua":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/143.0.0.0 Safari\/537.36"}}');
INSERT INTO user_providers (usrprov, provider, id, usr, "name", "data", created, used, disabled, details) VALUES(2, 'PasswordDatabase', 'admin_io', 2, '', '$2y$10$zjAppn/xPBkw1Xmx5e8LieKP3xqqII2qaaGKThQCjuPtFqeCgU0r.', '2026-01-26 15:37:02.000', '2026-01-26 15:37:02.000', 0, NULL);
INSERT INTO user_groups (usr, grp, main, created) VALUES(2, 1, 1, '2026-01-26 15:35:09.000');
SELECT setval(
    'users_usr_seq',
    (SELECT MAX(usr) FROM users)
);

SELECT setval(
    'user_providers_usrprov_seq',
    (SELECT MAX(usrprov) FROM user_providers)
);

