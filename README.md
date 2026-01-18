# AGRIVISION - Crop Health Monitoring Platform

A comprehensive web-based platform for farmers to monitor crop health, manage fields and greenhouses, access AI-powered agricultural advice, and connect with agricultural experts.

## Features

### User (Farmer) Features

1. **Authentication**
   - Farmer signup with name, mobile number, and password
   - Secure login and logout
   - Language selection (10 Indian languages supported)

2. **Dashboard**
   - Live weather display (temperature, rainfall, humidity, wind speed)
   - Government schemes section with attractive cards
   - Statistics overview (fields, greenhouses, appointments, alerts)

3. **My Field**
   - Field management with multiple zones
   - Zone-wise health analysis with pie charts
   - Crop health metrics (healthy, stressed, deficient)
   - Water requirement, pest risk, and nutrition analysis
   - Farm report upload and viewing
   - Interactive bar charts for detailed risk analysis

4. **Krishi Mandi**
   - Crop prediction based on sunlight, water, and soil quality
   - Pesticide information search
   - Marketplace for agricultural products
   - Location-based supplier and seller system

5. **My Greenhouse**
   - Real-time monitoring (temperature, humidity, soil moisture)
   - Control systems for irrigation, ventilation, and cooling
   - Visual indicators (ON/OFF status)
   - Smart alerts for abnormal conditions

6. **AI Decision Support**
   - AI-powered chat interface
   - Queries on crop health, fertilizers, pests, and weather
   - Query history tracking
   - Quick question suggestions

7. **Appointments**
   - Book appointments with agricultural experts
   - View appointment history and status
   - Contact information management

8. **Profile**
   - Update personal information
   - Change preferred language
   - View account details
   - Contact support information

### Admin Features

1. **Admin Dashboard**
   - Overview statistics (farmers, fields, greenhouses, appointments)
   - Recent farmers list
   - Pending appointments
   - AI query tracking

2. **Farmer Management**
   - View all registered farmers
   - Manage farmer profiles and data

3. **Field & Crop Management**
   - Monitor all fields and zones
   - View crop health analytics
   - Analyze farm reports

4. **Greenhouse Management**
   - Monitor greenhouse parameters
   - View alerts and system status

5. **Government Schemes Management**
   - Add and update schemes
   - Control visibility on farmer dashboard

6. **Marketplace Management**
   - Manage crop prediction data
   - Add pesticide information
   - Monitor suppliers and products

7. **Appointment Management**
   - View and manage appointments
   - Update appointment status

8. **AI Query Management**
   - Monitor farmer queries
   - Review AI responses
   - Improve knowledge base

## Technology Stack

- **Frontend**: PHP
- **Backend**: PHP (PDO)
- **Database**: MySQL
- **Charts**: Chart.js
- **Icons**: Font Awesome 6.4
- **Styling**: Custom CSS with Material Design inspiration

## Installation

### Prerequisites

- XAMPP/WAMP/MAMP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   cd "c:\xampp\htdocs\AGRIVISION 2"
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `agrivision_db`
   - Import the SQL file: `database/schema.sql`

3. **Configure Database**
   - Edit `config/config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'agrivision_db');
   ```

4. **Create Admin User**
   Run this SQL in phpMyAdmin to create an admin account:
   ```sql
   INSERT INTO users (name, mobile, password, role, status) 
   VALUES ('Admin', '9999999999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
   ```
   Default password: `password`

5. **Access the Application**
   - Farmer Login: http://localhost/AGRIVISION%202/user/login.php
   - Admin Login: http://localhost/AGRIVISION%202/admin/login.php
   - Home Page: http://localhost/AGRIVISION%202/

## Project Structure

```
AGRIVISION 2/
├── admin/                  # Admin panel
│   ├── login.php
│   ├── dashboard.php
│   ├── farmers.php
│   ├── fields.php
│   ├── greenhouses.php
│   ├── schemes.php
│   ├── marketplace.php
│   ├── appointments.php
│   ├── ai-queries.php
│   └── logout.php
├── user/                   # User (farmer) panel
│   ├── login.php
│   ├── signup.php
│   ├── language.php
│   ├── dashboard.php
│   ├── my-field.php
│   ├── add-field.php
│   ├── krishi-mandi.php
│   ├── crop-prediction.php
│   ├── my-greenhouse.php
│   ├── add-greenhouse.php
│   ├── ai-support.php
│   ├── appointments.php
│   ├── profile.php
│   ├── schemes.php
│   ├── notifications.php
│   ├── upload-report.php
│   ├── view-report.php
│   └── logout.php
├── config/                 # Configuration files
│   ├── config.php
│   └── database.php
├── database/               # Database schema
│   └── schema.sql
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── index.php               # Landing page
└── README.md              # This file
```

## Features Implementation

### Responsive Design
- Mobile-first approach
- Bottom navigation for mobile devices
- Sidebar navigation for desktop
- Responsive grid layouts
- Dark/Light mode support

### Security
- Password hashing (bcrypt)
- Session management
- SQL injection prevention (PDO prepared statements)
- Input validation and sanitization

### User Experience
- Material Design inspired UI
- Smooth transitions and animations
- Interactive charts and graphs
- Real-time status indicators
- Intuitive navigation

## Database Schema

The application uses the following main tables:
- `users` - User accounts (farmers, admins, analysts)
- `fields` - Field information
- `field_zones` - Field zones with health metrics
- `crop_analysis` - Detailed crop analysis data
- `farm_reports` - Uploaded farm reports
- `weather_data` - Weather information
- `government_schemes` - Government agricultural schemes
- `ai_queries` - AI chat queries and responses
- `crops` - Crop information database
- `pesticides` - Pesticide information
- `suppliers` - Marketplace suppliers
- `products` - Marketplace products
- `greenhouses` - Greenhouse data
- `greenhouse_alerts` - Greenhouse alerts
- `appointments` - Appointment bookings
- `notifications` - User notifications

## API Integration Ready

The platform is designed to integrate with:
- Weather API (OpenWeatherMap, etc.)
- SMS/Alert API (Twilio, etc.)
- AI/ML systems for advanced crop analysis

## Future Enhancements

- Multi-language UI support
- Advanced AI/ML integration
- IoT sensor integration for real-time data
- Mobile app (React Native/Flutter)
- Payment gateway integration
- Advanced analytics and reporting
- Video consultation with experts

## Support

For support and queries:
- Email: support@agrivision.com
- Phone: 1800-123-4567 (Toll Free)

## License

This project is developed for educational and demonstration purposes.

## Credits

Developed for AGRIVISION - Smart Agriculture Initiative
