# WeCoza Class Management System

A standalone PHP application for managing training classes with full CRUD functionality. Built with Slim Framework 4, PostgreSQL, and Bootstrap 5.

## Features

### MVP (Phase 1) - Create Functionality
- ✅ **Client & Site Management**: Select clients and associated sites
- ✅ **Class Information**: Type, subject, code, duration
- ✅ **Schedule Management**: Start dates, delivery dates, QA visits
- ✅ **Funding & Exams**: SETA funding, exam classes
- ✅ **Staffing**: Agent and supervisor assignment
- ✅ **Local Data Storage**: Reference data stored in local JSON files
- ✅ **PostgreSQL Integration**: Main class data stored in PostgreSQL with JSONB fields
- ✅ **Bootstrap 5 UI**: Modern, responsive interface matching existing design

### Planned (Phase 2) - Update/Maintenance Functionality
- 🔄 **Update Mode**: Separate form for post-creation management
- 🔄 **Schedule Analytics**: Advanced scheduling features
- 🔄 **Quality Management**: Notes and quality tracking
- 🔄 **Staff Changes**: Agent reassignment and history
- 🔄 **File Uploads**: QA reports and documents

## Architecture

### Technology Stack
- **Backend**: PHP 8.1+ with Slim Framework 4
- **Database**: PostgreSQL with JSONB fields
- **Frontend**: Bootstrap 5 with Twig templates
- **Reference Data**: Local JSON files for form dropdowns
- **Authentication**: Session-based (JWT planned for Phase 2)

### Project Structure
```
├── public/                 # Web root
│   └── index.php          # Application entry point
├── src/                   # Application source code
│   ├── Controllers/       # Request handlers
│   ├── Models/           # Data models
│   ├── Repositories/     # Database access layer
│   ├── Services/         # Business logic
│   └── Middleware/       # Request middleware
├── templates/            # Twig templates
├── config/              # Configuration files
├── data/                # Local JSON reference data
├── database/            # Database schema and migrations
├── uploads/             # File upload storage
└── logs/               # Application logs
```

## Installation

### Prerequisites
- PHP 8.1 or higher
- PostgreSQL 12 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone git@github.com:yourdesigncoza/wecoza_html.git
   cd wecoza_html
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Create PostgreSQL database**
   ```sql
   CREATE DATABASE wecoza_classes;
   CREATE USER wecoza_user WITH PASSWORD 'your_password';
   GRANT ALL PRIVILEGES ON DATABASE wecoza_classes TO wecoza_user;
   ```

5. **Initialize database schema**
   ```bash
   psql -U wecoza_user -d wecoza_classes -f database/init.sql
   ```

6. **Create required directories**
   ```bash
   mkdir -p uploads logs data cache/twig
   chmod 755 uploads logs data cache
   ```

7. **Start the application**
   ```bash
   # Using PHP built-in server (development)
   php -S localhost:8000 -t public

   # Or configure your web server to point to the public/ directory
   ```

8. **Access the application**
   - Open http://localhost:8000
   - Default login: admin / admin123 (change in production!)

## Configuration

### Environment Variables (.env)
```env
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_NAME="WeCoza Class Management"

# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=wecoza_classes
DB_USER=wecoza_user
DB_PASS=your_password

# Security
JWT_SECRET=your-secret-key-here
SESSION_SECRET=your-session-secret-here

# File Uploads
UPLOAD_MAX_SIZE=5242880
UPLOAD_PATH=uploads
ALLOWED_FILE_TYPES=pdf,doc,docx,jpg,jpeg,png
```

### Reference Data
The application uses local JSON files for reference data (clients, agents, etc.):
- `data/clients.json` - Client list
- `data/sites.json` - Sites grouped by client
- `data/agents.json` - Available agents
- `data/supervisors.json` - Project supervisors
- `data/learners.json` - Available learners
- `data/setas.json` - SETA organizations
- `data/class_types.json` - Class types
- `data/class_subjects.json` - Subjects by class type
- `data/public_holidays_YYYY.json` - South African public holidays

