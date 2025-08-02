# Bilingual Jewelry Platform

A comprehensive bilingual Persian/English jewelry business management web application built with Laravel and Vue.js, fully containerized with Docker.

## Features

- **Bilingual Support**: Seamless switching between Persian (RTL) and English (LTR)
- **Docker-First**: Fully containerized application with Docker Compose
- **Modern Stack**: Laravel 10+ backend with Vue.js 3 + TypeScript frontend
- **Enterprise Features**: Invoicing, inventory management, CRM, and accounting
- **PWA Support**: Progressive Web App capabilities for offline usage
- **Real-time Analytics**: Dashboard with business KPIs and metrics

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Make (optional, for convenience commands)

### Development Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd bilingual-jewelry-platform
   ```

2. **Initial setup**
   ```bash
   make setup
   ```
   
   Or manually:
   ```bash
   cp .env.example .env
   docker-compose build
   docker-compose up -d
   docker-compose exec app composer install
   docker-compose exec frontend npm install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   ```

3. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost/api
   - Full application: http://localhost

### Production Deployment

1. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with production values
   ```

2. **Deploy**
   ```bash
   make prod-build
   make prod-up
   ```

## Architecture

### Services

- **app**: Laravel PHP-FPM application
- **frontend**: Vue.js development server
- **mysql**: MySQL 8.0 database
- **redis**: Redis for caching and queues
- **nginx**: Reverse proxy and web server
- **scheduler**: Laravel task scheduler
- **queue**: Laravel queue workers

### Directory Structure

```
├── docker/                 # Docker configuration files
│   ├── app/               # Laravel container config
│   ├── frontend/          # Vue.js container config
│   ├── nginx/             # Nginx configuration
│   └── mysql/             # MySQL configuration
├── frontend/              # Vue.js application
│   ├── src/               # Source code
│   ├── public/            # Static assets
│   └── package.json       # Dependencies
├── app/                   # Laravel application
├── config/                # Laravel configuration
├── database/              # Migrations and seeders
├── routes/                # API and web routes
├── docker-compose.yml     # Development services
├── docker-compose.prod.yml # Production services
└── Makefile              # Convenience commands
```

## Development

### Available Commands

```bash
make help          # Show all available commands
make build         # Build Docker containers
make up            # Start services
make down          # Stop services
make logs          # View logs
make shell         # Access app container
make test          # Run tests
make clean         # Clean Docker resources
```

### Laravel Commands

```bash
make migrate       # Run migrations
make seed          # Run seeders
make fresh         # Fresh migration with seeding
make cache         # Clear caches
```

### Frontend Development

The frontend runs on port 3000 with hot module replacement enabled.

```bash
docker-compose exec frontend npm run dev
docker-compose exec frontend npm test
```

## Configuration

### Environment Variables

Key environment variables in `.env`:

- `APP_NAME`: Application name
- `APP_URL`: Application URL
- `DB_*`: Database configuration
- `REDIS_*`: Redis configuration
- `DEFAULT_LOCALE`: Default language (en/fa)
- `SUPPORTED_LOCALES`: Supported languages

### Localization

The application supports Persian and English:

- **Persian**: RTL layout, Jalali calendar, Persian numerals
- **English**: LTR layout, Gregorian calendar, standard numerals

Language files are located in:
- Backend: `resources/lang/`
- Frontend: `frontend/src/locales/`

## Testing

### Backend Tests

```bash
make test
# or
docker-compose exec app php artisan test
```

### Frontend Tests

```bash
make test-frontend
# or
docker-compose exec frontend npm test
```

## Deployment

### Production Checklist

1. Configure `.env` for production
2. Set up SSL certificates
3. Configure backup strategy
4. Set up monitoring
5. Configure domain and DNS

### SSL Setup

The production configuration includes Let's Encrypt support:

```bash
# Configure domain in .env
APP_DOMAIN=yourdomain.com

# Deploy with SSL
make prod-up
```

## Backup and Maintenance

### Database Backup

```bash
make backup
```

### Log Management

```bash
# View logs
make logs

# View specific service logs
docker-compose logs -f app
docker-compose logs -f frontend
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please refer to the project documentation or create an issue in the repository.