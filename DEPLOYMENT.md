# Deployment Guide - Dreamhost

This guide explains how to deploy the Happiness application to your Dreamhost server.

## Prerequisites

1. **SSH Access**: You need SSH access to your Dreamhost server
2. **SSH Key Setup**: For seamless deployment, set up SSH key authentication (optional but recommended)
3. **Server Requirements**:
   - PHP 8.0 or higher
   - Apache with mod_rewrite enabled
   - Composer (will be installed automatically if not present)

## First-Time Setup

### 1. Configure Your Deployment Settings

Copy the example configuration file and fill in your details:

```bash
cp .deploy-config.example .deploy-config
```

Edit `.deploy-config` with your actual server details:

```bash
# Example for Dreamhost
SSH_USER="your-dreamhost-username"
SSH_HOST="iad1-shared-a1-12.dreamhost.com"  # Your Dreamhost server
REMOTE_PATH="/home/your-username/happiness.mikesorvillo.com"
GIT_BRANCH="trillion-refactor"  # or "main"
```

**Important**: `.deploy-config` is in `.gitignore` and will never be committed to version control.

### 2. Set Up SSH Key Authentication (Recommended)

To avoid entering your password on every deployment:

```bash
# Generate SSH key if you don't have one
ssh-keygen -t ed25519 -C "your_email@example.com"

# Copy your public key to Dreamhost
ssh-copy-id your-username@your-server.dreamhost.com
```

### 3. Prepare Your Dreamhost Server

1. Log into Dreamhost Panel
2. Navigate to **Domains** → **Manage Domains**
3. Ensure `happiness.mikesorvillo.com` points to the correct directory
4. Verify PHP 8.0+ is enabled for the domain

## Deploying

Once configured, deployment is a single command:

```bash
./deploy.sh
```

The script will:
1. ✅ Sync all files to the server (excluding vendor, database, logs)
2. ✅ Install PHP dependencies with Composer
3. ✅ Initialize the database if it doesn't exist
4. ✅ Set proper file permissions
5. ✅ Verify the deployment

### What Gets Deployed

**Included:**
- All source code (`src/`, `public/`, `templates/`)
- Configuration files (`composer.json`, `.htaccess`)
- Database schema (`database/schema.sql`)
- Assets (CSS, JS, images)

**Excluded (automatically):**
- `vendor/` (rebuilt on server)
- `database/*.db` (preserves production data)
- `.git/` (version control files)
- Log files and temporary files
- Development files

## Deployment Output Example

```
🚀 Deploying Happiness to Dreamhost...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📍 Server: username@server.dreamhost.com
📁 Path: /home/username/happiness.mikesorvillo.com
🌿 Branch: trillion-refactor
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📦 Step 1: Syncing files to server...
✅ Files synced successfully

📦 Step 2: Installing dependencies...
✅ Dependencies installed

🗄️  Step 3: Setting up database...
✅ Database initialized

🔒 Step 4: Setting permissions...
✅ Permissions set

🔍 Step 5: Verifying deployment...
✅ Core files present
✅ Vendor directory exists
✅ Database directory exists

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Deployment completed successfully!
```

## Post-Deployment Verification

After deploying, verify your site is working:

1. **Homepage**: https://happiness.mikesorvillo.com
2. **Test signup**: Create a test account
3. **Admin panel**: https://happiness.mikesorvillo.com/admin
4. **Check email functionality**: Emails should be sent (not logged like in development)

## Troubleshooting

### Error: "Composer not found"

If Composer isn't available on your server, the script will attempt to install it automatically. If this fails:

```bash
# SSH into your server
ssh your-username@your-server.dreamhost.com

# Navigate to your app directory
cd happiness.mikesorvillo.com

# Install Composer locally
curl -sS https://getcomposer.org/installer | php

# Run composer install
php composer.phar install --no-dev --optimize-autoloader
```

### Error: "500 Internal Server Error"

1. Check PHP version: Must be 8.0 or higher
2. Verify `.htaccess` is enabled
3. Check error logs:
   ```bash
   ssh your-username@your-server.dreamhost.com
   cd happiness.mikesorvillo.com
   tail -f error.log
   ```

### Error: "Database is locked" or "Permission denied"

```bash
# SSH into your server
ssh your-username@your-server.dreamhost.com
cd happiness.mikesorvillo.com

# Fix database permissions
chmod 755 database
chmod 666 database/happiness.db
```

### Database Migration Needed

If you've updated `database/schema.sql` and need to reset the production database:

⚠️ **WARNING**: This will delete all production data!

```bash
# SSH into your server
ssh your-username@your-server.dreamhost.com
cd happiness.mikesorvillo.com

# Backup existing database
cp database/happiness.db database/happiness.db.backup

# Delete and reinitialize
rm database/happiness.db
php -r "require 'vendor/autoload.php'; \App\Database::getInstance()->initializeFromSchema('database/schema.sql');"
```

## Updating Configuration

If you need to change the domain or app name after deployment:

1. Edit `src/Config.php` locally
2. Run `./deploy.sh` to deploy changes
3. Configuration changes take effect immediately (no restart needed)

## Rolling Back

If a deployment causes issues:

```bash
# Option 1: Deploy a previous branch/commit
git checkout <previous-commit>
./deploy.sh

# Option 2: SSH and restore from backup
ssh your-username@your-server.dreamhost.com
cd happiness.mikesorvillo.com
# Restore database if needed
cp database/happiness.db.backup database/happiness.db
```

## Continuous Deployment

For automated deployments on every git push, consider setting up:
- GitHub Actions
- GitLab CI/CD
- Or a webhook to trigger `./deploy.sh`

This is currently a manual deployment process but can be automated if needed.

## Support

For deployment issues:
1. Check the troubleshooting section above
2. Review Dreamhost documentation for PHP hosting
3. Verify server requirements are met
4. Check application logs on the server

---

**Note**: This deployment script uses `rsync` to sync files, which preserves the production database and logs while updating code. Each deployment is incremental and fast.
