# Happiness - Multi-User Goodbye Message Platform

A heartwarming web application that allows people to create personalized goodbye pages when leaving their company, school, or organization. Recipients can look up their personalized farewell messages using their email address.

## 🎯 What It Does

**Happiness** transforms the traditional goodbye email into an interactive, memorable experience. Instead of sending a mass farewell email, users create a personalized goodbye page where colleagues can discover their own custom message by entering their email address.

### Key Features

- **Personalized Messages**: Each recipient gets a unique, heartfelt message
- **Email Lookup**: Recipients find their message by entering their email
- **Squishmallow Themes**: 8 adorable character themes with matching color schemes
- **Waitlist System**: Manages user onboarding and access control
- **Admin Dashboard**: Complete user management and oversight tools
- **Mobile Responsive**: Works beautifully on all devices

## 🏗️ How It Works

### For Message Creators

1. **Join Waitlist**: Users sign up with their email address
2. **Get Invited**: Admins approve users and send creation links
3. **Choose Settings**: Select page URL, main message, and squishmallow theme
4. **Add Messages**: Create personalized messages for specific email addresses
5. **Go Live**: Page becomes publicly accessible at their chosen URL

### For Message Recipients

1. **Visit Page**: Access the goodbye page via the creator's custom URL
2. **Enter Email**: Type their email address in the lookup form
3. **Discover Message**: Receive their personalized farewell message
4. **Feel Loved**: Experience a moment of joy and connection

### For Administrators

1. **Manage Waitlist**: Approve users from waitlist to active status
2. **Monitor Activity**: Track user engagement and page creation
3. **Send Reminders**: Nudge inactive users to complete their pages
4. **User Management**: Reset creation URLs, delete users, view analytics

## 🎨 Squishmallow Themes

The platform features 8 adorable squishmallow character themes:

- **Rosie the Unicorn** - Magical and sweet (pink)
- **Hooty the Owl** - Wise and peaceful (teal)
- **Bruno the Bear** - Warm and comforting (brown)
- **Whiskers the Seal** - Gentle and calm (gray)
- **Penny the Pig** - Cheerful and bright (pink)
- **Lily the Frog** - Fresh and hopeful (green)
- **Buzzy the Bee** - Sunny and energetic (yellow)
- **Panda the Panda** - Classic and timeless (black/white)

Each theme includes a prominent character display and matching background gradient.

## 🛠️ Technical Architecture

### Frontend
- **Twig Templates**: Server-side rendering with clean separation
- **Vanilla JavaScript**: No frameworks, just clean, fast interactions
- **CSS Grid/Flexbox**: Modern responsive layout techniques
- **AJAX**: Smooth form submissions and auto-save functionality

### Backend
- **PHP 8+**: Modern PHP with Slim Framework 4
- **Slim Framework**: Lightweight, fast routing and middleware
- **SQLite Database**: Simple, file-based database for easy deployment
- **Email Service**: Development logging + production email delivery

### Database Schema
- `senders`: User accounts and page settings
- `messages`: Personalized messages for recipients
- `waitlist`: Email signups awaiting approval
- `stats`: Usage analytics and metrics

## 🚀 Getting Started

### Prerequisites
- PHP 8.0 or higher
- Composer (for dependencies)
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone and Setup**
   ```bash
   git clone <repository-url>
   cd happiness
   composer install
   ```

2. **Initialize Database**
   ```bash
   php -r "
   require 'vendor/autoload.php';
   \App\Database::getInstance()->initializeFromSchema('database/schema.sql');
   "
   ```

3. **Start Development Server**
   ```bash
   php -S localhost:8080 -t public
   ```

4. **Access Application**
   - Homepage: http://localhost:8080
   - Admin: http://localhost:8080/admin
   - Dev Emails: http://localhost:8080/dev/emails

### Development Features

#### Email Testing
- All emails are logged to `development_emails.log` in development
- View emails at `/dev/emails` with clickable URLs
- Clear all development emails with one click

#### Admin Tools
- Complete user management dashboard
- Filter users by status (waitlist, inactive, active)
- Bulk actions and individual user controls
- Real-time status updates

## 🎯 User Journey & Status Flow

### User Statuses
1. **Waitlist**: Initial signup, awaiting admin approval
2. **Inactive**: Approved but haven't completed their page
3. **Active**: Page is live and publicly accessible

### Admin Actions
- **Allow User**: Move from waitlist → inactive (sends creation email)
- **Send Reminder**: Nudge inactive users to complete their page
- **Reset Creation URL**: Generate new creation link (invalidates old)
- **Delete User**: Permanently remove user and all data

