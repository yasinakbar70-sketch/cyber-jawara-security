# Jawara Web Shield AI - Translation

This directory contains translation files for the Jawara Web Shield AI plugin.

## Available Languages

Currently, the plugin is available in:
- English (default)

## Contributing Translations

If you'd like to contribute translations for other languages:

1. Use a tool like [Poedit](https://poedit.net/) to create `.po` and `.mo` files
2. The text domain is: `jawara-web-shield-ai`
3. Place your translation files here using the naming convention:
   - `jawara-web-shield-ai-{locale}.po`
   - `jawara-web-shield-ai-{locale}.mo`

Example:
- Indonesian: `jawara-web-shield-ai-id_ID.po` and `jawara-web-shield-ai-id_ID.mo`
- Spanish: `jawara-web-shield-ai-es_ES.po` and `jawara-web-shield-ai-es_ES.mo`

## Translatable Strings

All user-facing strings in the plugin use WordPress i18n functions:
- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `esc_html__()` - Returns escaped translated string
- `esc_html_e()` - Echoes escaped translated string

## Resources

- [WordPress i18n Documentation](https://developer.wordpress.org/plugins/internationalization/)
- [Poedit - Translation Editor](https://poedit.net/)
