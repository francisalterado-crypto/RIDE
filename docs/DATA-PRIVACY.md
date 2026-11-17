# RIDE Hub: Data Privacy

This document describes the personal data that RIDE Hub collects and stores, why, who can see it, how it is
protected, and what remains to be done. It is written for the university's Data Protection Officer (DPO),
administrators and developers, as input to the university's compliance with the Data Privacy Act of 2012
(Republic Act No. 10173), its Implementing Rules and Regulations, and the issuances of the National Privacy
Commission (NPC). It describes the system as built; it is not legal advice, and the university's own privacy
policies take precedence.

## Roles under the Data Privacy Act

| Role | Who | Responsibility |
|---|---|---|
| Personal information controller | Western Philippines University, through the Office of the VP for RIDE | Decides the purposes of processing; answers requests and incidents |
| Data Protection Officer | Designated by the university | Oversees compliance; contact point for data subjects and the NPC |
| Personal information processors | Developers and hosting providers acting for the university | Process data only as instructed and under appropriate agreements |

**Contact for privacy matters:** _to be completed by the university (DPO name, office and e-mail)._

## Personal data held

| Category | Data | Where it is kept | Who can see it |
|---|---|---|---|
| Account | Name (first, middle, last, title prefix), e-mail, password hash, active status | `users` | The user; administrators; names appear to reviewers and co-authors on records |
| Profile | Sex, position, contact number, college, campus, academic program, specializations, ORCID / Google Scholar / ResearchGate / GitHub links | `users`, `user_specializations` | The user; administrators; other signed-in users through the faculty directory and records |
| Profile photo | Image file | `storage/avatars` | Anyone who knows the user's ID (see open items) |
| Signature | Image file, printed on approved forms | `storage/signatures` | Any signed-in user who knows the user's ID (see open items) |
| Roles and assignments | Roles, college, clusters | `user_roles`, `user_clusters` | Administrators; used for access decisions |
| Records | Content of proposals, requests and reports, including the proponent's name, sex, position, contact number and e-mail copied from the profile, co-authors, budgets and results | `proposals.summary` (JSON) | The author, co-authors, and reviewers within their scope (college, cluster, office) |
| Review history | Who approved or returned a record, when, with comments and reviewer evaluations | `approval_actions`, `proposal_comments`, `proposals.summary` | Same as the record |
| Uploaded files | Project documents, activity documents, scholarly attachments; may contain personal data of researchers, participants or beneficiaries | `storage/` | Same as the record or activity they belong to |
| Extension data | Beneficiary groups and counts, participant types, survey counts; legacy attendance sheets with attendee names, sex, affiliation, contact details and signatures | `extension_activities`, `community_beneficiaries`, `proposals.summary` | The author and reviewers within scope |
| Scholarly record | Publications and presentations with co-authors | `published_papers`, `paper_presentations`, `scholarly_attachments` | The user; administrators |
| Communications | Direct messages, notifications, announcements | `direct_messages`, `notifications`, `admin_messages` | Sender and recipient; the notified user; all users for announcements |
| Technical | Session data (signed-in user, form drafts, security tokens); audit log entries with user, action, record and IP address | `php_sessions`, `audit_logs` | Not shown in the interface; database administrators |

The system does not process sensitive personal information as defined by the Act beyond what users choose to
put in records or uploads (for example health or government ID details in supporting documents). It does not
collect location, does not track browsing, and runs no analytics.

## Purposes

Personal data is processed to:

- identify users, control access and keep accounts secure;
- prepare, route, review and approve research and extension proposals, requests and reports;
- monitor approved projects and produce the university's research and extension reports, including
  accreditation and gender and development (GAD) reporting;
- print official forms with the names and signatures of the persons who signed them;
- keep an accountable record of approvals and changes;
- maintain researcher profiles and the faculty directory for collaboration.

## Basis for processing

The university processes this data to carry out its research and extension functions as a state university and
to meet its reporting obligations, and for the employment and academic relationship with its personnel. Where
data about outside persons is collected (for example participants of extension activities), the collecting
office is responsible for informing them, as the legacy attendance sheet's printed privacy notice did. The DPO
should confirm the applicable bases and document them in the university's privacy notice.

