-- Research & Extension Monitoring: three-role workflow (Coordinator > Dean > VPRIDE)

UPDATE roles SET slug = 'vpride', name = 'Admin / VPRIDE', description = 'Monitors all submitters and grants final approval after College Dean approval'
WHERE slug = 'ride_director';

UPDATE roles SET name = 'College Coordinator', description = 'Endorses research and extension submissions from their college and forwards to the College Dean'
WHERE slug = 'college_coordinator';

UPDATE roles SET name = 'College Dean', description = 'Approves endorsed submissions and forwards to VPRIDE for final approval'
WHERE slug = 'dean';

UPDATE proposals SET current_step = 'vpride' WHERE current_step = 'ride_director';
UPDATE proposals SET current_step = 'dean' WHERE current_step = 'ethics_review';
