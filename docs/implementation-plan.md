# STAFF MODULE IMPLEMENTATION PLAN

**Document Status:** Draft
**Last Updated:** April 2026
**Module:** FlowRise HMS Staff Module

---

## 1. EXECUTIVE SUMMARY

The Staff module manages healthcare employees/providers with roles, credentials, department assignments, and FHIR interoperability.

This module is the FOUNDATION for clinical operations - every clinical action is performed by a staff member.

### Core Entities

| Entity | Purpose |
|--------|--------|
| **Staff** | Healthcare employee/provider |
| **StaffCredential** | Licenses, certifications |
| **StaffDepartment** | Department assignments |
| **StaffSpecialty** | Specialty/skill tracking |

---

## 1. Foundation & Models

### 1.1 Core Models

- [x] **Staff (Employee)**
  - UUID-based identifier
  - User account link (CoreUser)
  - Personal info: name, DOB, gender, photo
  - Employee ID (staff number - unique)
  - Employment info: hire date, termination date
  - Employment status (active, inactive, terminated, on_leave)
  - Staff type (full_time, part_time, contract, volunteer)
  - Specialties (many-to-many)
  - Department assignments (many-to-many with primary)
  - Role assignments (Provider, Nurse, Admin, etc.)

- [x] **StaffCredential**
  - License/registration numbers
  - Issued by (authority)
  - Issue date, expiry date
  - Verification status
  - Document upload (license scan)

- [ ] **StaffAvailability**
  - Working days/hours per department
  - Shift patterns
  - On-call schedules

- [ ] **StaffSchedule** (Optional - moved to Appointments Module)
  - Weekly schedule template
  - Exception dates

### 1.2 Enums Needed

- [x] **StaffType** - full_time, part_time, contract, volunteer, intern
- [x] **EmploymentStatus** - active, inactive, terminated, suspended, on_leave
- [x] **StaffRole** - provider, nurse, administrator, billing, receptionist, pharmacist, lab_technician, radiographer, physiotherapist, social_worker, counselor

---

## 2. Services Layer

### 2.1 StaffService
- [x] CRUD operations for staff
- [x] Registration with events dispatch
- [x] Profile updates with events
- [x] Search and filtering

### 2.2 StaffCredentialService
- [x] Add/update credentials
- [x] Verify credential
- [x] Track expiry
- [ ] Document upload

### 2.3 StaffAssignmentService
- [x] Assign to departments
- [x] Set primary department
- [x] Assign roles
- [x] Manage specialties

### 2.4 StaffScheduleService (Optional)
- [ ] Set availability
- [ ] Update working hours

---

## 3. Role-Based Access Control (RBAC)

### 3.1 Staff Roles
- [x] **Provider** - Doctors, Specialists, GPs
- [x] **Nurse** - RN, EN, ANM
- [x] **Administrative** - Admin staff, Receptionists
- [x] **Billing** - Finance team
- [x] **Pharmacist** - Pharmacy staff
- [x] **Lab Technician** - Laboratory staff
- [x] **Radiographer** - Imaging staff
- [x] **Physiotherapist** - Rehab staff
- [x] **Social Worker** - Social services
- [x] **Counselor** - Mental health

### 3.2 Role Permissions Matrix
- [ ] Map each role to Filament permissions
- [x] Integrate with Core's permission system

---

## 4. FHIR Integration

### 4.1 FHIR Resources
- [x] **Practitioner** - Maps to Staff
  - Identifier (staff number)
  - Active status
  - Name (humanName)
  - Telecom (phone, email)
  - Address
  - Gender, birthDate
  - Qualification (credentials)
  - PractitionerRole (department assignments)

- [x] **PractitionerRole** - Maps to Staff-Department assignment
  - Practitioner reference
  - Organization (Branch)
  - Location (Facility/Department)
  - Role (StaffRole)
  - Specialty
  - Active period

---

## 5. User Interface (Filament)

