-- Office titles: Field Extensionist, Research Coordinator, ESO Director, RDO Director
UPDATE roles SET name = 'Research Coordinator',
    description = 'Endorses research submissions from their college and forwards to the College Dean'
    WHERE slug = 'coordinator_research';
UPDATE roles SET name = 'Field Extensionist',
    description = 'Endorses extension submissions from their college and forwards to the College Dean'
    WHERE slug = 'coordinator_extension';
UPDATE roles SET name = 'RDO Director',
    description = 'Approves research submissions after College Dean and forwards to VPRIDE'
    WHERE slug = 'director_research';
UPDATE roles SET name = 'ESO Director',
    description = 'Approves extension submissions after College Dean and forwards to VPRIDE'
    WHERE slug = 'director_extension';
UPDATE roles SET description = 'Approves endorsed submissions and forwards to the RDO Director or ESO Director'
    WHERE slug = 'dean';
UPDATE roles SET description = 'Grants final approval after RDO Director or ESO Director approval'
    WHERE slug = 'vpride';