## 📁 Project Structure

```
happiness/
├── public/                 # Web-accessible files
│   ├── index.php          # Main application entry point
│   └── assets/            # CSS, JS, images
│       ├── css/style.css  # Main stylesheet
│       ├── js/main.js     # Shared JavaScript
│       └── themes/        # Squishmallow character images
├── src/                   # PHP application code
│   ├── Database.php       # Database abstraction layer
│   └── Services/          # Business logic services
│       ├── AdminService.php
│       ├── EmailService.php
│       ├── SenderService.php
│       ├── ThemeService.php
│       └── WaitlistService.php
├── templates/             # Twig template files
│   ├── layout.twig        # Base layout template
│   ├── homepage.twig      # Landing page
│   ├── admin.twig         # Admin dashboard
│   ├── creation.twig      # Message creation interface
│   ├── goodbye.twig       # Public goodbye pages
│   └── dev-emails.twig    # Development email viewer
├── database/              # Database files and schema
│   └── schema.sql         # Database structure definition
└── vendor/                # Composer dependencies
```

## 🔌 API Endpoints

### Public Endpoints
- `GET /` - Homepage with waitlist signup
- `GET /{slug}` - Public goodbye page
- `POST /api/waitlist` - Join waitlist
- `POST /api/{slug}/lookup` - Find personalized message

### Creation Flow
- `GET /create/{creation_url}` - Message creation interface
- `POST /api/create/{creation_url}` - Save messages and settings

### Admin Endpoints
- `GET /admin` - Admin dashboard
- `POST /api/admin/allow-user` - Approve waitlist user
- `POST /api/admin/send-reminder` - Send reminder email
- `POST /api/admin/reset-creation` - Reset creation URL
- `POST /api/admin/delete-user` - Delete user account

### Development Endpoints
- `GET /dev/emails` - View development emails (localhost only)
- `POST /api/dev/clear-emails` - Clear email log (localhost only)

## ⚙️ Configuration

### Domain Configuration
To change the domain name, edit `src/Config.php`:

```php
public static function getDomain(): string
{
    return 'your-domain.com';  // Change this
}

public static function getEmailDomain(): string
{
    return 'your-domain.com';  // Change this for emails
}
```

This will automatically update:
- URL previews in the creation interface
- Email sender addresses 
- All generated links and references

### Application Settings
You can also customize:
```php
public static function getAppName(): string
{
    return 'Your App Name';
}

public static function getAppDescription(): string
{
    return 'Your tagline here';
}
```

## 🎨 Customization

### Adding New Themes
1. Add character images to `public/assets/themes/characters/`
2. Update `ThemeService::getAllThemes()` with new theme data
3. Character images automatically display in theme selector

### Email Templates
Email content is defined in `AdminService.php`:
- `sendCreationEmail()` - "You're off the waitlist" 
- `sendReminderEmail()` - Reminder to complete page
- `sendResetCreationEmail()` - New creation URL

### Styling
- Main styles in `public/assets/css/style.css`
- Responsive design with mobile-first approach
- CSS Grid for complex layouts, Flexbox for alignment

## 📊 Analytics & Monitoring

### Built-in Metrics
- User status distribution
- Time spent in each status
- Page creation completion rates
- Message lookup activity

### Development Monitoring
- All emails logged with timestamps
- Admin action tracking
- Error logging and debugging tools

## 🔒 Security Considerations

### Access Control
- Creation URLs are cryptographically secure (32-character hex)
- Admin endpoints require proper authentication context
- Development features only accessible on localhost

### Data Protection
- No sensitive data stored in plain text
- Email addresses are the only PII collected
- Users can be completely removed with all data

### Email Security
- Development mode prevents accidental email sending
- Production mode uses proper email headers
- All email content is logged for debugging

## 🚧 Future Enhancement Ideas

- **Analytics Dashboard**: Detailed usage metrics and insights
- **Bulk Import**: CSV upload for large message lists
- **Custom Themes**: User-uploaded character images
- **Message Templates**: Pre-written message suggestions
- **Social Sharing**: Share goodbye pages on social media
- **Message Scheduling**: Send time-delayed farewell messages
- **Team Pages**: Collaborative goodbye pages for groups

## 📝 License

This project is a custom application for creating personalized goodbye experiences. All squishmallow character images are used for educational/demonstration purposes.

---

*Built with ❤️ to spread happiness and meaningful connections during life transitions.*