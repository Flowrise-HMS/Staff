# Staff Module Implementation Record

**Document Status:** Implemented (verified against code 2026-09-20)
**Module:** FlowRise HMS Staff Module (`Modules/Staff`)

This document replaces the original April 2026 implementation plan and describes the module as built. Staff-facing instructions are in [docs/user-guide/staff-management.md](../../../docs/user-guide/staff-management.md); account and role administration in [docs/admin-guide/user-management.md](../../../docs/admin-guide/user-management.md).

---

## 1. Scope

The Staff module owns the people directory: staff profiles, professional credentials, department assignments, specialties, the link to the login account (`users.id`), the staff ID card, REST endpoints for staff and credentials, and the FHIR `Practitioner` / `PractitionerRole` transformers. Attendance (badge id `zk_user_id`) and Appointment (practitioner) build on it.

Not implemented (and not planned in this module): availability/roster models, on-call schedules, a `StaffRole` enum (system roles come from Core's `UserRole` and Filament Shield), credential-expiry notifications (the `CredentialExpired` event exists but nothing schedules `StaffCredentialService::processExpiredCredentials()`).

---

## 2. Database

6 migrations:

| Table | Purpose |
|-------|---------|
| `staff` | UUID id, `user_id` (login), `branch_id` (added 2026-05-09), `staff_number` (unique, `STF-<year>-<sequence>`), title, names, gender, date_of_birth, `staff_type`, `employment_status`, hire/termination dates and reason, `contact` (JSON), `address` (JSON), `emergency_contact` (JSON), `zk_user_id` (biometric badge, added 2026-08-10), soft deletes |
| `staff_credentials` | credential_type, credential_number, issuing_authority, issuing_country, issuing_state, issue_date, expiry_date, status, verification_notes, verified_by/at, `document_path` (uploaded scan) |
| `staff_departments` | staff_id, department_id, designation, start_date, end_date, is_primary |
| `staff_specialties` | specialty_name, specialty_code, description, issuing_body, certificate_number, certification_date, expiry_date, is_primary, certificate_path |

---

## 3. Code structure (`app/`)

| Area | Contents |
|------|----------|
| `Models/` | `Staff` (generates `staff_number` on create), `StaffCredential`, `StaffDepartment`, `StaffSpecialty` |
| `Enums/` | `StaffType` (full_time, part_time, contract, volunteer, intern, resident, consultant), `EmploymentStatus` (active, inactive, on_leave, suspended, terminated, pending_verification), `CredentialType` (24 values), `CredentialStatus` (pending, verified, expired, rejected, revoked, under_review) |
| `Classes/Services/` | `StaffService` (CRUD/search), `StaffSearchService` (table + global search), `StaffAssignmentService` (assign/remove/transfer departments, bulk assign, specialties, primary specialty, summary), `StaffCredentialService` (create/update/verify/reject/renew/bulk verify, expiring, pending, expired processing, statistics), `StaffAccountService` (create user account with generated username/email/password, send/resend credentials email, reset password, activate/deactivate/unlink account, account status) |
| `Classes/Fhir/` | `FhirPractitionerTransformer`, `FhirPractitionerRoleTransformer` (read/search/create/update/delete through `Modules/FHIR`) |
| `Events/` | `StaffRegistered`, `StaffUpdated`, `StaffDeactivated`, `StaffReactivated`, `CredentialVerified`, `CredentialRejected`, `CredentialRenewed`, `CredentialExpired` |
| `Notifications/` | `StaffCredentialsNotification` (login details email) |
| `Http/` | `StaffIdCardController` (`GET /staff/{staff}/id-card`, auth + verified), API `StaffController` (`GET/POST /api/v1/staff`, `GET/PUT /api/v1/staff/{id}`) and `StaffCredentialController` (`GET/POST /api/v1/staff/{staff}/credentials`, `PUT .../credentials/{credential}`) registered through Core's `ApiRouteRegistrar` when the Api module is enabled; form requests; `StaffTransformer` / `StaffCredentialTransformer` API resources |
| `Filament/` | `StaffCluster` (sidebar Operations → Staff, sort 20), `StaffResource` (pages List, Create, View, Edit, Activities; schemas `StaffForm`, `StaffInfolist`; `StaffTable`), relation managers `CredentialsRelationManager`, `DepartmentsRelationManager`, `SpecialtiesRelationManager`; soft-dependency managers from Attendance (records, daily attendance) and Appointment (staff appointments); `StaffExporter` |
| `Policies/` | `StaffPolicy` |
| `database/` | factories for all four models; `StaffDatabaseSeeder`, `StaffCustomPermissionSeeder` (`print_staff_id` → super_admin) |

---

## 4. Filament behaviour

- **Form** sections: Personal Information (branch, title, names, gender, date of birth), Contact Information, Address, Employment Details (staff type, employment status, hire date, ZK user ID), Emergency Contact.
- **Table**: Staff #, Name (+ account icon), Gender, Type, Status, Department, Email, Phone, Hired, Tenure; filters staff type, status, gender, department, with/without user account, hire date range; row actions View, Staff ID Card, Edit, Delete, Create User Account, Manage Account, Reset Password, Resend Credentials, Update Status, Activities; bulk delete.
- **View page** header: Activities, Staff ID card (needs `print_staff_id`), Edit, Delete. Infolist: summary (staff number, type, status), Personal Information, Employment (hire date, tenure, termination), Contact, Address, Emergency Contact.
- **Account actions** call `StaffAccountService`; "Create User Account" has a "Send credentials via email" toggle (default on) and a role multi-select.

---

## 5. Permissions

Shield abilities on `Staff` plus `View StaffCluster`; custom `print_staff_id` (config `permissions`). Credential/department/specialty managers inherit the staff permissions.

---

## 6. Tests

17 test files (`tests/Feature`: model, credential model, edge cases, ID card print, FHIR Practitioner/PractitionerRole API, staff API, credential API; `tests/Unit`: enums, models, services, FHIR transformers, relation-manager soft boundary). Run with `php artisan test --compact Modules/Staff/tests`.
