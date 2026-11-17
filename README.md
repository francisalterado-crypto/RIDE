# RIDE Hub - Information Management System for RIDE

RIDE Hub is the information management system of the Office of the Vice President for Research, Innovation,
Development and Extension (OVPRIDE) of Western Philippines University. Faculty file research and extension
proposals, requests and reports online; the records move through the college and RIDE approval chain; approved
work is monitored as projects and summarized in the university's quarterly and accreditation reports.

## Documentation

| Document | Contents |
|---|---|
| [General overview](docs/OVERVIEW.md) | Purpose, users and roles, record types, approval workflow, features |
| [Architecture](docs/ARCHITECTURE.md) | Components, request flow, data model, configuration, deployment, security |
| [Data privacy](docs/DATA-PRIVACY.md) | Personal data held, access, sharing, retention, data subject rights, open items |
| [Database diagram](docs/erd/) | Entity-relationship diagram (draw.io, with PNG and PDF exports) |
| [License](LICENSE) | Copyright and terms of use |

## Stack

| Component | Technology |
|---|---|
| Application | PHP 8.2+, plain MVC (no framework or Composer dependencies) |
| Database | MySQL / MariaDB (PostgreSQL also supported) |
| Web server | Apache with `mod_rewrite` (XAMPP locally) |
| Sign-in | E-mail and password, database-stored sessions |

## Local setup (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel, with `mod_rewrite` enabled.
2. Place the project at `c:\xampp\htdocs\RIDE`.
3. If the database credentials differ from `root` with an empty password, set them through the environment
   variables listed in [Architecture: Configuration](docs/ARCHITECTURE.md#configuration).
4. Run the installer, which creates the database, the tables and the administrator account:

   ```bat
   c:\xampp\php\php.exe scripts\install.php
   ```

5. Open **http://localhost/RIDE/login**.

Schema changes are applied automatically on the next request after an update.

### Demo accounts

**Password for all:** `password123`

| Name | Email | Role | College / scope |
|------|-------|------|-----------------|
| Ana Reyes | admin@ride.local | Administrator | University-wide |
| Ramon Villanueva | vpride@ride.local | OVPRIDE | University-wide |
| Liza Mendoza | director.research@ride.local | RDO Director | University-wide |
| Carlos Javier | director.extension@ride.local | ESO Director | University-wide |
| Liza Mendoza | director@ride.local | RDO Director (alias) | University-wide |
| Teresa Aquino | coord.research.cluster1@ride.local | Research Coordinator (RIDE-RDO) | Research Cluster I: CET, CCAS |
| Victor Santiago | coord.research.cluster2@ride.local | Research Coordinator (RIDE-RDO) | Research Cluster II: CBEHM |
| Rafael Ilagan | coord.extension.group1@ride.local | Field Extensionist (RIDE-ESO) | ESO Group 1: CET |
| Bianca Ferrer | coord.extension.group2@ride.local | Field Extensionist (RIDE-ESO) | ESO Group 2: CCAS, CBEHM |
| Mark Santos | coord.research.cet@ride.local | College Coordinator - Research | CET |
| Grace Lim | coord.extension.cet@ride.local | College Coordinator - Extension | CET |
| Patricia Ong | dean.cet@ride.local | College Dean | CET |
| John Cruz | faculty.cet@ride.local | Faculty | CET |
| Elena Ramos | coord.research.cas@ride.local | College Coordinator - Research | CCAS |
| Paolo Garcia | coord.extension.cas@ride.local | College Coordinator - Extension | CCAS |
| Isabel Torres | dean.cas@ride.local | College Dean | CCAS |
| Miguel Lopez | faculty.cas@ride.local | Faculty | CCAS |
| Daniel Tan | coord.research.cbm@ride.local | College Coordinator - Research | CBEHM |
| Monica Reyes | coord.extension.cbm@ride.local | College Coordinator - Extension | CBEHM |
| Antonio Flores | dean.cbm@ride.local | College Dean | CBEHM |
| Rachel Gomez | faculty.cbm@ride.local | Faculty | CBEHM |

Create or refresh the demo accounts on an existing installation:

```bat
c:\xampp\php\php.exe scripts\seed-demo-accounts.php
```

Remove sample data (records, extra accounts and uploads):

```bat
c:\xampp\php\php.exe scripts\clean-sample-data.php
```

Demo accounts are for local use only; do not create them on a production deployment.

## Approval workflow in brief

1. **Faculty** create a record as a draft and submit it.
2. The **College Coordinator** (Research or Extension) endorses it, unless they filed it themselves.
3. The **College Dean** approves.
4. The **RIDE coordinator** of the college's cluster (Research Coordinator for RDO, Field Extensionist for ESO)
   reviews.
5. The **RDO or ESO Director** approves.
6. **OVPRIDE** grants final approval. Proposals receive a project code such as `RIDE-2026-CET-001`; requests
   receive a `REQ-` code.

Quarterly reports, prepared by the RIDE coordinator for each college of their cluster, go to the College Dean,
the Director and OVPRIDE. The Administrator manages the system but takes no part in approvals. See the
[general overview](docs/OVERVIEW.md) for details.

## Health check

`GET /api/health` returns `{"status":"ok","database":"connected"}`, or HTTP 503 with `"status":"error"` when the
database is not configured or not reachable. It is public and reports no connection details.

## Project structure

```
RIDE/
|-- app/           Controllers, models, services, support rules, views, core
|-- database/      schema.sql, seeds.sql, migrations/
|-- docs/          Documentation and database diagram
|-- public/        Web root (index.php, assets); the server's document root in production
|-- routes/        web.php
`-- scripts/       Installer and maintenance scripts
```

## License

Copyright (c) 2026 Alterado et al. All rights reserved. See [LICENSE](LICENSE).
