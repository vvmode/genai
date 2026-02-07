# Railway Database Configuration

Your app is trying to connect to MySQL database "trustchain", but:
1. Railway provides PostgreSQL by default (not MySQL)
2. The database needs to be properly configured

## Option 1: Use Railway PostgreSQL (Recommended)

### 1. Add PostgreSQL to Railway Project

In Railway dashboard:
- Click "New" → "Database" → "Add PostgreSQL"
- Railway will automatically create these variables:
  - `PGHOST`
  - `PGPORT`
  - `PGDATABASE`
  - `PGUSER`
  - `PGPASSWORD`

### 2. Add Database Configuration Variables

In Railway Variables tab, add:

```env
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}
```

Railway will auto-substitute the `${PGHOST}` etc. with actual values.

## Option 2: Use SQLite (Simpler, no separate database)

In Railway Variables, set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite
```

Note: SQLite data will be lost on redeploy unless you use persistent volumes.

## Current Issue

The error shows:
```
could not find driver (Connection: mysql
```

This means:
1. ❌ `DB_CONNECTION` is set to `mysql` 
2. ❌ MySQL driver wasn't installed (now fixed in Dockerfile)
3. ❌ No MySQL database is available

## Fix Applied

Updated Dockerfile to include all database drivers:
- ✅ `pdo_sqlite` - SQLite support
- ✅ `pdo_pgsql` - PostgreSQL support  
- ✅ `pdo_mysql` - MySQL support

Now you just need to configure the database connection in Railway Variables.

## Recommended Next Steps

1. **Push the updated Dockerfile:**
   ```bash
   git add Dockerfile docker-entrypoint.sh
   git commit -m "fix: Add PostgreSQL and MySQL drivers to Docker"
   git push origin main
   ```

2. **Choose database option:**
   - For production: Use PostgreSQL (Option 1)
   - For testing: Use SQLite (Option 2)

3. **Set environment variables in Railway**

4. **Railway will auto-redeploy** with working database!

## Verify After Fix

Check health endpoint:
```bash
curl https://web-production-ef55e.up.railway.app/api/health/database
```

Should return:
```json
{
  "status": "ok",
  "database": {
    "connected": true,
    "documents_count": 0
  }
}
```
