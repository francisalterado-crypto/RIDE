-- College names: small connecting words ("of", "and", ...) in lowercase, e.g. "College of Engineering and Technology"
UPDATE colleges SET name = REPLACE(name, ' Of ', ' of ') WHERE name LIKE BINARY '% Of %';
UPDATE colleges SET name = REPLACE(name, ' And ', ' and ') WHERE name LIKE BINARY '% And %';
UPDATE colleges SET name = REPLACE(name, ' The ', ' the ') WHERE name LIKE BINARY '% The %';
UPDATE colleges SET name = REPLACE(name, ' In ', ' in ') WHERE name LIKE BINARY '% In %';
UPDATE colleges SET name = REPLACE(name, ' For ', ' for ') WHERE name LIKE BINARY '% For %';
