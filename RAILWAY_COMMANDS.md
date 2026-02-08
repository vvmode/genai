# Railway Setup Commands

## Create Test Organizations

Run this in Railway terminal:

```bash
php create-test-org.php
```

This creates 3 organizations:
- 2 active (can use for testing)
- 1 pending (for testing inactive org behavior)

**Output example:**
```
✓ Organization 1 Created
  Name: Test University
  Status: active
  API Key: org_abc123xyz...

✓ Organization 2 Created
  Name: Global Certification Institute
  Status: active
  API Key: org_def456uvw...
```

**Copy one of the API keys!**

---

## List Existing Organizations

If organizations already exist or you lost the API keys:

```bash
php list-org-keys.php
```

This shows all organizations with their API keys.

---

## Alternative: Use Artisan Seeder

```bash
php artisan db:seed --class=VerifiedOrganizationSeeder
```

---

## Quick Test

Once you have an API key, test it:

```bash
curl -X GET "https://web-production-ef55e.up.railway.app/api/fraud-detection/verify-key" \
  -H "X-Organization-Key: YOUR_API_KEY_HERE"
```

Expected response:
```json
{
  "success": true,
  "verified": true,
  "organization": {
    "id": 1,
    "name": "Test University",
    "country": "US",
    "status": "active"
  }
}
```

---

## Files

- `create-test-org.php` - Create new test organizations
- `list-org-keys.php` - List all existing organizations and keys
- `database/seeders/VerifiedOrganizationSeeder.php` - Artisan seeder
