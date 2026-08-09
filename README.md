# Aura Backend

This is the backend API for the Aura project, built with PHP 8.4 and Laravel. It uses Docker (via Laravel Sail) for a seamless development experience across all platforms.

## Requirements

- [Docker](https://docs.docker.com/get-docker/) installed and running.
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop).

## Project Setup

Follow the instructions based on your operating system to start the project.

### Windows (Using WSL2)

1. Ensure you have WSL2 installed and configured.
2. Install Docker Desktop and enable the WSL2 backend integration in its settings.
3. Open your WSL2 terminal (e.g., Ubuntu).
4. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
5. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
6. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
7. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
8. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

### Linux

1. Ensure Docker and Docker Compose are installed.
2. Open your terminal.
3. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
4. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
5. Install composer dependencies using a small Docker container (if you don't have composer locally):
   ```bash
   docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
   ```
6. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
7. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
8. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

### macOS

1. Install Docker Desktop for Mac.
2. Open your terminal.
3. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
4. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
5. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
6. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
7. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## Useful Commands

To run Artisan commands, use Sail:
```bash
./vendor/bin/sail artisan <command>
```

To run tests:
```bash
./vendor/bin/sail artisan test
```

To stop the containers:
```bash
./vendor/bin/sail down
```

---

## Configuración de Stripe y Webhooks

### ¿Es obligatorio configurar el Webhook?

**No para el flujo básico actual en desarrollo.** 
Actualmente la aplicación móvil procesa el pago con Stripe SDK (`PaymentSheet`) y confirma la transacción invocando la API (`POST /api/mobile/payments/pay`), donde el servidor valida sincrónicamente el estado del `PaymentIntent` directamente con los servidores de Stripe.

**Recomendado para:**
- Procesar pagos asíncronos (ej. OXXO, transferencias bancarias, SPEI).
- Recibir notificaciones en tiempo real sobre disputas, reembolsos o fallos diferidos.

### Cómo instalar y ejecutar Stripe CLI para desarrollo local

`./sail` ejecuta contenedores Docker de la aplicación, por lo que la herramienta `stripe` CLI debe instalarse en tu sistema operativo host (WSL2 / Ubuntu / macOS) y no como un comando de Sail.

#### 1. Instalación de Stripe CLI

**Ubuntu / WSL2:**
```bash
curl -s https://packages.stripe.dev/api/security/keypair/stripe-cli-gpg/public | gpg --dearmor | sudo tee /usr/share/keyrings/stripe.gpg > /dev/null
echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/stripe-cli-debian-local stable main" | sudo tee /etc/apt/sources.list.d/stripe.list
sudo apt update
sudo apt install stripe
```

**macOS (Homebrew):**
```bash
brew install stripe/stripe-cli/stripe
```

#### 2. Autenticar Stripe CLI
```bash
stripe login
```
Sigue las instrucciones en pantalla para autorizar la CLI con tu cuenta de desarrollador de Stripe.

#### 3. Escuchar Webhooks y redirigir al entorno local

Para redirigir los eventos recibidos en Stripe hacia tu contenedor local de Laravel Sail:

```bash
stripe listen --forward-to localhost/api/stripe/webhook
```

Al ejecutar el comando, Stripe CLI te entregará un secreto de firma que luce así:
`Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxxxxx`

#### 4. Configurar el Secreto en `.env`

Copia el `whsec_...` generado por la terminal y asignalo en tu archivo `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxx
```