## Sharing and third parties

Personal data is not sold or shared for marketing. It leaves the university's control only in these cases:

| Recipient | What it receives | Why |
|---|---|---|
| Hosting or database provider, if the system is hosted outside university servers | All data stored by the system | Hosting; they act as processors |
| cdnjs (Cloudflare), jsDelivr, Google Fonts | The visitor's IP address and browser details when pages load fonts, icons, charts, maps and the PDF tool | Delivery of browser libraries |
| OpenStreetMap Nominatim and OpenFreeMap | Location text typed into a venue or location field, and map tile requests | Location search on activity and event forms |
| Western Philippines University journal site | A request from the server, with no personal data | Showing the latest journal issue on the public site |

If the system is hosted outside the university, a data processing agreement with the provider and an
assessment of any cross-border transfer are the responsibility of the university.

## Retention

Current behavior:

- Expired sessions are deleted by PHP session garbage collection (session lifetime 2 hours by default).
- Records, uploads, review history, messages, notifications and audit logs are kept indefinitely.
- Deactivated accounts are kept, with their records, so that approval histories remain complete.

The university should set retention periods consistent with its records disposition schedule (for example under
the National Archives of the Philippines), after which records are archived or deleted and audit log IP
addresses are removed. The system does not yet enforce such periods.

## Security measures

Organizational, physical and technical measures required by the Act are the university's responsibility. The
system provides these technical measures:

- Passwords stored as bcrypt hashes; plain passwords are never stored or logged.
- Sessions stored in the database, with the session ID regenerated at sign-in and a cookie that is `HttpOnly`,
  `SameSite=Lax` and, over HTTPS, `Secure`.
- Protection against cross-site request forgery on every form submission.
- Prepared statements for all database queries.
- Role-based access with record-level scope; nobody can approve their own record.
- Uploads limited by type and size, and served with safe content types.
- Encrypted connections (TLS) to the hosted database and over HTTPS in production.
- An audit trail of approvals and changes, with user, time and IP address.

## Rights of data subjects

| Right | How it is handled |
|---|---|
| To be informed | Through the university's privacy notice (to be shown at registration; see open items) |
| To access | Users see their profile and their records in the system; other requests go to the DPO |
| To rectify | Users edit their own profile, photo and signature under My Profile; record content is corrected by returning the record for revision |
| To object, erase or block | Requests go to the DPO. Administrators can deactivate an account; deleting data that is part of approved records is decided case by case, since those records are official |
| To data portability | Registry exports (CSV) and printed forms; other requests go to the DPO |
| To file a complaint | With the DPO, and with the National Privacy Commission |

## Personal data breaches

If a breach is suspected (for example unauthorized access, a leaked database or backup, or lost credentials of
an administrator):

1. Contain it: deactivate affected accounts, rotate database and hosting credentials, and preserve logs.
2. Report it to the DPO immediately.
3. The DPO assesses it and, when the Act requires, notifies the National Privacy Commission and the affected
   persons within 72 hours of knowledge of the breach.
4. Record the incident and the actions taken.

## Open items

These gaps were found while documenting the system and should be resolved:

1. **No privacy notice or consent step at registration.** Registration should show the university's privacy
   notice and record that the user has read it.
2. **Profile photos are public.** `GET /avatars/{userId}` needs no sign-in, so photos can be fetched by guessing
   user IDs. It should require sign-in.
3. **Any signed-in user can fetch any signature image** through `GET /signatures/{userId}`. Signatures should be
   visible only to their owner and on records the viewer may open, to limit misuse.
4. **No retention enforcement.** Records, audit logs and IP addresses are kept indefinitely (see Retention).
5. **Uploaded files are not encrypted at rest.** Access to the `storage/` folder and its backups must be
   restricted on the server.
6. **No sign-in rate limiting or two-factor authentication**, which raises the risk of account takeover.
7. **Committed database backup.** `database/backups/ride_ims_demo_backup.sql` is in version control. Confirm it
   holds demo data only; real data must never be committed.
8. **Hosting agreements.** If the system is hosted outside university servers, a data processing agreement and a
   cross-border transfer assessment should be in place before real data is stored.
