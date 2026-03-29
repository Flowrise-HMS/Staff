# Staff Module - Implementation Plan

## Overview
The Staff module manages healthcare employees/providers with roles, credentials, department assignments, and FHIR interoperability.

**Phase 3 of FlowRise HMS Roadmap**

---

## 1. Foundation & Models

### 1.1 Core Models

- [ ] **Staff (Employee)**
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

- [ ] **StaffCredential**
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

- [ ] **StaffType** - full_time, part_time, contract, volunteer, intern
- [ ] **EmploymentStatus** - active, inactive, terminated, suspended, on_leave
- [ ] **StaffRole** - provider, nurse, administrator, billing, receptionist, pharmacist, lab_technician, radiographer, physiotherapist, social_worker, counselor

---

## 2. Services Layer

### 2.1 StaffService
- [ ] CRUD operations for staff
- [ ] Registration with events dispatch
- [ ] Profile updates with events
- [ ] Employment status changes
- [ ] Search and filtering

### 2.2 StaffCredentialService
- [ ] Add/update credentials
- [ ] Verify credential
- [ ] Track expiry
- [ ] Document upload

### 2.3 StaffAssignmentService
- [ ] Assign to departments
- [ ] Set primary department
- [ ] Assign roles
- [ ] Manage specialties

### 2.4 StaffScheduleService (Optional)
- [ ] Set availability
- [ ] Update working hours

---

## 3. Role-Based Access Control (RBAC)

### 3.1 Staff Roles
- [ ] **Provider** - Doctors, Specialists, GPs
- [ ] **Nurse** - RN, EN, ANM
- [ ] **Administrative** - Admin staff, Receptionists
- [ ] **Billing** - Finance team
- [ ] **Pharmacist** - Pharmacy staff
- [ ] **Lab Technician** - Laboratory staff
- [ ] **Radiographer** - Imaging staff
- [ ] **Physiotherapist** - Rehab staff
- [ ] **Social Worker** - Social services
- [ ] **Counselor** - Mental health

### 3.2 Role Permissions Matrix
- [ ] Map each role to Filament permissions
- [ ] Integrate with Core's permission system

---

## 4. FHIR Integration

### 4.1 FHIR Resources
- [ ] **Practitioner** - Maps to Staff
  - Identifier (staff number)
  - Active status
  - Name (humanName)
  - Telecom (phone, email)
  - Address
  - Gender, birthDate
  - Qualification (credentials)
  - PractitionerRole (department assignments)

- [ ] **PractitionerRole** - Maps to Staff-Department assignment
  - Practitioner reference
  - Organization (Branch)
  - Location (Facility/Department)
  - Role (StaffRole)
  - Specialty
  - Active period

---

## 5. User Interface (Filament)

### 5.1 Staff Management
- [ ] Staff List Table
  - Search by name, staff number, department
  - Filter by role, status, department
  - Bulk actions

- [ ] Create/Edit Staff Form
  - Personal Information
  - Employment Details
  - Department Assignment
  - Role Assignment
  - Credentials

- [ ] Staff Profile View
  - Full profile display
  - Credentials list
  - Department assignments
  - Activity logs

### 5.2 Credential Management
- [ ] Credential List per Staff
- [ ] Add/Edit Credential Form
- [ ] Upload License Document
- [ ] Verification workflow

### 5.3 Settings
- [ ] Staff Roles Management
- [ ] Specialties Management

---

## 6. Events

- [ ] **StaffRegistered** - Dispatched on staff creation
- [ ] **StaffUpdated** - Dispatched on profile update
- [ ] **StaffDeactivated** - Dispatched on termination
- [ ] **CredentialVerified** - Dispatched on credential verification
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

## 8. Dependencies

### 8.1 Internal Modules
- [ ] Core (Branch, Department, Location, User)

### 8.2 External
- [ ] Spatie MediaLibrary (for credentials documents)
- [ ] Filament (already in use)

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

## OpenEMR/OpenMRS Inspirations

### From OpenEMR
- User and Facility Management system
- ACL-based access control
- Provider credentials tracking
- User groups (legacy)
- Multi-facility support

### From OpenMRS
- Practitioner FHIR resource
- Provider Management Module concepts
- Provider roles and specialities
- Provider-patient relationships

---

## Notes

- Staff is linked to CoreUser for authentication
- One Staff can have multiple roles
- One Staff can be assigned to multiple Departments
- Credentials have expiry tracking with notifications
- FHIR integration for interoperability
