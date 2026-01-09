#!/bin/bash

# Happiness - Automated Deployment Script for Dreamhost
# Usage: ./deploy.sh

set -e  # Exit on any error

# Load configuration
if [ -f ".deploy-config" ]; then
    source .deploy-config
else
    echo "❌ Error: .deploy-config file not found"
    echo "Please create .deploy-config with your server details:"
    echo ""
    echo "SSH_USER=\"your-username\""
    echo "SSH_HOST=\"your-host.dreamhost.com\""
    echo "REMOTE_PATH=\"/home/your-username/happiness.mikesorvillo.com\""
    echo "GIT_BRANCH=\"trillion-refactor\"  # or main"
    exit 1
fi

# Validate required variables
if [ -z "$SSH_USER" ] || [ -z "$SSH_HOST" ] || [ -z "$REMOTE_PATH" ]; then
    echo "❌ Error: Missing required variables in .deploy-config"
    echo "Required: SSH_USER, SSH_HOST, REMOTE_PATH"
    exit 1
fi

# Use main as default branch if not specified
GIT_BRANCH=${GIT_BRANCH:-main}

echo "🚀 Deploying Happiness to Dreamhost..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📍 Server: $SSH_USER@$SSH_HOST"
echo "📁 Path: $REMOTE_PATH"
echo "🌿 Branch: $GIT_BRANCH"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Confirm deployment
read -p "Continue with deployment? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Deployment cancelled"
    exit 1
fi

echo ""
echo "📦 Step 1: Syncing files to server..."
rsync -avz --delete \
    --exclude '.git' \
    --exclude 'vendor/' \
    --exclude 'database/*.db' \
    --exclude '*.log' \
    --exclude '.DS_Store' \
    --exclude '.env*' \
    --exclude 'node_modules/' \
    --exclude '.vscode/' \
    --exclude '.idea/' \
    --exclude '*.tmp' \
    --exclude '*.temp' \
    --exclude 'deploy.sh' \
    --exclude '.deploy-config' \
    ./ "$SSH_USER@$SSH_HOST:$REMOTE_PATH/"

if [ $? -eq 0 ]; then
    echo "✅ Files synced successfully"
else
    echo "❌ File sync failed"
    exit 1
fi

echo ""
echo "📦 Step 2: Installing dependencies..."
ssh "$SSH_USER@$SSH_HOST" << ENDSSH
    cd $REMOTE_PATH

    # Determine which composer command to use
    COMPOSER_CMD=""
    if command -v composer &> /dev/null; then
        COMPOSER_CMD="composer"
        echo "Using system composer"
    elif [ -f ~/composer.phar ]; then
        COMPOSER_CMD="php ~/composer.phar"
        echo "Using ~/composer.phar"
    elif [ -f composer.phar ]; then
        COMPOSER_CMD="php composer.phar"
        echo "Using local composer.phar"
    else
        echo "Installing composer locally..."
        curl -sS https://getcomposer.org/installer | php
        if [ -f composer.phar ]; then
            COMPOSER_CMD="php composer.phar"
            echo "Composer installed successfully"
        else
            echo "❌ Failed to install composer"
            exit 1
        fi
    fi

    # Install dependencies
    echo "Installing PHP dependencies..."
    \$COMPOSER_CMD install --no-dev --optimize-autoloader

    if [ \$? -eq 0 ]; then
        echo "✅ Dependencies installed"
    else
        echo "❌ Dependency installation failed"
        exit 1
    fi
ENDSSH

echo ""
echo "🗄️  Step 3: Setting up database..."
ssh "$SSH_USER@$SSH_HOST" << 'ENDSSH'
    cd $REMOTE_PATH

    # Create database directory if it doesn't exist
    mkdir -p database

    # Initialize database if it doesn't exist
    if [ ! -f "database/happiness.db" ]; then
        echo "Initializing new database..."
        php -r "
        require 'vendor/autoload.php';
        \App\Database::getInstance()->initializeFromSchema('database/schema.sql');
        echo 'Database initialized successfully\n';
        "
    else
        echo "Database already exists, skipping initialization"
    fi

    # Set proper permissions
    chmod 644 database/happiness.db 2>/dev/null || echo "Database file will be created on first access"
    chmod 755 database
ENDSSH

echo ""
echo "🔒 Step 4: Setting permissions..."
ssh "$SSH_USER@$SSH_HOST" << ENDSSH
    cd $REMOTE_PATH

    # Set directory permissions
    find . -type d -exec chmod 755 {} \;

    # Set file permissions
    find . -type f -exec chmod 644 {} \;

    # Make database writable by web server
    chmod 755 database
    chmod 666 database/happiness.db 2>/dev/null || true

    echo "✅ Permissions set"
ENDSSH

echo ""
echo "🔍 Step 5: Verifying deployment..."
ssh "$SSH_USER@$SSH_HOST" << ENDSSH
    cd $REMOTE_PATH

    # Check if key files exist
    if [ -f "public/index.php" ] && [ -f "composer.json" ] && [ -f "database/schema.sql" ]; then
        echo "✅ Core files present"
    else
        echo "❌ Missing core files"
        exit 1
    fi

    # Check vendor directory
    if [ -d "vendor" ]; then
        echo "✅ Vendor directory exists"
    else
        echo "❌ Vendor directory missing"
        exit 1
    fi

    # Check database directory
    if [ -d "database" ]; then
        echo "✅ Database directory exists"
    else
        echo "❌ Database directory missing"
        exit 1
    fi
ENDSSH

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Deployment completed successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Your site should now be live at:"
echo "   https://happiness.mikesorvillo.com"
echo ""
echo "📋 Next steps:"
echo "   1. Visit your site to verify it's working"
echo "   2. Check that the homepage loads correctly"
echo "   3. Test the signup flow"
echo "   4. Access the admin panel at /admin"
echo ""
echo "💡 Troubleshooting:"
echo "   - If you see errors, check PHP version (needs 8.0+)"
echo "   - Verify .htaccess is enabled on your server"
echo "   - Check database permissions if you get database errors"
echo "   - View logs: ssh $SSH_USER@$SSH_HOST 'cd $REMOTE_PATH && tail -f error.log'"
echo ""