These files are automatically created with default data on first run.

## Usage

### Creating a New Class

1. Navigate to **Classes > Create New Class**
2. Fill in the 5 main sections:
   - **Client & Site Information**: Select client and site
   - **Class Information**: Type, subject, code, duration
   - **Schedule Information**: Start date, delivery date, QA visits
   - **Funding & Exams**: SETA funding and exam details
   - **Staffing**: Assign agent and supervisor
3. Click **Create Class**

### Form Validation
- Required fields are marked with *
- Client, class type, subject, code, duration, start date, agent, and supervisor are mandatory
- Class codes must be unique
- Business rules are enforced (e.g., SETA must be specified if SETA funded)

### Data Storage
- **Main class data**: Stored in PostgreSQL `classes` table
- **Complex data**: Stored in JSONB fields (learner_ids, schedule_data, etc.)
- **Reference data**: Loaded from local JSON files
- **File uploads**: Stored locally in `uploads/` directory

## API Endpoints

### Web Routes
- `GET /` - Home page
- `GET /classes` - List all classes
- `GET /classes/create` - Create class form
- `POST /classes/create` - Store new class
- `GET /classes/{id}` - View class details
- `GET /classes/{id}/edit` - Edit class form
- `POST /classes/{id}/edit` - Update class

### API Routes (Planned)
- `GET /api/classes` - Get classes (JSON)
- `POST /api/classes` - Create class (JSON)
- `PUT /api/classes/{id}` - Update class (JSON)
- `DELETE /api/classes/{id}` - Delete class

## Database Schema

### Classes Table
```sql
CREATE TABLE classes (
    class_id SERIAL PRIMARY KEY,
    client_id INTEGER,
    site_id INTEGER,
    class_type VARCHAR(50),
    class_subject VARCHAR(255),
    class_code VARCHAR(100) UNIQUE NOT NULL,
    class_duration INTEGER,
    original_start_date DATE,
    seta_funded BOOLEAN DEFAULT FALSE,
    seta VARCHAR(50),
    exam_class BOOLEAN DEFAULT FALSE,
    exam_type VARCHAR(100),
    class_agent INTEGER,
    project_supervisor_id INTEGER,
    
    -- JSONB fields for flexibility
    learner_ids JSONB DEFAULT '[]'::jsonb,
    backup_agent_ids JSONB DEFAULT '[]'::jsonb,
    schedule_data JSONB DEFAULT '{}'::jsonb,
    stop_restart_dates JSONB DEFAULT '[]'::jsonb,
    class_notes_data JSONB DEFAULT '[]'::jsonb,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## Development

### Running Tests
```bash
composer test
```

### Code Quality
```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Static analysis
composer analyse
```

### Adding New Reference Data
1. Create/edit JSON files in `data/` directory
2. Update `LocalDataService` to load the new data
3. Add form fields in Twig templates
4. Update model and validation as needed

## Deployment

### Production Setup
1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Use a proper web server (Apache/Nginx)
3. Enable HTTPS
4. Set up proper database user permissions
5. Configure log rotation
6. Set up backup procedures
7. Change default passwords

### Security Considerations
- Change default admin password
- Use strong JWT secrets
- Implement proper CSRF protection
- Validate and sanitize all inputs
- Use prepared statements (already implemented)
- Set up proper file upload restrictions

## Support

For issues and questions:
1. Check the logs in `logs/app.log`
2. Verify database connectivity
3. Ensure all required directories exist and are writable
4. Check PHP error logs

## License

MIT License - see LICENSE file for details.

## Roadmap

### Phase 2 (Update/Maintenance)
- [ ] Update mode with different form sections
- [ ] File upload functionality
- [ ] Advanced scheduling features
- [ ] Quality management and notes
- [ ] Staff change tracking
- [ ] Export functionality (CSV, PDF, Excel)

### Phase 3 (Advanced Features)
- [ ] JWT authentication
- [ ] Role-based permissions
- [ ] API documentation
- [ ] Advanced reporting
- [ ] Email notifications
- [ ] Calendar integration
