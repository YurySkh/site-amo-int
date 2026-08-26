# Site–amoCRM Integration

Test assignment: a lead form that creates a contact and a linked deal in amoCRM, enriched with Roistat visit data.

## Project structure

- `frontend/` — static HTML, CSS, and vanilla JavaScript application.
- `backend/` — PHP API and amoCRM integration.

Implementation and deployment instructions will be added as the project evolves.

## Local development

### Frontend

Serve the `frontend/` directory at `http://127.0.0.1:4173` with any static file server.

### Backend

The API requires PHP 8.5 with the `mbstring` extension:

```bash
cd backend
php -S 127.0.0.1:8080 -t public router.php
```

Alternatively, run it with Docker:

```bash
docker build -t site-amo-backend ./backend
docker run --rm -p 8080:8080 \
  -e ALLOWED_ORIGIN=http://127.0.0.1:4173 \
  site-amo-backend
```

The backend exposes these endpoints:

```text
POST http://127.0.0.1:8080/api/leads
GET  http://127.0.0.1:8080/health
```

## amoCRM configuration

This project uses an amoCRM long-lived access token, which is intended for small integrations connected to a specific account. Create `backend/.env` from `backend/.env.example` and provide:

```dotenv
AMOCRM_BASE_URL=https://your-account.amocrm.ru
AMOCRM_ACCESS_TOKEN=your_long_lived_token
```

Never commit `backend/.env` or copy its token into frontend code. On Render, configure the same values as environment variables.

When both amoCRM variables are present, `POST /api/leads` creates a deal and its linked contact through `POST /api/v4/leads/complex`. Without them, the local endpoint stays in `validation_only` mode and performs no external request.

For a local Docker run with amoCRM enabled:

```bash
docker run --rm -p 8080:8080 --env-file backend/.env site-amo-backend
```

Run the dependency-free backend tests with:

```bash
php backend/tests/LeadRequestValidatorTest.php
php backend/tests/AmoCrmPayloadFactoryTest.php
php backend/tests/AmoCrmClientTest.php
```

## Render deployment

The repository includes a `render.yaml` Blueprint for a Docker web service. It:

- builds `backend/Dockerfile` with `backend/` as the Docker context;
- starts the PHP API on Render's `PORT` and binds to `0.0.0.0`;
- checks application health through `GET /health`;
- deploys the `main` branch automatically;
- requests secret environment values during the initial Blueprint setup.

Create a new Blueprint in Render from this GitHub repository and provide these values when prompted:

```dotenv
ALLOWED_ORIGIN=https://your-netlify-site.netlify.app
AMOCRM_BASE_URL=https://your-account.amocrm.ru
AMOCRM_ACCESS_TOKEN=your_long_lived_token
```

Do not add `PORT`: Render provides it automatically. Do not commit production values to `render.yaml` or any `.env` file.

After the first successful deploy, verify:

```text
GET https://your-service.onrender.com/health
```

The expected response is:

```json
{"success":true,"status":"ok"}
```

Finally, set `frontend/config.js` to the deployed backend URL with `/api/leads`. If the Netlify URL changes, update `ALLOWED_ORIGIN` in the Render dashboard.
