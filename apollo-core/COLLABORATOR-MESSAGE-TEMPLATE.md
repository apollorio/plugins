# Collaborator Message Template

Copy and paste this message when inviting collaborators to debug Apollo Core.

---

## Message to Send

```
Hi [Collaborator Name],

I need help debugging Apollo Core on my LocalWP site. Here's everything you need:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🏗️ ENVIRONMENT DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Local Site:
- URL: http://localhost:10004
- WP Root: C:\Users\rafae\Local Sites\1212\app\public
- Plugin Path: C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins\apollo-core

Database (Local MySQL):
- Host: localhost
- Port: 10005
- DB Name: local
- DB User: root
- DB Pass: root
- Adminer URL: http://localhost:10004/adminer (if needed)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚨 CRITICAL RULES (READ FIRST!)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. ONLY EDIT FILES UNDER:
   ✅ wp-content\plugins\apollo-core\**

2. DO NOT EDIT:
   ❌ wp-admin/**
   ❌ wp-includes/**
   ❌ wp-content/themes/**
   ❌ wp-content/plugins/OUTDATED-*/**
   ❌ Any WordPress core files
   ❌ wp-config.php

3. USE A FEATURE BRANCH:
   - git checkout -b fix/your-issue-name
   - Do NOT commit directly to main/master

4. OPEN A PR:
   - Tag me (@rafael) for review
   - Include git diff and description

5. AFTER DEBUGGING:
   - I'll rotate DB credentials
   - Remove any temporary access you created

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎯 WHAT I NEED YOU TO CHECK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Activation Flow:
   - Pages created correctly?
   - CPTs registered?
   - Roles created (apollo, cena-rio, dj)?
   - Options set (apollo_mod_settings)?

2. Canvas Pages:
   - Render with uni.css only (no theme CSS)?
   - Template isolation working?
   - Routes: /feed/, /painel/, /cena/, /id/{ID}

3. REST Endpoints:
   - /wp-json/apollo/v1/health
   - /wp-json/apollo/v1/feed
   - /wp-json/apollo/v1/events
   - /wp-json/apollo/v1/moderation/*
   - Permission callbacks working?

4. Security:
   - Any debug scripts exposed publicly?
   - Files like debug-text.php, db-test.php?
   - If found, secure them immediately

5. Specific Issue:
   [DESCRIBE YOUR SPECIFIC ISSUE HERE]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔧 COMMANDS TO RUN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PowerShell (Windows):
```powershell
# Set WP path
$WP = "C:\Users\rafae\Local Sites\1212\app\public"

# 1. List plugin files
Get-ChildItem "$WP\wp-content\plugins\apollo-core" -Recurse | Select-Object FullName

# 2. Check if plugin is active
wp plugin is-active apollo-core --path="$WP"

# 3. List roles
wp role list --path="$WP"

# 4. Get settings option
wp option get apollo_mod_settings --path="$WP"

# 5. List pages
wp post list --post_type=page --path="$WP" --format=csv --fields=ID,post_title,post_name

# 6. Find debug scripts
Get-ChildItem "$WP\wp-content\plugins" -Recurse -Filter "*debug*.php" | Select-Object FullName
```

Bash (Local Shell):
```bash
WP="/path/to/site/public"

# 1. List plugin files
ls -la "$WP/wp-content/plugins/apollo-core"

# 2. Check if plugin is active
wp plugin is-active apollo-core --path="$WP"

# 3. List roles
wp role list --path="$WP"

# 4. Get settings
wp option get apollo_mod_settings --path="$WP"

# 5. Test REST endpoint
curl -i http://localhost:10004/wp-json/apollo/v1/health

# 6. Find debug scripts
rg "debug-text|db-test" "$WP/wp-content/plugins" || true
```

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 WHAT TO REPORT BACK
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Please reply with:

1. COMMANDS RAN:
   - Copy/paste exact commands you ran
   - Include full output

2. ERRORS FOUND:
   - Copy/paste any error messages
   - Include line numbers if possible
   - Screenshots if helpful

3. CHANGES MADE:
   - Git diff of changes
   - Explanation of what you changed
   - Why you made the change

4. FILES EXAMINED:
   - List of files you looked at
   - Any suspicious code found

5. NEXT STEPS:
   - What else needs to be checked?
   - Any blockers?

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📚 DOCUMENTATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Before you start, read:
- apollo-core/DEVELOPMENT.md (development guidelines)
- apollo-core/README.md (plugin overview)
- apollo-core/CAPABILITIES-COMPLIANCE.md (permissions)
- apollo-core/VERIFICATION-AUDIT-REPORT.md (current status)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔒 SECURITY REMINDER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- This is a LOCAL development site (not production)
- DB credentials shown are LOCAL ONLY
- After debugging, I'll rotate all credentials
- Do NOT commit credentials to Git
- Do NOT push to public repos

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
❓ QUESTIONS?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- Ask before making major changes
- Tag me (@rafael) in PRs
- Use project Slack/Discord for quick questions

Thanks for your help! 🙏
```

---

## Checklist for Collaborator

Copy this checklist and send it to the collaborator:

```
Apollo Core Debugging Checklist

Please check these items and report back:

Environment Setup:
[ ] I can access the Local site (http://localhost:10004)
[ ] I can see apollo-core plugin files
[ ] WP-CLI is working

Plugin Activation:
[ ] Plugin is active (or I activated it)
[ ] No activation errors
[ ] Roles created (apollo, cena-rio, dj)
[ ] Option apollo_mod_settings exists

Canvas Pages:
[ ] Pages with _apollo_canvas meta exist
[ ] Canvas pages render without theme CSS
[ ] Only uni.css loaded on canvas pages

REST API:
[ ] /wp-json/apollo/v1/health returns 200
[ ] /wp-json/apollo/v1/feed returns data
[ ] Permission callbacks work
[ ] Nonce verification working

Security:
[ ] No debug scripts in plugin root
[ ] No debug scripts in wp-content root
[ ] All test scripts in tests/ directory
[ ] No credentials in code

Code Quality:
[ ] No PHP syntax errors
[ ] No linter warnings
[ ] PSR-4 autoloading works

Specific Issue:
[ ] I investigated the reported issue
[ ] I can reproduce the problem
[ ] I identified the root cause
[ ] I have a proposed fix

Deliverables:
[ ] Commands output (copy/pasted)
[ ] Error logs (if any)
[ ] Git diff (if I made changes)
[ ] PR opened (if changes made)
[ ] Documentation updated (if needed)
```

---

## After Debugging - Security Checklist

After the collaborator finishes:

```
Post-Debugging Security Tasks

[ ] Review all changes made by collaborator
[ ] Rotate database credentials:
    - Change DB password in Local
    - Update wp-config.php
[ ] Remove temporary user accounts (if created)
[ ] Revoke API keys/tokens (if created)
[ ] Check debug.log for sensitive data
[ ] Clear debug.log
[ ] Review git history for credentials
[ ] Verify no OUTDATED-* folders were activated
[ ] Run security audit:
    wp plugin verify-checksums --all
[ ] Test all functionality still works
[ ] Document any changes made
```

---

## Template Customization

Replace these placeholders before sending:

- `[Collaborator Name]` - Their actual name
- `[DESCRIBE YOUR SPECIFIC ISSUE HERE]` - Detailed issue description
- `@rafael` - Your actual GitHub/GitLab username
- URLs/paths if different from defaults

---

**Last Updated**: 24/11/2025  
**Apollo Core Version**: 3.0.0

