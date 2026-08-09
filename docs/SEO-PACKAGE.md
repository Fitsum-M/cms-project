# SEO package deviation (GAP.SEO.01)

**Status:** Accepted  
**SRS reference:** §8.4  
**Last reviewed:** 9 August 2026

## Deviation

| Item | SRS specification | Implementation |
|------|-------------------|----------------|
| Package | `spatie/laravel-seo` 3.x | [`ralphjsmit/laravel-seo`](https://github.com/ralphjsmit/laravel-seo) `^1.7` |
| Vendor | Spatie | Ralph J. Smit |

## Rationale

The SRS names **Spatie Laravel SEO 3.x**, but that package is not published on Packagist under that name. The project adopted **`ralphjsmit/laravel-seo`**, which provides the same MVP capabilities required by the SRS:

- Meta title and description tags
- Canonical URL and robots meta
- Open Graph and Twitter Card tags
- JSON-LD structured data (`SchemaCollection`, `ArticleSchema`, `CustomSchema`, etc.)

This deviation was recorded at project bootstrap (IMPLEMENTATION-PHASES F0.3) and is formally accepted for MVP.

## Capability mapping

| SRS requirement | Implementation |
|-----------------|----------------|
| Meta tags on content | `ralphjsmit/laravel-seo` `TagManager` + `App\Models\SeoMetadata` |
| Open Graph fields | `ContentSeoService` inheritance + `HasContentSeo::getDynamicSEOData()` |
| Schema type per content / defaults | `schema_type` column + `SeoDefaultsSettings` + CPT defaults |
| JSON-LD output (§19.10) | `App\Support\Seo\JsonLdSchemaBuilder` → `SEOData::schema` |

## Future migration

If Spatie ships an official `laravel-seo` package with equivalent APIs, migration would touch:

- `config/seo.php` and `SeoMetadata` model
- `HasContentSeo` trait
- `JsonLdSchemaBuilder` adapter layer

No migration is planned for MVP.
