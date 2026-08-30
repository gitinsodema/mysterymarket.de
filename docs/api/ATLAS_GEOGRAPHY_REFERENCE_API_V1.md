# ATLAS Geography Reference API v1 — MysteryMarket Integration

Status: approved shared INSODEMA geography contract

Consumer: `MYSTERYMARKET`

Provider: ATLAS (`atlas.insodema.com`)

## Purpose

MysteryMarket should consume reusable geographic master/reference data from ATLAS instead of maintaining independent country, administrative-unit, postal-area, locality, or street master lists.

Canonical reference chain:

`Country -> Administrative Unit -> Postal Area -> Locality -> Street`

The hierarchy is intentionally generic. MysteryMarket must not hard-code country-specific assumptions such as "every country has Bundesländer" or "one postal code always maps to exactly one city".

## Authentication

ATLAS uses server-to-server Bearer authentication. MysteryMarket has its own isolated product credential and must not reuse the SHOPPERMATCH token.

Required headers:

```http
Authorization: Bearer <MYSTERYMARKET_ATLAS_TOKEN>
X-INSODEMA-Product: MYSTERYMARKET
X-Request-ID: <unique request id>
Accept: application/json
```

Optional tracing header:

```http
X-Correlation-ID: <correlation id>
```

The ATLAS-side deployment secret is configured under:

```env
ATLAS_API_MYSTERYMARKET_TOKEN=...
```

MysteryMarket should keep its corresponding credential in its own deployment secret/environment configuration. Never commit the token to Git.

Current scope:

`geography:read`

ATLAS validates product identity, token and scope together. A SHOPPERMATCH token combined with `X-INSODEMA-Product: MYSTERYMARKET` is invalid by design.

## API base

```text
https://atlas.insodema.com/api/v1
```

## Stable identity rule

MysteryMarket SHOULD persist ATLAS IDs as canonical references. Human-readable names and codes may additionally be stored as snapshots for display, audit and historical readability.

Recommended profile/address representation:

```json
{
  "country_code": "DE",
  "administrative_unit_id": "atlas:admin:DE:NW",
  "postal_area_id": "atlas:postal:DE:50667",
  "postal_code": "50667",
  "locality_id": "atlas:locality:DE:<stable-id>",
  "locality_name": "Köln",
  "street_id": "atlas:street:DE:<stable-id>",
  "street_name": "Hohe Straße"
}
```

The ATLAS ID is the reference identity. Display names may change without changing the logical entity.

## Response contract

Successful responses use:

```json
{
  "success": true,
  "data": {},
  "meta": {
    "request_id": "...",
    "correlation_id": "..."
  }
}
```

Errors use:

```json
{
  "success": false,
  "error": {
    "code": "...",
    "message": "..."
  },
  "meta": {
    "request_id": "...",
    "correlation_id": "..."
  }
}
```

MysteryMarket should log the returned `request_id` / `correlation_id` for cross-product troubleshooting.

## Endpoints

### Countries

```http
GET /api/v1/countries
```

Returns active countries available through ATLAS.

```http
GET /api/v1/countries/{countryCode}
```

Returns one country by ISO 3166-1 alpha-2 code, e.g. `DE`.

### Administrative units

```http
GET /api/v1/countries/{countryCode}/subdivisions
```

Returns active administrative units for a country. Depending on the country these can represent Bundesländer, Kantone, Regions, Provinces or comparable units.

```http
GET /api/v1/subdivisions/{atlasId}
```

Returns one administrative unit by ATLAS ID.

### Postal areas

```http
GET /api/v1/subdivisions/{atlasId}/postal-areas
```

Returns postal areas related to an administrative unit.

```http
GET /api/v1/postal-areas/{countryCode}/{postalCode}
```

Returns a postal reference by country and postal code.

```http
GET /api/v1/postal-areas/{atlasId}
```

Returns a postal area by stable ATLAS ID.

Geometry is deliberately separate from ordinary reference-data requests:

```http
GET /api/v1/postal-areas/{atlasId}/geometry
```

### Localities

```http
GET /api/v1/localities?country_code=DE&postal_code=50667&q=Kö&limit=20
```

`country_code` is required. `postal_code`, `q` and `limit` are filters.

```http
GET /api/v1/localities/{atlasId}
```

Returns one locality by ATLAS ID.

Locality is an explicit reference level because international postal-code-to-city relationships are not universally 1:1.

### Streets

```http
GET /api/v1/streets?country_code=DE&postal_code=50667&locality_id=<atlas-locality-id>&q=Ho&limit=20
```

Use `locality_id` when available. A legacy `city` filter may remain available for compatibility, but new MysteryMarket code should prefer the stable locality reference.

Street coverage is source/country dependent. Germany currently has centralized street-reference coverage. Missing street coverage must be treated as a supported state, not as an application error.

### Coordinate containment

```http
POST /api/v1/geo/containment
```

Resolves a coordinate to available geographic references such as administrative units and postal areas when suitable selectable geometry exists.

## Recommended MysteryMarket contact/profile flow

For normal address entry:

