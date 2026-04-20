# NewPortal

Small PHP + MySQL editor login portal.

## How to Work

1. Run the full DB setup script (database + table + roles + admin seed):

```bash
mysql -u root -P 3306 < setups.sql
```

2. Confirm DB config in `db.php`:
- Host: `127.0.0.1`
- Port: `3307`
- User: `root`
- Password: empty
- Database: `news_portal`

3. Start local PHP server from project folder:

```bash
php -S localhost:8000
```

4. Open in browser:

```text
http://localhost:8000/index.php
```

5. Initial login accounts after setup:
- Admin
  - Username: `admin` (or email `admin@local`)
  - Password: `Admin@12345`

Change the admin password immediately after first login.

## Branches and Commands

Use this simple flow:
- `main`: stable branch
- `feature/<name>`: your task branch


### Start new work

```bash
git checkout main
git checkout main
git checkout -b feature/<branch-name>
```

### Save changes

```bash
git status
git add .
git commit -m "Your commit message"
git push -u origin feature/<branch-name>
```
