-- Research and extension centers as units next to the colleges, with their cluster memberships.
-- Source: RIDE organizational chart (RDO Research Centers, pages 3, 5 and 8; ESO Internal, page 9).
-- unit_type tells colleges and centers apart; office is the RIDE office a center reports to (RDO or TTPO).

ALTER TABLE colleges ADD COLUMN IF NOT EXISTS unit_type VARCHAR(20) NOT NULL DEFAULT 'college';

ALTER TABLE colleges ADD COLUMN IF NOT EXISTS office VARCHAR(20) NULL;

ALTER TABLE clusters ADD COLUMN IF NOT EXISTS leader_name VARCHAR(150) NULL;

ALTER TABLE clusters ADD COLUMN IF NOT EXISTS notes TEXT NULL;

INSERT INTO colleges (code, name, unit_type, office) SELECT 'ATBI', 'Agri-Aqua Technology Business Incubation (ATBI) Center', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'ATBI');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'ICEN-AQUA', 'Inland Center for Aquaculture R&D (ICEN-AQUA)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code IN ('ICEN-AQUA', 'ICENAQUA'));
INSERT INTO colleges (code, name, unit_type, office) SELECT 'ABBA', 'Aquatic, Biodiversity, Biotechnology and Aquaculture (ABBA) R&D Center', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'ABBA');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'TBTI', 'Palawan Too-Big-To-Ignore (TBTI Hub)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'TBTI');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'GREAT-CRIOA', 'Agro-Ecological Farming System (GREAT-CRIOA) Center', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code IN ('GREAT-CRIOA', 'GREAT'));
INSERT INTO colleges (code, name, unit_type, office) SELECT 'PALBIORECC', 'Palawan Biodiversity Research and Conservation Center (PALBioReCC)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'PALBIORECC');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'SSRDC', 'Social Science Research and Development Center (SSRDC)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code IN ('SSRDC', 'SSRDEC'));
INSERT INTO colleges (code, name, unit_type, office) SELECT 'BMRS', 'Binduyan Marine Research Station (BMRS)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'BMRS');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'WPS-CORP', 'West Philippine Sea Conservation, Research & Policy (WPS-CoRP) Center', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'WPS-CORP');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'WESTPHIL-CASH-RIC', 'WESTPHIL CASH RIC', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'WESTPHIL-CASH-RIC');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'BDLEC', 'BioDiscovery Learning Center (BDLeC)', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'BDLEC');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'HALAL', 'Halal Research and Training (Halal) Center', 'center', 'RDO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'HALAL');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'AMCEN', 'Regional Advanced Manufacturing Center (AMCen)', 'center', 'TTPO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'AMCEN');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'MEIC', 'Metals & Engineering Innovation Center (MEIC)', 'center', 'TTPO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'MEIC');
INSERT INTO colleges (code, name, unit_type, office) SELECT 'KI', 'Knowledge and Innovation Hub (KI)', 'center', 'TTPO' FROM schema_migrations WHERE version = 'phase39' AND NOT EXISTS (SELECT 1 FROM colleges WHERE code IN ('KI', 'KIH'));

-- Centers added earlier through "Add default centers" (old codes) are centers too.
UPDATE colleges SET unit_type = 'center' WHERE code IN ('TPSP', 'BDLEC', 'ABBA', 'TBTI', 'HALAL', 'TELP', 'COTLEC', 'SSRDEC', 'SSRDC', 'ASL', 'FPC', 'ATBI', 'AMCEN', 'MEIC', 'KIH', 'KI', 'CRAFIT', 'PALBIORECC', 'GREAT', 'GREAT-CRIOA', 'BMRS', 'ICENAQUA', 'ICEN-AQUA', 'WPS-CORP', 'WESTPHIL-CASH-RIC');
UPDATE colleges SET office = 'TTPO' WHERE code IN ('AMCEN', 'MEIC', 'KI', 'KIH') AND office IS NULL;
UPDATE colleges SET office = 'RDO' WHERE unit_type = 'center' AND office IS NULL;

