# Apollo Core Language Files

This folder contains translation files (.mo, .po) for the Apollo Core plugin.

## Supported Languages
- en_US (English - United States) - Default
- pt_BR (Portuguese - Brazil)
- es_ES (Spanish - Spain)
- fr_FR (French - France)
- de_DE (German - Germany)
- it_IT (Italian - Italy)
- zh_CN (Chinese - Simplified)
- ja (Japanese)
- ko_KR (Korean)
- ru_RU (Russian)
- ar (Arabic)
- nl_NL (Dutch - Netherlands)

## Strict Mode i18n

When **Strict Mode** is enabled (default), all front-end text is automatically
forced to English regardless of the user's browser language. This uses:

1. **Accept-Language Header Parsing** - Detects user's preferred language
2. **Device Detection** - Identifies mobile/tablet/desktop
3. **LibreTranslate Integration** - Offline translation to English

## Files

- `apollo-core-{locale}.po` - Source translation file (editable)
- `apollo-core-{locale}.mo` - Compiled translation file (used by WordPress)

## Usage

To generate .mo files from .po files:
```bash
msgfmt apollo-core-pt_BR.po -o apollo-core-pt_BR.mo
```
