# Gemini RAG App (Laravel + MongoDB + Google Gemini)

## Setup Instructions

1. **Clone the repository:**
   ```bash
   git clone <your-repo-url>
   cd <your-repo-directory>
   ```

2. **Copy and configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env to set your MongoDB DSN, database, and Gemini API key
   ```
   - Set `MONGODB_DSN` and `MONGODB_DATABASE` for your MongoDB Atlas instance.
   - Set `GEMINI_API_KEY` for your Google Gemini API key.

3. **Install dependencies:**
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install && ./vendor/bin/sail npm run dev
   ```

4. **Run migrations:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

5. **Vectorize your documents:**
   - Place your markdown files in `storage/app/private/<feature-folder>`
   - Run:
     ```bash
     ./vendor/bin/sail artisan vectorize:docs
     ./vendor/bin/sail artisan vectorize:gemini
     ```

## API Usage

### 1. Generate Gemini Chat (using local document embeddings)
```bash
curl -X POST http://localhost/api/gemini/chat \
  -H "Content-Type: application/json" \
  -d '{
    "query": "What is the summary of the groups module?",
    "limit": 5,
    "threshold": 0.7
  }'
```

### 2. Generate Gemini Chat (using Gemini-generated embeddings)
```bash
curl -X POST http://localhost/api/gemini/chat-gemini \
  -H "Content-Type: application/json" \
  -d '{
    "query": "What is the summary of the groups module?",
    "limit": 5,
    "threshold": 0.7
  }'
```

## Troubleshooting & Common Errors

- **MongoDB connection errors:**
  - Ensure your `MONGODB_DSN` and `MONGODB_DATABASE` are correct and your IP is whitelisted in Atlas.
- **Gemini API errors:**
  - Check your `GEMINI_API_KEY` and Google Cloud billing/quota.
  - If you see `Invalid Gemini completion API response`, ensure your context documents have a non-empty `content` field.
- **No context or empty answers:**
  - Make sure your documents were ingested with the original text in the `content` field.
  - Re-run vectorization if you update your ingestion logic.
- **Chunking issues:**
  - Large markdown files are split into ~10,000 character chunks for embedding. Each chunk is stored as a separate document.
- **Route not found:**
  - Make sure you are using the correct API endpoint and HTTP method (POST).

## Notes
- This app uses chunked document storage for RAG best practices.
- All context passed to Gemini is original text, never embeddings.
- You can tune generation parameters in `GeminiEmbeddingService.php` via the `GenerationConfig` array.

## Support
If you encounter issues, check the logs in `storage/logs/laravel.log` for detailed error messages.

---
