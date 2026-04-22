SET FOREIGN_KEY_CHECKS = 0;
-- switch out the tables for the ones you need to remove/truncate for testing
TRUNCATE TABLE Language;
TRUNCATE TABLE ProductionCompany;
TRUNCATE TABLE User;

SET FOREIGN_KEY_CHECKS = 1;