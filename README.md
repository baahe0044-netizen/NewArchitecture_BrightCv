# BrightCV

BrightCV is a database-driven CV and resume builder developed with a custom PHP MVC architecture. It helps users create, manage, preview, and export professional resumes through a structured and maintainable web application.

## Project Status

BrightCV is under active development. Core application areas currently include the landing page, authentication, dashboard, resume builder, template gallery, template preview, and PDF-related workflows.

## Key Features

- User authentication and session-based access control
- Resume creation and management
- Step-by-step resume builder
- Dynamic resume preview
- Resume template selection
- Dashboard statistics and recent CVs
- PDF preview and export workflow
- Profile, account, and help pages
- Responsive user interface
- Layered MVC architecture

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Tailwind CSS
- **Architecture:** Custom MVC, service layer, repository pattern
- **Development environment:** WAMP Server
- **Version control:** Git and GitHub

## Application Architecture

```text
app/
├── Core/
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── Helpers/
├── Middleware/
└── Views/

api/
config/
public/
storage/
database/
docs/
tests/
vendor/
```

### Architecture Responsibilities

- **Controllers** receive requests and coordinate application actions.
- **Services** contain business logic and connect controllers to data access.
- **Repositories** handle database operations and queries.
- **Models** represent application data and domain entities.
- **Views** render the user-facing pages.
- **Core** provides routing, requests, responses, authentication, and shared framework functionality.

## Main Pages

When the project folder is named `LunettiStar` inside WAMP's `www` directory, the main local routes are:

```text
http://localhost/LunettiStar/public/
http://localhost/LunettiStar/public/login
http://localhost/LunettiStar/public/dashboard
http://localhost/LunettiStar/public/resume/builder
http://localhost/LunettiStar/public/templates
```

Adjust the URLs if the project folder has a different name.

## Local Installation

### Requirements

- PHP 8 or later
- MySQL
- Apache
- WAMP, XAMPP, or a comparable local PHP environment
- Composer
- Git

### Setup

1. Clone the repository:

```bash
git clone https://github.com/baahe0044-netizen/NewArchitecture_BrightCv.git
```

2. Move the project into your local web-server directory, for example:

```text
C:\wamp64\www\LunettiStar
```

3. Install PHP dependencies if required:

```bash
composer install
```

4. Create a MySQL database named:

```text
lunettistar_db
```

5. Import the project SQL file from the `database` directory.

6. Update the database configuration in the project's configuration files with your local credentials.

7. Start Apache and MySQL.

8. Open the application:

```text
http://localhost/LunettiStar/public/
```

## Database Areas

The application uses tables for areas including:

- users
- resumes
- personal information
- education
- experience
- skills
- templates
- selected templates
- resume generations
- ATS scores
- AI history
- user activity

## Screenshots

Project screenshots will be added to `docs/screenshots/`.

Recommended screenshots:

- Landing page
- Login page
- Dashboard
- Resume builder
- Template gallery
- Template preview
- PDF preview

## Development Roadmap

- Complete resume-builder integration
- Expand template management
- Improve PDF export consistency
- Add ATS scoring improvements
- Add AI-assisted writing suggestions
- Improve automated testing
- Add a hosted demonstration

## Security

Do not commit database passwords, API keys, session secrets, or production credentials. Use environment variables or local configuration files excluded by `.gitignore`.

## Author

**Emmanuel Baah**  
Backend Software Engineer  
GitHub: [baahe0044-netizen](https://github.com/baahe0044-netizen)

## License

This project is currently provided for portfolio and educational review. No open-source licence has been granted unless a licence file is added later.
