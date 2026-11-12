# Website assistant

The floating **Ask Thunder Mounts** assistant is enabled by default. It works without an AI subscription: it searches published content and returns matching website sentences with links. Questions without a matching source are directed to the contact team.

## Configure an optional AI provider

1. Sign in to the admin and open **Settings → AI Assistant** (`/admin/settings?tab=chat`).
2. Select **Website content only**, **Google Gemini**, **xAI Grok**, or **OpenAI**.
3. Enter the chosen provider's API key into its password field, and confirm a model ID available to your account. Suggested model IDs are editable; provider availability and billing can change.
4. Save. A blank key field preserves the saved key. Use the explicit removal checkbox to delete one. You can also hide the assistant using the enable checkbox.

Keys are encrypted in the settings database with the application's existing AES-256-GCM/`APP_KEY` mechanism. They are not populated into admin HTML or sent to the storefront. Back up `APP_KEY` securely: changing it prevents existing encrypted credentials from being decrypted. Do not put provider keys in JavaScript, Git, public environment variables, or a chat message.

**Hosting requirements:** PHP cURL, a valid `APP_KEY`, and outbound HTTPS to the selected provider:

- OpenAI: `api.openai.com/v1/chat/completions`
- Grok: `api.x.ai/v1/chat/completions`
- Gemini: `generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`

The development workspace does not permit outbound calls to these hosts. Provider request formatting, parsing, encryption, and fallback are tested with fake transports; a live call must be checked on the deployment host with the merchant's own credentials.

## Content, privacy, and safeguards

- Sources include active CMS pages, file-backed service/resource pages, journal articles, and active public product descriptions. No reseed or external index is needed after content changes.
- The assistant does not access customer records, private dealer prices, orders, payment details, or account data. It does not place orders or confirm appointments.
- AI mode sends the current question and up to three matching public excerpts to the selected provider. A disclosure appears in the widget; provider privacy/retention terms also apply. Website-only mode makes no AI request.
- Conversation history is kept only in the current browser page, is not persisted in local storage, and is not forwarded as model history. Clear chat removes the displayed conversation.
- Missing keys, timeouts, provider errors, and malformed answers fall back to website excerpts. The UI labels AI-assisted answers and links to source pages; visitors should confirm fitment and current availability with the team.
- CSRF protection, 600-character input limits, bounded response sizes, a 20-second provider timeout, and fixed HTTPS provider endpoints are enforced server-side. Answers are rendered as plain text, not HTML.
- Requests are limited to 12/minute per session and 60/hour per remote IP. Rate files contain only a keyed IP hash, counter, and expiry, under ignored `storage/cache/`. Existing maintenance removes stale cache files. On multiple independent application servers, use a shared rate-limit store or a gateway as well. Configure trusted proxy/edge limits for the hosting environment.
- Set provider spending limits and monitor usage before enabling paid AI traffic. Model-generated answers are not guaranteed to be correct; the contact team remains the source for project-specific advice.

## Regression checks

Against an installed local/test store:

```sh
python3 bin/check-storefront.py http://localhost:8080
php bin/check-chat.php
```

`check-chat.php` refuses production environments, uses fake provider transports, and rolls back its temporary settings changes. It tests retrieval, irrelevant questions, all three request/response formats, encrypted storage, key preservation/removal, settings validation, failure fallback, and secret-free admin rendering.