1. Load country options from ATLAS.
2. Load the relevant administrative units when useful for the selected country/UX.
3. Accept/select postal code and resolve it through ATLAS.
4. Load localities filtered by country + postal code.
5. Autocomplete streets using country + postal code + locality ID + typed prefix.
6. Store returned ATLAS IDs plus readable snapshots.

Example form progression:

`Deutschland -> Nordrhein-Westfalen -> 50667 -> Köln -> Hohe Straße`

Do not download national street tables into the browser. Use bounded server/API autocomplete calls.

## Recommended MysteryMarket database fields

For a reusable profile/address model, use nullable fields where coverage may not exist:

```text
country_code
administrative_unit_atlas_id
postal_area_atlas_id
postal_code
locality_atlas_id
locality_name
street_atlas_id
street_name
house_number
```

`country_code` and readable values may be kept for display/search. The `*_atlas_id` values are the canonical cross-product references.

Do not add foreign-key constraints from the MysteryMarket database directly to the ATLAS database. These are external reference IDs across service boundaries.

## Fallback behavior

ATLAS coverage is intentionally quality-controlled and can differ by country. MysteryMarket must support controlled fallback when a reference level is unavailable.

Recommended behavior:

- Country: ATLAS reference required where the form is ATLAS-backed.
- Administrative unit: optional where not applicable or not required by UX.
- Postal code: preserve user input; attach an ATLAS postal ID when resolved.
- Locality: prefer ATLAS ID; allow validated/free-text snapshot if coverage is unavailable.
- Street: prefer ATLAS autocomplete/ID; allow free-text street input when coverage is unavailable.

Never fabricate an ATLAS ID for fallback text.

## Geometry quality rule

ATLAS distinguishes reference identity from geometry quality. Approximate or synthetic map polygons can remain in ATLAS for provenance/analysis but are not automatically selectable.

MysteryMarket must only use geometry returned/approved by ATLAS for selectable geographic profile/work-area features. It must not infer that every postal identity has an authoritative selectable polygon.

This rule is especially important for markets where postal boundaries are not officially published as polygons.

## Caching

Reasonable short-lived caching of reference lists is allowed in MysteryMarket, but ATLAS remains the system of record.

Recommended approach:

- countries: cache longer (e.g. hours/day)
- subdivisions: cache moderately
- postal/locality lookup: cache by query where useful
- street autocomplete: short cache or no persistent cache
- do not create a separately maintained MysteryMarket geography master dataset

Cache invalidation should be TTL/version driven rather than assuming reference data never changes.

## Security requirements

- Keep the Bearer token server-side only.
- Never expose the ATLAS credential to browser JavaScript.
- Browser/frontend requests should go through MysteryMarket's backend when authentication is required.
- Never commit credentials to Git.
- Use a unique `X-Request-ID` per server-to-server request.
- Treat HTTP `401` as authentication/configuration failure and `403` as insufficient scope.

## Initial smoke tests

Country list:

```bash
curl -sS \
  -H "Authorization: Bearer $MYSTERYMARKET_ATLAS_TOKEN" \
  -H "X-INSODEMA-Product: MYSTERYMARKET" \
  -H "X-Request-ID: mysterymarket-country-001" \
  -H "Accept: application/json" \
  "https://atlas.insodema.com/api/v1/countries"
```

Köln locality lookup:

```bash
curl -sS \
  -H "Authorization: Bearer $MYSTERYMARKET_ATLAS_TOKEN" \
  -H "X-INSODEMA-Product: MYSTERYMARKET" \
  -H "X-Request-ID: mysterymarket-locality-001" \
  -H "Accept: application/json" \
  "https://atlas.insodema.com/api/v1/localities?country_code=DE&postal_code=50667&q=Kö&limit=20"
```

Street lookup after a locality ID has been returned:

```bash
curl -sS \
  -H "Authorization: Bearer $MYSTERYMARKET_ATLAS_TOKEN" \
  -H "X-INSODEMA-Product: MYSTERYMARKET" \
  -H "X-Request-ID: mysterymarket-street-001" \
  -H "Accept: application/json" \
  "https://atlas.insodema.com/api/v1/streets?country_code=DE&postal_code=50667&locality_id=<LOCALITY_ID>&q=Ho&limit=20"
```

## Integration acceptance criteria

MysteryMarket integration is complete when:

- its own ATLAS Bearer credential is configured server-side;
- the `MYSTERYMARKET` product header is used on every ATLAS request;
- country/admin/postal/locality/street data is consumed from ATLAS rather than independently maintained lists;
- stable ATLAS IDs are persisted where references are available;
- street/locality fallback is supported without inventing IDs;
- credentials are never exposed client-side;
- ATLAS request/correlation IDs are available in logs;
- geometry-based selection uses only ATLAS-selectable geometry.

## Ownership

ATLAS owns geographic reference identity, provenance, source quality and the shared API contract.

MysteryMarket owns its form/profile UX, validation rules, local snapshots, fallback behavior and the association of ATLAS references with MysteryMarket business entities.

Consumer-specific changes should not fork the ATLAS geography model. If MysteryMarket needs a reusable geographic capability not yet present, extend the shared ATLAS v1 contract in a backward-compatible way whenever possible.
