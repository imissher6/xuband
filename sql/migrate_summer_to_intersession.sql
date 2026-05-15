-- Migration: rename "Summer" term to "Intersession" in scholarship_terms
-- Also update the ENUM to include Intersession and remove Summer
ALTER TABLE scholarship_terms 
  MODIFY COLUMN term ENUM('1st Semester','2nd Semester','Summer','Intersession') NOT NULL;

UPDATE scholarship_terms SET term = 'Intersession' WHERE term = 'Summer';

ALTER TABLE scholarship_terms 
  MODIFY COLUMN term ENUM('1st Semester','2nd Semester','Intersession') NOT NULL;
