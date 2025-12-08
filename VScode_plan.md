STRICT MODE RUN THE PLAN BELOW SMOOTHLY WITH EXTRA ATTENTION!

1. Repository Preparation & Environment

Clone the repository and open it in VS Code with Copilot and Intelephense enabled.

Run composer install (or npm install if needed) to install dependencies.

Run vendor/bin/phpcs with the project’s PHPCS ruleset to ensure the codebase is initially passing. Fix any existing issues with phpcbf before beginning modifications.

2. Dictionary of Mandatory Replacements (pt‑BR)
For REST endpoints, slug names, and printed front‑end text, apply the following translations exactly. Use them consistently across all plugins. Do not modify internal function names or variables unrelated to REST or UI.

memberships → membro
moderation → mod
cena-rio/events → cena-rio/eventos
cena-rio/submit → cena-rio/add
cena-rio/confirm → cena-rio/confirmar
cena-rio/unconfirm → cena-rio/cancelar
feed → explore
like → wow
events (GET) → eventos
/events (POST) → /eventos (POST)
events/{id} → evento/{id}
/events (post as add new event) → /evento/add
(same mapping applies to ./cena-rio/*)
documents → docs (list)
document/{id} → doc/{id} (single)
health → testando
/onboarding/ → /bem-vinde/
/profile/{id}, user/{id}, or id/{id} → id/{id}
favorites → fav
/like → /wow
unions → membro
classifieds → anuncios (listing)
classifieds/{id} → anuncio/{id} (single)
classifieds → anuncio/add (add new)
sign → assinar
verify → verificar
audit → auditar
protocol → protocolo
signatures → assinaturas
builder → fabrica
/builders/stickers → /fabrica/adesivos
/categories → /categorias
locations → locais
locations/{id} or location/{id} → /rio/{id}
analytics → estatisticas
likes → wows
technotes → notas
/attendee-profile → /preferencias
upload-user-file → upload-photo
onboarding → bem-vindo
register → registrar
*/complete/ → */completo
*/verify/ → */verificando
*/request-dm/ → */confirme-dm
*/options/ → */opcoes
*/begin/ → */inicio
*/cancel/ → */cancelar


Routes vs. Labels: When replacing slugs, adjust route definitions in controller classes and any Vue/JS front‑end code. When replacing labels, update translation strings or user‑facing text (__(), _e(), esc_html__(), etc.).

Case‑insensitivity: Treat patterns case‑insensitively (e.g., “Memberships” → “Membro”).

3. Icons Associated with Names

Scan all plugin code for objects or arrays with a name key adjacent to an icon key. For each matching entry:

If name contains “núcleo” or “nucleo”, set icon: 'ri-team-fill'.

If name contains “feed”, “social”, or “apollo-social”, set icon: 'ri-building-3-line'.

If name contains “evento” or “eventos”, set icon: 'ri-calendar-event-line'.

If name contains “classificados”, “anuncios”, or “anuncio”, set icon: 'ri-megaphone-line'.

If name contains “doc”, “documents”, or “doc”, set icon: 'ri-file-text-line'.

If name contains “profile” or “perfil”, set icon: 'ri-user-smile-fill'.

Ensure other icons remain unchanged. Do not alter the code’s logical flow; only adjust the icon value.

4. Search & Replace Process

Use Copilot’s search or VS Code’s “Search in files” (Ctrl+Shift+F) to locate each dictionary term in the ./wp-content/plugins directory.

For each match:

a. Identify context – Determine if it’s a REST route/path, a front‑end label, or an unrelated code string.

b. Apply translation – Replace the term only if it relates to endpoint paths, slug names, or user‑facing text. Adjust route definitions (e.g. register_rest_route('apollo/v1','/memberships', ...) becomes /membro).

c. Update translations – For printed text, change the string or update translation files, ensuring __() calls reference the correct text domain and language. Use pt‑BR translation strings except where explicitly stated (the DJ page remains in English).

d. Review for side‑effects – Make sure the replacement does not alter variable names, class names, or function names that are not part of the UI or API.

5. Complex Endpoint Updates

Some routes include HTTP methods (e.g. POST to /events) or parameters (e.g. /events/{id}). When updating:

Mirror the existing structure: /events → /eventos; /events/{id} → /evento/{id}.

For POST routes used to add resources, map to “/evento/add” (or similar) while preserving the method.

For grouped endpoints under ./cena-rio/*, apply the same renaming logic to both list and single endpoints (e.g. /cena-rio/events → /cena-rio/eventos, /cena-rio/submit → /cena-rio/add).

6. Onboarding & Profile Paths

Update onboarding pages and API endpoints from /onboarding/ to /bem-vinde/ (or /bem-vindo/, depending on context).

Consolidate multiple profile endpoints (/profile/{id}, /user/{id}, /id/{id}) into the single canonical form /id/{id}. Update all references accordingly, including links in templates and JS.

7. Translation & Internationalization

Ensure all front‑end text is translated to pt‑BR. Use WordPress translation functions (__(), _e(), esc_html__()) with the correct text domain.

Exclude the DJ promotional page (keep its text in English). Verify that translation updates do not affect this page.

If a term appears in both translation calls and code (e.g., array keys), update only the user‑facing portions.

8. Testing & Static Analysis

After making changes, run:

vendor/bin/phpcs
vendor/bin/phpcbf   # if needed to fix whitespace and formatting


and address any PHPCS warnings or errors. Adhere to WordPress coding standards for consistency.

Test each plugin in a local WordPress environment:

Access new endpoints in the browser or via wp rest to confirm correct operation.

Click through front‑end pages to verify that labels, buttons, and menus display Portuguese text as defined.

Confirm that the DJ page still displays in English and that no routes are broken.

9. Documentation & Version Control

Document all changes in a CHANGELOG or commit messages, noting that route and slug names were updated to pt‑BR.

Verify that version bump (if any) matches SemVer guidelines.

10. Additional Notes

Changes should be additive or modifications only—do not remove existing functionality or data structures.

If in doubt about replacing a specific occurrence, cross‑check against route definitions and translation contexts.

Focus exclusively on the code under ./wp-content/plugins/*; do not modify core WordPress files.