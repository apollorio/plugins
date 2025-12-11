# Apollo Events Manager – Inventário e Segurança

## CPTs e Taxonomias
- CPTs: `event_listing`, `event_dj`, `event_local`, `apollo_event_stat`.
- Taxonomias (em `event_listing`): `event_listing_category`, `event_listing_type`, `event_listing_tag`, `event_sounds`.

## Metas de Post
- Eventos: `_event_title`, `_event_banner`, `_event_video_url`, `_event_start_date/_end_date/_start_time/_end_time`, `_event_location/_country`, `_tickets_ext`, `_cupom_ario`, `_event_dj_ids`, `_event_local_ids`, `_event_timetable`, `_3_imagens_promo`, `_imagem_final`, `_favorites_count`, `_event_co_authors`, `_event_link_*`.
- Locais: `_local_name/_description/_address/_city/_state/_latitude/_longitude/_lat/_lng`, `_local_website/_facebook/_instagram`, `_local_image_1..5`.
- DJs: `_dj_name/_bio/_image`, redes/streams (`_dj_website/_instagram/_facebook/_soundcloud/_bandcamp/_spotify/_youtube/_mixcloud/_beatport/_resident_advisor/_twitter/_tiktok`), mídia (`_dj_set_url/_dj_media_kit_url/_dj_rider_url/_dj_mix_url`), projetos originais 1-3.
- Stats: `_event_id`, `_page_views`, `_popup_views`, `_total_views`, `_daily_views`, `_last_view_date`.
- Extras usados: `_apollo_bookmark_count`, `_apollo_mod_approved/_rejected/_by/_date`, `_apollo_frontend_submission`.

## Opções relevantes
- Versão/flags: `apollo_aprio_version`, `apollo_events_auto_create_eventos_page`, `apollo_events_manager_activated_version`, `apollo_events_manager_missing_core`.
- JWT/REST: `apollo_jwt_secret`, `aprio_rest_api_version`, `aprio_rest_api_app_name/logo/splash`, `aprio_app_branding_settings/_dark`, paleta `aprio_*_color`, `aprio_active_mode`.
- Matchmaking: `enable_matchmaking`, `participant_activation`, `aprio_meeting_*`.
- Unidades: `aprio_weight_unit`, `aprio_dimension_unit`.
- Permissões/licenças: `aprio_rest_allowed_roles`, `<plugin>_licence_key`.

## Endpoints REST carregados (namespace `apollo/v1`)
- Eventos: `GET /eventos`, `GET /evento/{id}`, `GET /categorias`, `GET /locais`, `GET /my-events`.
- Bookmarks: `GET /bookmarks`, `POST /bookmarks/{id}`.
- Dashboard/analytics: `GET|POST /estatisticas`, `GET|POST /likes`, `GET|POST /technotes/{venue_id}`.

## Endpoints AJAX
- Público+priv: `filter_events`, `load_event_single`, `apollo_get_event_modal`, `apollo_record_click_out`, `apollo_submit_event_comment`, `apollo_track_event_view`, `apollo_get_event_stats`, `toggle_favorite`, `apollo_toggle_bookmark`.
- Apenas logado: `apollo_save_profile`, `apollo_mod_approve_event`, `apollo_mod_reject_event`, `apollo_add_new_dj`, `apollo_add_new_local`, `apollo_create_canvas_page`.
- Legado não carregado: favoritos (`favorites_favorite`, `favorites_array`, `favorites_clear`, `favorites_totalcount`, `favorites_list`, `favorites_cookie_consent`) em `modules/favorites/app/Events/RegisterPublicEvents.php`.

## Legados/duplicados
- `modules/rest-api/**` (fork WP Event Manager REST API) não é incluído pelo core atual.
- `modules/favorites/**` (favoritos WP Event Manager) não usado; core usa `includes/ajax-favorites.php` + `includes/class-bookmarks.php`.
- Duplicidades já apontadas em `DUPLICITY-REPORT.md` (shortcodes duplicados e criação de páginas redundante no main file).

## Hardening aplicado
- `apollo-events-manager.php`: timetable validado com `wp_unslash` + sanitização de `time`/`dj`; cupom (`cupom_ario`) desserializado + sanitizado na validação e no salvamento; `_imagem_final` desserializado + sanitizado antes de decidir ID/URL com `esc_url_raw`.
- Lints: sem erros após ajustes.

## Riscos e recomendações
- REST público para telemetria: `POST /apollo/v1/estatisticas` e `POST /apollo/v1/likes` seguem abertos; decidir se exigirão nonce/cap ou throttling.
- `modules/rest-api/*` contém SQL/validações inconsistentes; revisar antes de qualquer reativação.
- `modules/favorites/*` pode ser removido para evitar confusão.
- Padronizar `wp_unslash + sanitize_*` em todas as submissões públicas para consistência total.
- CDN em `includes/admin-dashboard.php` (DataTables/Chart.js) somente se for política aprovada.

