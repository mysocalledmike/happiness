# One Trillion Smiles

A web application that enables anyone to spread happiness by sending personalized, heartfelt messages to people they appreciate.

## Vision

Bring a smile to 1 trillion faces.

## The Problem

The Internet and social media have made connecting easier, but have had an adverse effect on the depth of our relationships. Social media changes our perception of what real life and relationships look like, leading people to chase things that don't make us happier (status, vanity). This negativity is causing widespread unhappiness.

## Our Hypothesis

People feel happier by knowing they made other people happy.

## How It Works

### For Senders

1. **Sign Up**: Enter your name and email to create an account
2. **Access Dashboard**: Receive your unique dashboard link via email
3. **Create Messages**: Write personalized messages for people you appreciate
4. **Send**: Each recipient gets a direct link to their personal message

### For Receivers

1. **Get Link**: Receive an email with a link to your personal message
2. **View Message**: See the heartfelt message someone wrote just for you
3. **Smile**: Click the smile button to acknowledge you received joy
4. **Pay It Forward**: Optionally send your own messages to spread the happiness

### For Administrators

1. **Monitor Users**: View all user accounts and their activity
2. **Send Reminders**: Nudge users to send more smiles
3. **Manage Accounts**: Reset dashboard links, delete users as needed

## Character Themes

Messages feature adorable Squishmallow character themes:

- **Rosie the Unicorn** - Magical and sweet (pink)
- **Hooty the Owl** - Wise and peaceful (teal)
- **Bruno the Bear** - Warm and comforting (brown)
- **Whiskers the Seal** - Gentle and calm (gray)
- **Penny the Pig** - Cheerful and bright (pink)
- **Lily the Frog** - Fresh and hopeful (green)
- **Buzzy the Bee** - Sunny and energetic (yellow)
- **Panda the Panda** - Classic and timeless (black/white)

## Technical Stack

### Frontend
- **Twig Templates**: Server-side rendering
- **Vanilla JavaScript**: No frameworks, clean DOM manipulation
- **CSS Grid/Flexbox**: Modern responsive layouts

### Backend
- **PHP 8+**: With Slim Framework 4
- **SQLite**: File-based database
- **Resend**: Email delivery service (production)

### Database Tables
- `senders`: User accounts and settings
- `messages`: Individual messages with unique URLs
- `email_notifications`: Tracking sent notifications
- `stats`: Global smile count and metrics

## Getting Started

### Prerequisites
- PHP 8.0 or higher
- Composer

### Installation

1. **Clone and install dependencies**
   ```bash
   git clone <repository-url>
   cd happiness
   composer install
   ```

2. **Initialize database**
   ```bash
   php -r "
   require 'vendor/autoload.php';
   \App\Database::getInstance()->initializeFromSchema('database/schema.sql');
   "
   ```

3. **Start development server**
   ```bash
   php -S localhost:8080 -t public
   ```

4. **Access the app**
   - Homepage: http://localhost:8080
   - Admin: http://localhost:8080/admin
   - Dev Emails: http://localhost:8080/dev/emails

### Development Features

**Email Testing**
- All emails logged to `development_emails.log` in development mode
- View at `/dev/emails` with clickable links
- Clear all test emails with one click

**Admin Tools**
- User management dashboard
- Send reminder emails
- Reset dashboard URLs
- Delete users

## Project Structure

```
happiness/
├── public/                 # Web-accessible files
│   ├── index.php          # Application routes
│   └── assets/            # CSS, JS, images
│       ├── css/style.css
│       ├── js/main.js
│       └── themes/        # Character images
├── src/                   # PHP application code
│   ├── Database.php       # Database layer
│   ├── Config.php         # Configuration
│   └── Services/          # Business logic
│       ├── AdminService.php
│       ├── EmailService.php
│       ├── MessageService.php
│       ├── QuickSendService.php
│       ├── SignupService.php
│       ├── StatsService.php
│       └── ThemeService.php
├── templates/             # Twig templates
│   ├── layout.twig        # Base layout
│   ├── homepage.twig      # Landing page
│   ├── dashboard.twig     # Sender dashboard
│   ├── message.twig       # Message view page
│   ├── admin.twig         # Admin dashboard
│   ├── dev-emails.twig    # Dev email viewer
│   └── stats-modals.twig  # Stats components
└── database/
    └── schema.sql         # Database schema
```

## API Endpoints

### Public
- `GET /` - Homepage
- `POST /api/signup` - Create account
- `GET /confirm/{token}` - Confirm email
- `GET /s/{message_url}` - View message
- `POST /api/messages/{message_url}/smile` - Record smile

### Dashboard
- `GET /dashboard/{dashboard_url}` - Sender dashboard
- `POST /api/dashboard/{dashboard_url}/send` - Send message
- `DELETE /api/messages/{message_id}` - Delete message

### Admin
- `GET /admin` - Admin dashboard
- `POST /api/admin/send-reminder` - Send reminder
- `POST /api/admin/reset-creation` - Reset dashboard URL
- `POST /api/admin/delete-user` - Delete user

### Development
- `GET /dev/emails` - View sent emails
- `POST /api/dev/clear-emails` - Clear email log

## Configuration

Edit `src/Config.php` to customize:

```php
public static function getDomain(): string
{
    return 'onetrillionsmiles.com';
}

public static function getAppName(): string
{
    return 'One Trillion Smiles';
}
```

## Security

- Dashboard URLs are cryptographically secure (32-character hex)
- Message URLs are unique per recipient
- Admin endpoints require authentication
- Development endpoints restricted to localhost
- Email confirmation required for unlimited messaging

---

*Spreading happiness, one smile at a time.*
