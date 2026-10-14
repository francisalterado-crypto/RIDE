# RIDE Integrated Monitoring & Workflow Approval System

PHP web application (XAMPP) with a Java REST API client for integration and public showcase workflows.

## Stack

| Component | Technology |
|-----------|------------|
| Web app | PHP 8.2+, plain MVC |
| Database | MySQL (MariaDB via XAMPP) |
| API client | Java 17 + Maven (`java-client/`) |
| Auth (dev) | Email / password sessions |
| Auth (prod) | SSO planned |

## Requirements

- XAMPP (Apache + MySQL + PHP 8.2+)
- Optional: JDK 17+ and Maven for the Java client

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
| Ana Reyes | admin@ride.local | RIDE Admin (system) | University-wide |
| Ramon Villanueva | vpride@ride.local | Admin / VPRIDE | University-wide |
| Liza Mendoza | director.research@ride.local | RDO Director | University-wide |
| Carlos Javier | director.extension@ride.local | ESO Director | University-wide |
| Liza Mendoza | director@ride.local | RDO Director (alias) | University-wide |
| Mark Santos | coord.research.cet@ride.local | Research Coordinator | CET |
| Grace Lim | coord.extension.cet@ride.local | Field Extensionist | CET |
| Patricia Ong | dean.cet@ride.local | College Dean | CET |
| John Cruz | faculty.research.cet@ride.local | Faculty (Research) | CET |
| Nina Bautista | faculty.extension.cet@ride.local | Faculty (Extension) | CET |
| Elena Ramos | coord.research.cas@ride.local | Research Coordinator | CAS |
| Paolo Garcia | coord.extension.cas@ride.local | Field Extensionist | CAS |
| Isabel Torres | dean.cas@ride.local | College Dean | CAS |
| Miguel Lopez | faculty.research.cas@ride.local | Faculty (Research) | CAS |
| Sara Dela Cruz | faculty.extension.cas@ride.local | Faculty (Extension) | CAS |
| Daniel Tan | coord.research.cbm@ride.local | Research Coordinator | CBM |
| Monica Reyes | coord.extension.cbm@ride.local | Field Extensionist | CBM |
| Antonio Flores | dean.cbm@ride.local | College Dean | CBM |
| Rachel Gomez | faculty.research.cbm@ride.local | Faculty (Research) | CBM |
| Kevin Navarro | faculty.extension.cbm@ride.local | Faculty (Extension) | CBM |

**Already installed?** Create or refresh all demo accounts:

```bat
c:\xampp\php\php.exe scripts\seed-demo-accounts.php
```

**Remove sample/demo data** (proposals, extra accounts, uploads):

```bat
c:\xampp\php\php.exe scripts\clean-sample-data.php
```

## Workflow (Phase 1)

1. **Project Leader** creates a proposal (draft) and submits.
2. **College Coordinator** approves → routes to ethics (if required) or DEAN.
3. **Ethics Reviewer** (assign role manually) approves when applicable.
4. **DEAN** approves → **RIDE Director** final approval.
5. On final approval, a **project code** is generated (e.g. `RIDE-2026-CET-001`).

## REST API (for Java / external systems)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | JSON `{email, password}` → `{token, user}` |
| GET | `/api/proposals` | Bearer token → proposal list |
| GET | `/api/stats` | Bearer token → KPI stats |

### Java client

```bat
cd java-client
mvn -q package
java -jar target\ride-api-client-1.0.0-SNAPSHOT.jar http://localhost/RIDE/public director@ride.local password123
```

## Project structure

```
RIDE/
├── app/           # Controllers, models, views, core
├── database/      # schema.sql, seeds.sql
├── java-client/   # Maven API client
├── public/        # Web root (index.php, assets)
├── routes/        # web.php, api.php
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

### API (Phase 2)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/projects` | Ongoing approved projects |
| GET | `/api/reports/extension-beneficiaries?years=3` | Beneficiary report (reporter roles) |

Existing databases auto-migrate on first request after update (`database/migrations/phase2.sql`).

## Next phases

- PDF/Excel export for accreditation reports
- University SSO and finance system integration
- Public showcase website API
