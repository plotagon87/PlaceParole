# Database Soft Delete Migration - COMPLETE ✅

## Problem
The application was attempting to use soft delete functionality (marking records as deleted instead of actually removing them) but the required database columns were missing, causing PDOExceptions:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'a.deleted_at' in 'where clause'
```

## Root Cause
The code referenced `deleted_at` column in soft delete queries, but the columns didn't exist in:
- `announcements` table
- `suggestions` table

## Solution Implemented

### 1. Added `deleted_at` column to `announcements` table
```sql
ALTER TABLE announcements ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL
```

**Impact:** Enables soft delete functionality in:
- [modules/announcements/list.php](modules/announcements/list.php#L17) - Filters out deleted announcements
- [modules/announcements/delete.php](modules/announcements/delete.php#L38) - Soft deletes announcements

### 2. Added `deleted_at` column to `suggestions` table
```sql
ALTER TABLE suggestions ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL
```

**Impact:** Enables soft delete functionality in:
- [modules/suggestions/list.php](modules/suggestions/list.php#L29) - Filters out deleted suggestions  
- [modules/suggestions/delete.php](modules/suggestions/delete.php) - Soft deletes suggestions

## Updated Table Structures

### Announcements Table
```
id                int(11)
market_id         int(11)
manager_id        int(11)
title             varchar(200)
body              text
sent_via          set('web','sms','email')
created_at        timestamp
deleted_at        timestamp        ← ADDED
```

### Suggestions Table
```
id                int(11)
market_id         int(11)
user_id           int(11)
content           text
status            enum('pending','approved','rejected')
rejection_reason  text
created_at        timestamp
updated_at        timestamp
deleted_at        timestamp        ← ADDED
```

## Verification Results

✅ All soft delete SELECT queries execute successfully
✅ All soft delete UPDATE queries work correctly
✅ No more "Unknown column 'deleted_at'" errors
✅ Pagination and filtering with deleted records work
✅ Delete.php pages can properly soft-delete records

## Affected Modules

| Module | Feature | Status |
|--------|---------|--------|
| announcements/list.php | Display active announcements | ✅ Fixed |
| announcements/delete.php | Soft delete announcements | ✅ Fixed |
| suggestions/list.php | Display active suggestions | ✅ Fixed |
| suggestions/delete.php | Soft delete suggestions | ✅ Fixed |
| admin/pending_feedback.php | Filter deleted feedback | ✅ Fixed |
| community/list.php | Filter deleted reports | ✅ Fixed |

## Testing

All database queries related to soft deletes have been verified to work correctly:
- WHERE clauses with deleted_at IS NULL operate as expected
- UPDATE statements to set deleted_at = NOW() execute properly
- Filtering and sorting with soft delete logic works

## Notes

- Soft delete preserves data for audit trails and recovery
- Users cannot see soft-deleted content in listings
- Only non-deleted records (deleted_at IS NULL) appear in public views
- Managers can see the complete history if needed (with additional admin queries)

## Migration Completion
- ✅ Database schema updated
- ✅ All references verified
- ✅ All queries tested
- ✅ Temporary scripts cleaned up

---
Status: COMPLETE ✅
Date: March 31, 2026
