-- SQL command to fix relationships in the survey_roles table
ALTER TABLE survey_roles
ADD CONSTRAINT fk_survey_id
FOREIGN KEY (survey_id) REFERENCES surveys(id)
ON DELETE CASCADE,
ADD CONSTRAINT fk_role_id
FOREIGN KEY (role_id) REFERENCES roles(id)
ON DELETE CASCADE;