# GigResource — Address Verification & Geolocation

> Developer Feedback v1.1 §7.2–7.5. **Scaffold status:** the free Layer-1 filter
> and the risk-based state machine work today; the PAID provider call and the
> MaxMind/HTML5 geolocation steps are wired but dormant behind a go-live lock
> until Peter provisions the API accounts — same posture as the payments lock.

---

## 1. Risk-based address verification (§7.3–7.5)

Two layers — free first, paid only if needed:

| Layer | What | Cost | Status |
|---|---|---|---|
| **1 — Filter** | Reject blank fields, **PO Boxes**, obviously-fake input; flag likely home addresses | Free, no network | ✅ Live now |
| **2 — Provider** | USPS / Google Address Validation match + (later) KYB business-name match | Paid API | 🔒 Launch-gated |

### Flow (`AddressVerificationService::verifyBusiness`)
1. Already verified → return.
2. Attempts ≥ `max_paid_attempts` (default 2) → **Manual Review Required** (locked).
3. **Layer 1** (`AddressFilter`): PO Box / blank → **Needs Correction** (no attempt spent);
   bad zip / junk → **Needs Correction**; pass (maybe `flag_home`) → Layer 2.
4. **Layer 2**: if `AddressVerificationGuard::paidVerificationEnabled()` is **false**
   (pre-launch) → **Manual Review Required**, *no attempt spent, no money*. If true → call
   the provider:
   - matched → **Address Verified**
   - not matched, attempt 1 → **Needs Correction**; attempt 2 → **Manual Review Required** (lock)

### Status labels (`AddressStatus`)
`Pending` · `Address Verified` · `Business Match Confirmed` · `Needs Correction` ·
`Manual Review Required` · `Registration Blocked` · `Private Client Address — Hidden Until
Booking Confirmed`.

Persisted on `user_profiles`: `address_status`, `address_verification_attempts`,
`address_flagged_home`, `address_verified_at`, `address_locked_at`,
`address_verification_meta`.

### Clients vs professionals (§7.3)
- **Professionals/businesses** — strict, verify before going live (the flow above). Surfaced
  in the professional profile → General tab → *Business Address Verification* card.
- **Clients/residents** — frictionless: sign up with name/email/phone/zip only. Full address
  is collected **only at booking** and stored privately
  (`AddressStatus::PRIVATE_CLIENT_HIDDEN` via `markClientAddressPrivate()`) — never shown to a
  professional until the booking is confirmed.

### PO Box & home-address edge cases (§7.5)
- **PO Boxes** are blocked at the **free** layer (`AddressFilter::isPoBox`) — no paid call.
- **Home addresses** are **flagged, not blocked** (`address_flagged_home`) — the profile card
  notes a business license / state registration may be requested.

---

## 2. Hybrid geolocation (§7.2)

`GeolocationService::guessState()` layers signals cheapest-first:

| Step | Signal | Status |
|---|---|---|
| 1 | Session/cookie (`rememberState`, set at signup from the zip→state map) | ✅ Live |
| 3a | Cloudflare edge headers (`CF-IPCountry`, `CF-Region-Code`) — zero-code | ✅ Live (when behind Cloudflare) |
| 2 | Browser HTML5 `navigator.geolocation` → `fromHtml5()` reverse-geocode | 🔧 Stub (needs reverse-geocoder) |
| 3b | MaxMind GeoLite2 IP DB → `lookupMaxmind()` | 🔧 Stub (needs `MAXMIND_DB_PATH` + reader) |

All results are clamped to the 7 launch states (`config/geo.php`).

---

## 3. ⚠️ Activation at launch

1. **Provider account** — choose USPS (free-ish) or Google Address Validation; set
   `ADDRESS_VERIFICATION_DRIVER` + the key (`USPS_USER_ID` / `GOOGLE_ADDRESS_API_KEY`).
2. Implement the provider's `verify()` (the class has a `TODO(launch)` marker).
3. Set `ADDRESS_VERIFICATION_GO_LIVE=true` + `php artisan config:clear`.
4. *(Optional)* KYB business match — set `ADDRESS_KYB_DRIVER` + key, extend the service to
   promote `Address Verified` → `Business Match Confirmed`.
5. *(Optional, §7.2)* MaxMind — download GeoLite2-City (needs `MAXMIND_LICENSE_KEY`), set
   `MAXMIND_DB_PATH`, `composer require geoip2/geoip2`, fill `lookupMaxmind()`.

> §7.4: Peter wants paid verification to trigger **after payment clears** where possible —
> `address_verification.verify_after_payment` (default true) signals callers to defer.

Config: `config/address_verification.php`. Keys: `.env.example`.
