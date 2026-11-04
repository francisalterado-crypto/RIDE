-- College names: small connecting words ("of", "and", ...) in lowercase, e.g. "College of Engineering and Technology"
UPDATE colleges SET name = REPLACE(name, ' Of ', ' of ');
UPDATE colleges SET name = REPLACE(name, ' And ', ' and ');
UPDATE colleges SET name = REPLACE(name, ' The ', ' the ');
UPDATE colleges SET name = REPLACE(name, ' In ', ' in ');
UPDATE colleges SET name = REPLACE(name, ' For ', ' for ');
