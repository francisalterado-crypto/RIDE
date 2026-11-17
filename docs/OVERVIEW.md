# RIDE Hub: General Overview

RIDE Hub is the information management system of the Office of the Vice President for Research, Innovation,
Development and Extension (OVPRIDE) of Western Philippines University. It replaces paper routing of research and
extension forms with an online record that moves through the university's approval chain, and it keeps the
approved work monitored and reported until completion.

This document describes what the system does and who uses it. For how it is built, see
[ARCHITECTURE.md](ARCHITECTURE.md). For how it handles personal data, see [DATA-PRIVACY.md](DATA-PRIVACY.md).

## Offices served

| Office | Abbreviation | Covers |
|---|---|---|
| Research and Development Office | RDO | Research proposals, requests, incentive claims, research reports |
| Extension Services Office | ESO | Extension project proposals, conducted activities, extension reports |
| Office of the VP for RIDE | OVPRIDE | Final approval and university-wide reporting |

Colleges and research centers are grouped into research clusters and extension groups. Each cluster or group is
covered by a RIDE coordinator, who reviews the work of its colleges. Research centers are attached to an office
(RDO, ESO or TTPO, the technology transfer office).

## Users and roles

| Role | What they do |
|---|---|
| Faculty | Files proposals, requests and reports; co-authors records; keeps a researcher profile and scholarly record. |
| College Coordinator - Research / - Extension | Endorses the research or extension records of their college (first review step). Also a faculty member. |
| College Dean | Approves the records of the college; attests quarterly reports. |
| Research Coordinator (RIDE-RDO) | Reviews research records of the colleges in their clusters; prepares the RDO quarterly reports; completes the Terminal Report Assessment and the verification sections of incentive claims. |
| Field Extensionist (RIDE-ESO) | Reviews extension records of the colleges in their groups; prepares the ESO quarterly reports. |
| RDO Director / ESO Director | Approves the records of their office before final approval. |
| OVPRIDE | Grants final approval; sees all records. |
| Administrator | Manages accounts, colleges, clusters, programs, form templates and the public site. Sees everything but takes no part in approvals. |

New accounts are created through self-registration and stay inactive until an administrator approves them and
assigns roles.

## Kinds of records

Every form filed in the system is one record with a status and an approval history. Records fall into three
registries:

- **Proposals** (`/proposals`): the Research Proposal Application (RDO-20) and the Extension Project Proposal
  (ESO-03). On final approval a proposal becomes a project with a code such as `RIDE-2026-CET-001`.
- **Requests** (`/requests`): RDO requests such as critical activities, program or study proposals, external
  funding, paper presentation, manuscript writing, and the presentation, IPR and publication incentive claims.
  Approved requests receive a `REQ-` code.
- **Reports** (`/reports`): progress and terminal reports, quarterly reports, research output and extension
  reports, and their college-level consolidations. Approved reports end with status "approved".

## Approval workflow

A record starts as a draft. When the author submits it, it moves through the review steps of its office:

1. College Coordinator (Research or Extension) of the college. Skipped when the coordinator filed the record.
2. College Dean.
3. RIDE coordinator of the college's cluster or group.
4. RDO or ESO Director.
5. OVPRIDE (final approval).

Quarterly reports prepared by a RIDE coordinator go to the College Dean, the Director and OVPRIDE.

At each step the reviewer can approve, which forwards the record and notifies the next step's holders, or return
it for revision with comments, which sends it back to the author. Nobody can approve their own record. Some
records carry a reviewer section that is filled in at a specific step and saved with that approval:

- **Incentive claims** (RDO-02, RDO-03, RDO-04): the College Coordinator's evaluation and the RIDE coordinator's
  verification and incentive amount.
- **Terminal reports**: the Terminal Report Assessment (WPU-QSF-RDE-VPRDE-22) by the Research Coordinator. A
  judgment of major revisions or failed to pass returns the report to the researcher.

Every action is recorded with its author and time, and the sign-off blocks of printed forms are filled from these
approvals.

## Monitoring

Approved proposals become projects (`/projects`) with:

- milestones, with overdue alerts;
- progress reports;
- a document repository for the project's files;
- innovation records (IP disclosures, patents, technology transfers, prototypes) for innovation projects;
- extension records (beneficiaries, partner MOUs, impact metrics) for extension projects.

Extension and research staff also log conducted activities and research accomplishments, which feed the
quarterly reports.

## Reporting

- Quarterly reports per college, built from the logged activities and accomplishments.
- College consolidations of faculty research reports, filled automatically as faculty reports are approved.
- Registry exports to CSV, and printable office forms (.docx and .xlsx) generated from the official templates.
- Dashboards per role, with counts by status, records waiting at each step, and overdue items.
- An extension beneficiaries report by year.

## Other features

- **Researcher profiles and faculty directory**, including specializations and scholarly links.
- **Scholarly record**: published papers and paper presentations with attachments.
- **Co-authors**: invitations for registered faculty to join a record.
- **Messages**: direct messages between users, and announcements from administrators.
- **Downloadable forms**: blank office forms uploaded by administrators.
- **Faculty deadline settings**: filing periods that lock faculty reporting outside the open window.
- **Public site** (`/`): news, journal and conference pages, and highlight slides, managed by administrators.

## Glossary

| Term | Meaning |
|---|---|
| RIDE | Research, Innovation, Development and Extension |
| OVPRIDE | Office of the Vice President for RIDE |
| RDO / ESO / TTPO | Research and Development Office / Extension Services Office / technology transfer office |
| RIDE coordinator | Research Coordinator (RIDE-RDO) or Field Extensionist (RIDE-ESO), assigned to clusters |
| Cluster / group | Set of colleges covered by one RIDE coordinator |
| Form codes | Official office form numbers, for example WPU-QSF-RIDE-RDO-20 |
