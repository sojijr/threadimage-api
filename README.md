# ThreadImage API

## Overview

ThreadImage API is a Laravel-based REST API designed to process and extract contents from Threads posts and profiles. (Formerly - [Threadimage](https://github.com/sojijr/threadimage))

## Features

- Extract Threads post content and images
- Extract profile information and image

## Live Site

[Threadimage API](https://threadimage-api.laravel.cloud)

## Installation

### Prerequisites

Before installing ThreadImage API, ensure you have the following installed on your system:

- **PHP 8.2 or higher**
- **Composer** (PHP dependency manager)
- **SQLite** (or your preferred database)
- **Git** (for cloning the repository)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/sojijr/threadimage-api
   cd threadimage-api
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   ```

5. **Start the development server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`

## API Endpoints

- `POST /api/threads-post` - Extract Threads post content and images
- `POST /api/threads-profile` - Extract profile information and image

## API Documentation

Visit `/api/documentation` when the server is running to view the interactive API documentation powered by Swagger.

## Contributing

Contributions are welcome to ThreadImage API!

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.