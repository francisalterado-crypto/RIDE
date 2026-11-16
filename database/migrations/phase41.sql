-- Remove the 2027 extension projects and planned activities that phase39 seeded from the ESO monitoring
-- sheet; they were sample data. Planned activities go with them (ON DELETE CASCADE) and any proposal or
-- conducted activity linked to one is unlinked (ON DELETE SET NULL). Projects added in RIDE are kept.
DELETE FROM extension_projects WHERE source = '2027 Monitoring Sheet';