### 5.1 Staff Management
- [x] Staff List Table
  - Search by name, staff number, department
  - Filter by role, status, department
  - Bulk actions

- [x] Create/Edit Staff Form
  - Personal Information
  - Employment Details
  - Department Assignment
  - Role Assignment
  - Credentials

- [x] Staff Profile View
  - Full profile display
  - Credentials list
  - Department assignments
  - Activity logs

### 5.2 Credential Management
- [x] Credential List per Staff
- [x] Add/Edit Credential Form
- [ ] Upload License Document
- [x] Verification workflow

### 5.3 Settings
- [x] Staff Roles Management
- [x] Specialties Management

---

## 6. EVENTS

- [x] **StaffRegistered** - Dispatched on staff creation
- [x] **StaffUpdated** - Dispatched on profile update
- [x] **StaffDeactivated** - Dispatched on termination
- [x] **StaffReactivated** - Dispatched on reactivation
- [x] **CredentialVerified** - Dispatched on credential verification
- [ ] **CredentialExpired** - Dispatched when credential expires

---

## 7. Testing

### 7.1 Unit Tests
- [ ] StaffServiceTest
- [ ] StaffCredentialServiceTest
- [ ] StaffAssignmentServiceTest

### 7.2 Coverage Target
- [ ] 90%+ code coverage

---

## 8. DEPENDENCIES

### 8.1 Internal Modules
- [x] Core (Branch, Department, Location, User)

### 8.2 External
- [x] Spatie MediaLibrary (for credentials documents)
- [x] Filament (already in use)

---

## Implementation Sequence

### Phase 3.1: Foundation (Priority: Critical)
1. Create Staff model + migration
2. Create Enums
3. Create StaffService with CRUD
4. Basic Filament resource

### Phase 3.2: Credentials (Priority: High)
5. StaffCredential model
6. StaffCredentialService
7. Credential management UI

### Phase 3.3: Assignments (Priority: High)
8. Department/Role assignment
9. Specialties

### Phase 3.4: RBAC (Priority: High)
10. Role permissions
11. Integration with auth

### Phase 3.5: FHIR (Priority: Medium)
12. FHIR Practitioner
13. FHIR PractitionerRole

### Phase 3.6: Polish (Priority: Medium)
14. Events
15. Tests
16. UI improvements

---

## 9. IMPLEMENTATION CHECKLIST

### 9.1 What's Done ✅

| Item | Notes |
|------|-------|
| Database migrations | staff, staff_credentials, staff_departments, staff_specialties |
| Staff model | With HasUuids, relationships |
| StaffCredential model | License tracking |
| StaffDepartment model | Department assignments |
| StaffSpecialty model | Specialty tracking |
| Enums | StaffType, EmploymentStatus, StaffRole, CredentialType |
| StaffService | Full CRUD + search |
| StaffCredentialService | Credential management |
| StaffAssignmentService | Department/role assignment |
| StaffSearchService | Global search |
| StaffResource | Full Filament resource |
| StaffForm | Multi-step form |
| StaffInfolist | Display cards |
| StaffTable | Column configuration |
| CredentialsRelationManager | Credential UI |
| DepartmentsRelationManager | Department assignment UI |
| SpecialtiesRelationManager | Specialties UI |
| Events | StaffRegistered, StaffUpdated, etc. |

### 9.2 What's Pending ⏳

| Item | Priority | Notes |
|------|----------|-------|
| StaffAvailability model | LOW | Working hours tracking |
| StaffSchedule model | LOW | Shift scheduling |
| Document upload for credentials | MEDIUM | Spatie Media Library |
| Role permissions matrix | MEDIUM | Per role permissions |
| Credential expiry notifications | LOW | Event listener |
| Tests | MEDIUM | Unit tests coverage |

### 9.3 FHIR Integration ✅

- [x] Practitioner resource mapping done
- [x] PractitionerRole resource mapping done
