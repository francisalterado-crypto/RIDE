# RIDE Hub - Information Management System for RIDE

PHP web application (XAMPP locally, Vercel with Postgres in production) for research and extension proposals, approvals, monitoring and reporting.

## Stack

| Component | Technology |
|-----------|------------|
| Web app | PHP 8.2+, plain MVC |
| Database | MySQL (MariaDB via XAMPP) |
| Auth (dev) | Email / password sessions |
| Auth (prod) | SSO planned |

## Requirements

- XAMPP (Apache + MySQL + PHP 8.2+)

## Setup (XAMPP)

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Ensure `mod_rewrite` is enabled in Apache.
3. Copy or clone this project to `c:\xampp\htdocs\RIDE`.
4. Edit `app/config/config.php` if your DB credentials differ from `root` / empty password.
5. Run the installer (creates DB, tables, and the default admin account on first run):

```bat
c:\xampp\php\php.exe scripts\install.php
```

6. Open: **http://localhost/RIDE/public/login**

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

**Already installed?** Create or refresh all demo accounts:

```bat
c:\xampp\php\php.exe scripts\seed-demo-accounts.php
```

**Remove sample/demo data** (proposals, extra accounts, uploads):

```bat
c:\xampp\php\php.exe scripts\clean-sample-data.php
```

## Approval workflow

Proposals, requests, progress and terminal reports:

1. **Faculty** creates the record (draft) and submits.
2. **College Coordinator** (Research or Extension, of the college) endorses. A College Coordinator's own record skips this step.
3. **College Dean** approves.
4. **RIDE Coordinator** of the college's cluster (Research Coordinator for RDO, Field Extensionist for ESO) reviews.
5. **RDO or ESO Director** approves.
6. **OVPRIDE** grants final approval. Proposals get a project code (e.g. `RIDE-2026-CET-001`); requests get a `REQ-` code.

Quarterly reports (ESO-06 to 10, RDO-05 to 14) are prepared by the RIDE Coordinator, one per college of their cluster, then go to the College Dean (attests), the Director and OVPRIDE.

The Administrator sees everything and manages the configuration, but is not part of the approval chain.

## Health check

`GET /api/health` returns `{"status":"ok","database":"connected"}`, or HTTP 503 with `"status":"error"` when the
database is not configured or not reachable. It is public and reports no connection details.

## Project structure

```
RIDE/
├── app/           # Controllers, models, views, core
├── database/      # schema.sql, seeds.sql
├── public/        # Web root (index.php, assets)
├── routes/        # web.php
└── scripts/       # install.php
```

## Phase 2 (Monitoring)

- **Projects** hub after approval (`/projects`) — milestones, progress reports, documents
- **Milestones** with overdue detection and in-app notifications
- **Progress reports** — narrative, financial lines, outputs; draft → submit workflow
- **Document repository** — upload/download (PDF, Office, images, zip; max 10 MB)
- **Innovation module** — IP disclosures, patents, technology transfers, prototypes
- **Extension module** — beneficiaries, partner MOUs, impact metrics
- **Accreditation report** — extension beneficiaries by year (`/reports/extension-beneficiaries`)

Existing databases auto-migrate on first request after update (`database/migrations/phase2.sql`).

## Next phases

- PDF/Excel export for accreditation reports
- University SSO and finance system integration
