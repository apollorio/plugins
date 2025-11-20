# Apollo Events Manager - Developer Guide
## TODO 134: Development guide

### Adding New CPTs

1. Create CPT class in `includes/post-types.php`
2. Register with `register_post_type()`
3. Add metaboxes in `includes/admin-metaboxes.php`
4. Create templates in `templates/`

### Extending Functionality

Use hooks and filters:
- `apollo_events_before_event_card` - Before event card
- `apollo_events_after_event_card` - After event card
- `apollo_events_event_card_classes` - Filter card classes

### Best Practices

- Always use `esc_*` functions for output
- Use `sanitize_*` functions for input
- Check nonces for AJAX requests
- Use `wp_cache_*` for performance

### Troubleshooting

- Check `APOLLO_DEBUG` constant
- Review error logs
- Verify asset loading order