-- ESO Internal (Karen G. Madarcos) splits its extensionists into three groups; the chart does not name them.
UPDATE clusters SET name = 'ESO Group 1' WHERE name = 'Extension Cluster 1';
UPDATE clusters SET name = 'ESO Group 2' WHERE name = 'Extension Cluster 2';
UPDATE clusters SET name = 'ESO Group 3' WHERE name = 'Extension Cluster 3';
UPDATE clusters SET leader_name = 'Karen G. Madarcos (ESO Internal)', notes = 'Extensionists: Alaysa A. Manuel, Brendon Jay A. Silverio, Rechiel S. Villarosa' WHERE name = 'ESO Group 1' AND leader_name IS NULL;
UPDATE clusters SET leader_name = 'Karen G. Madarcos (ESO Internal)', notes = 'Extensionists: Cristine Anne M. Castillo, Cristina Mae L. Posadas, Ellen Joy D. Manzano. Clerk: Jeshana Joelah H. Tumbagahan' WHERE name = 'ESO Group 2' AND leader_name IS NULL;
UPDATE clusters SET leader_name = 'Karen G. Madarcos (ESO Internal)', notes = 'Extensionists: Claribel B. Salazar, Riza G. San Juan, Edgar P. Paalan' WHERE name = 'ESO Group 3' AND leader_name IS NULL;
UPDATE clusters SET leader_name = 'Arlene L. Avillanosa (University Researcher V)', notes = 'ATBI: Maria Ana Caterina P. Alejandria (URA II), Elleonor P. Abatay (URA I), Lucio B. Ardines Jr. (Admin Aide VI). ICEN AQUA: Renie D. Siocon (URA II), Jasper D. Pacho (URA I). ABBA: Janry S. Ducado (URA I). TBTI: Sheryl M. Balbutan (URA II). PALBIORECC: Mike Allen G. Gabinete (URA II).' WHERE name = 'Research Cluster I' AND leader_name IS NULL;
UPDATE clusters SET leader_name = 'Lutgardo B. Alcantara (University Researcher IV)', notes = 'SSRDC: Michaela P. Fortaliza (Admin Aide VI, COTLEC). BMRS: Alexis G. Sumbe (URA I). BDLEC: Denz Ryl L. Herrera (URA I).' WHERE name = 'Research Cluster II' AND leader_name IS NULL;

UPDATE colleges SET extension_cluster_id = (SELECT id FROM clusters WHERE name = 'ESO Group 1') WHERE code IN ('CAFES', 'CET', 'AMCEN', 'MEIC', 'ATBI', 'WESTPHIL-CASH-RIC', 'GREAT-CRIOA', 'GREAT', 'PALBIORECC');
UPDATE colleges SET extension_cluster_id = (SELECT id FROM clusters WHERE name = 'ESO Group 2') WHERE code IN ('CED', 'CBEHM', 'CCJE', 'CPACD', 'CCAS', 'KI', 'KIH', 'HALAL', 'SSRDC', 'SSRDEC');
UPDATE colleges SET extension_cluster_id = (SELECT id FROM clusters WHERE name = 'ESO Group 3') WHERE code IN ('CFINS', 'ICEN-AQUA', 'ICENAQUA', 'WPS-CORP', 'ABBA', 'TBTI', 'BMRS', 'BDLEC');
UPDATE colleges SET research_cluster_id = (SELECT id FROM clusters WHERE name = 'Research Cluster I') WHERE code IN ('ATBI', 'ICEN-AQUA', 'ICENAQUA', 'ABBA', 'TBTI', 'GREAT-CRIOA', 'GREAT', 'PALBIORECC');
UPDATE colleges SET research_cluster_id = (SELECT id FROM clusters WHERE name = 'Research Cluster II') WHERE code IN ('SSRDC', 'SSRDEC', 'BMRS', 'WPS-CORP', 'WESTPHIL-CASH-RIC', 'BDLEC', 'HALAL');
