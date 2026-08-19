# Q5 / Q6 / Q7 — Developer response (19 Aug 2026)

**Status:** UNDER REVIEW. Not locked. Not built.  
**Stamp:** APPROVE · MODIFY · REJECT · NEED MORE INFORMATION  
**Source:** live GigResource account model and matching code, not a proposed schema.

Do not treat this as a rulebook entry until a PM stamp is on each question.

---

## Q5 — Professional Service Origin

**PM preference (not locked):** one dedicated Service Origin per Professional account; travel radius is measured from it; it is not automatically the billing address.

### 1. Location fields that exist today

There is **one address block** on the Professional account (`user_profiles`). There is **no** billing-street field, **no** latitude/longitude, **no** travel radius, and **no** Service Origin field.

| Field | Purpose today | Feeds search / matching today? |
|---|---|---|
| `address` | Street line. Shown and verified as the Professional’s **business address**. Layer-1 filter rejects PO Boxes. Paid USPS/Google verification is scaffolded and **off** until go-live. | **No.** Not used for browse, same-state matching, or Fit Score. |
| `city` | City name. Public profile, homepage “professionals by city” counts, browse city filter. | **Yes, coarsely.** Browse `?city=` is a string prefix on this field. Fit Score “proximity” (20 pts) is a **string contains** of this city inside the event’s free-text location. Homepage city grouping counts this city, not a coverage map. |
| `state` | Registered jurisdiction of **this account**. Locked once set (one account per state). Also derives whether the account may transact in the launch area at all. | **Yes — this is today’s eligibility rule.** Same-state matching (R38) compares Professional `state` to the **event’s** `state`. Browse can also filter by state. Fail-closed: blank state matches nobody. |
| `country` | With `state`, derives `service_area_status` (operate here vs coming soon). | **Indirect.** Transaction gate, not miles. |
| `zip_code` | Stored with the profile. At client registration, ZIP → launch state via a **3-digit prefix table**. Geolocation helper maps ZIP prefix → state only. | **Indirect.** State inference / profile storage. **Not** a coordinate. **Not** a miles calculation. |
| `service_area_status` | Derived `supported` / `coming_soon`. Out-of-area accounts can register and browse; they cannot transact. | **Yes** as a transaction gate. Not a travel origin. |
| `address_status` and verification columns (attempts, verified/locked timestamps, meta) | Address-verification pipeline, not a place. | **No.** |
| `address_flagged_home` | Layer-1 thinks the street looks like a home. Flagged for review; **not** blocked. | **No.** |
| `trade_license_state` | Which of the seven jurisdictions issued the trade licence. Must match this account’s state. | **No.** Licence jurisdiction, not travel origin. |

**Not on the Professional account at all**

- Billing / card address — membership billing lives at the payment provider. GigResource does not store a separate billing street.
- `latitude` / `longitude`
- `travel_radius`
- `service_origin` (or any second address block)

**On the request (event), not the Professional**

| Field | Purpose today | Feeds matching? |
|---|---|---|
| `location` | Free-text “city, venue, or address”. | Fit Score proximity (string). Bidding-board city search is also a string `LIKE`. **Cannot** do miles. |
| `state` | Two-letter request state (R38). Prefer the event’s own state; otherwise the client account’s state. | **Yes.** Same-state eligibility. |

### 2. Recommendation (pending stamp — not a schema lock)

**Create a dedicated Service Origin.** Do not reuse `address` as the radius origin, and do not treat it as billing (billing is not in this model).

Keep the existing block as what it already is:

- `address` + verification = business / identity address (may be a home flagged for review).
- `state` = same-state eligibility (R38) and licence account (R47). Unchanged.
- `city` / `zip_code` = too coarse for a miles radius; keep them for display, directory counts, and ZIP fallback (Q7).

Service Origin for V1 should be **new stored data**: a labelled origin (street or venue the Professional names as the dispatch base), **plus coordinates and a precision flag after geocoding**, **plus a travel radius in miles**. Existing `state` stays the legal/eligibility gate; origin coordinates are only used **inside** that state (Q2 Option B as already preferred: unclipped radius + state filter on the **event** state).

Exact new column names are **not** proposed here. The PM asked not to lock schema until the existing fields were identified. They are identified: **nothing current is a Service Origin.**

### 3. Does V1 need more than one active Service Origin?

**No current technical or product reason.**

- One Professional account is already one state (R47). A second state is a second account, not a second origin on the same account.
- Search, bidding, packages, and Fit Score all assume one profile city/state.
- There is no warehouse / satellite-yard workflow in the live product.

Multiple active origins in the **same** state (studio vs warehouse) is a later product choice, not a V1 requirement. V1 = **one** origin per account.

### NEED MORE INFORMATION (Q5)

| # | Question | Why it is not assumed |
|---|---|---|
| Q5-A | Default travel radius (miles), and whether the Professional may edit it. | Field does not exist. |
| Q5-B | Is the Service Origin street shown on the public profile, or used only for distance math? | Today’s business address is on the account; a dispatch yard might be private. |
| Q5-C | May Service Origin differ from the verified business address? (Recommended: **yes**.) | PM preference already says it is not billing; confirm it is also not forced to equal the verified street. |

---

## Q6 — Distance calculation for matching

**PM preference (not locked):** straight-line / geodesic for marketplace search and matching eligibility. Driving distance, travel time, and travel-fee math can be a later feature.

### Recommended implementation (actual stack)

GigResource is **Laravel 12 / PHP 8.2**. Production database is **MySQL** (cPanel). Automated tests run on **SQLite**. There is **no** PostgreSQL and **no** PostGIS.

**Method for V1 matching**

1. Geocode **once when the origin or the event location is saved** (write path). Store latitude, longitude, and a precision flag (`exact` / `zip` / `unresolved`).
2. At search / eligibility time, **no mapping API**. Filter:
   - same state as the event (existing R38, indexed), **then**
   - a bounding box around the event point using ordinary indexes on latitude and longitude, **then**
   - geodesic distance ≤ the Professional’s travel radius.
3. Geodesic formula:
   - MySQL production: `ST_Distance_Sphere` (metres on WGS84) converted to miles, **or** the equivalent Haversine in SQL.
   - SQLite tests: the same Haversine in PHP so tests do not depend on MySQL spatial functions.

This is **straight-line / great-circle**, not road miles and not drive time.

**Indexing / performance**

- Composite / separate indexes on Professional `state`, `latitude`, `longitude`.
- Bounding box first (cheap range scan), circle second (few remaining rows).
- Launch geography is seven jurisdictions, not a national point cloud. With indexes this stays interactive at thousands of Professionals and remains acceptable into the tens/low hundreds of thousands **inside one state**. If a single state ever reached a scale where the box still returns huge sets, the next step is a spatial index on a MySQL `POINT` column — still no PostGIS, still no per-search API.

**Third-party API?**

- **Search / matching: no.** Distance is arithmetic on stored coordinates.
- **Write path: yes, a geocoder**, once per saved origin or event location. Recommendation: **U.S. Census Geocoder** as the primary (no per-request fee, U.S. addresses). Optional paid Google Address Validation / Geocoding only as a fallback if Census fails **and** a go-live key already exists for address verification. Do not call either API on every browse or bid.

**Expected costs**

| Piece | Cost |
|---|---|
| Marketplace search / bidding eligibility | **$0** per query (our database). |
| Census Geocoder on save | **$0** (public; rate-limited — queue/retry on write, never on search). |
| Google (optional fallback) | Only if enabled; billed per geocode on **save/retry**, not per search. Same class of cost as the existing unused address-verification keys. |
| ZIP centroid table | **$0**. Ship a static file for U.S. ZIP/ZCTA centroids **for the seven launch states** (public Census/HUD data). |

**Limitations / accuracy trade-offs (approve with eyes open)**

- Straight-line is shorter than driving. A Professional 20 road-miles away via a river or beltway may be 12 miles as the crow flies (included) or the reverse (excluded). That is the V1 trade-off the PM asked for.
- It is **not** travel time and **not** a travel fee. Those need a Directions API later and must not be mixed into eligibility.
- It is only as good as the geocode. A ZIP centroid can be miles from the actual street (see Q7).
- Cross-state: R38 still wins. A Maryland origin whose circle overlaps Virginia does **not** match a Virginia event unless product later changes R38. (Aligns with Q2 Option B: radius is not a licence to leave the event’s state.)

**Not recommended for V1 matching:** Google Distance Matrix / Directions on each search (cost, latency, third-party dependency, overkill for eligibility).

---

## Q7 — Geocoding failure and ZIP fallback

**PM hierarchy (not locked):** Precise validated location → Validated ZIP-based fallback → Unresolvable location.

**Architectural fact:** today we can map a ZIP **prefix to a state**, not a ZIP to a point. ZIP fallback **requires shipping a ZIP/ZCTA centroid table**. Until that exists, ZIP is **not** a coordinate.

Matching must be evaluated on **both** sides: Professional origin **and** event location. The event’s `location` is still free text. If the event cannot be placed, that is **not** “no Professionals available.”

### Recommended states (do not invent a point)

| Status | Meaning | Eligible to match? |
|---|---|---|
| `exact` | Street/venue geocoded to coordinates. | Yes, if in-state and within radius. |
| `zip` | ZIP validated and centroid stored; ZIP is not “too broad” (below). | Yes, with the accuracy trade-off. |
| `unresolved` | Neither exact nor an acceptable ZIP. | **No.** Fail closed. |

Never write coordinates from a guess. Never treat `unresolved` as in-range.

### What happens when a full address fails to geocode

1. Do not store invented lat/lng.
2. If a **valid 5-digit ZIP** is present and that ZIP is in a launch state (existing prefix table) **and** a centroid exists **and** the ZIP is not too broad → save as `zip` + centroid, tell the user the location is **approximate (ZIP)**.
3. Otherwise → `unresolved`. User must correct the address or pick a more specific place. Matching stays off.

Same ladder for the **event** venue and for the **Professional** Service Origin.

### What happens when only a valid ZIP is available

- Resolve state from the existing 3-digit prefix table (already live).
- If that state is not a launch state → existing coming-soon / ineligible behaviour (not a geocode failure).
- If it is a launch state → look up centroid. If found and ZIP is not too broad → `zip`. If not found → `unresolved`.

### Unusually large / geographically broad ZIPs

A ZIP is **not acceptable** as a fallback when its uncertainty is large compared with travel radius. Recommended test (numbers are a **proposal**, not locked):

- Use published ZCTA land area (and/or bounding-box half-diagonal) from the same static table.
- If land area **> 150 square miles**, **or** the half-diagonal of the ZIP’s bounding box is **greater than half of the Professional’s travel radius** (event side: greater than 15 miles if no radius applies), treat the ZIP as **too broad** → `unresolved`, ask for a city, venue, or street.

**NEED MORE INFORMATION (Q7-A):** confirm or change the 150 sq mi / half-radius figures. Do not invent a different cutoff without a stamp.

Rural West Virginia and some Maryland Eastern Shore ZIPs will hit this on purpose. That is safer than pretending the centroid is the venue.

### When neither precise coordinates nor an acceptable ZIP can be established

- Persist `unresolved`. No coordinates.
- Professional: cannot become eligible for radius matching; profile save of Service Origin is incomplete. Same-state browse may still list them by city **until radius matching is switched on**; once radius matching is live, they are **out of radius matching** until resolved. **Do not** silently include them in “within X miles.”
- Event / client request: the request is **not** opened to matching. It is not published as an in-range gig.

### What the Client or Professional sees

Two different problems, two different messages. **Never** show “No Professionals available” when GigResource could not place the location.

| Condition | Who | Message (proposed English) |
|---|---|---|
| Event / search location `unresolved` | Client | **“We could not place this location.”** Ask them to enter a street, venue, or a more specific ZIP. Do **not** show an empty professional list as if the market were empty. |
| Event location `zip` (approximate) | Client | **“Location is approximate (ZIP). Nearby professionals are based on the centre of this ZIP, not a street.”** Results may still show. |
| Origin `unresolved` | Professional | **“We could not place your service origin. Distance matching is off until this is fixed.”** |
| Origin `zip` | Professional | **“Your service origin is approximate (ZIP). Clients will see you by travel radius from the ZIP centre.”** |
| Location placed, in-state, **zero** Professionals in radius | Client | **“No professionals available in this area for this request.”** This is the only time the empty-market wording is used. |
| Location placed, **out of launch / wrong state** | Client | Existing same-state / coming-soon copy — not a geocode failure. |

### How an unresolved geocode cannot silently become an eligible match

Server-side, fail closed (same pattern as R38 blank state):

1. Eligibility requires **both** sides `exact` or acceptable `zip`, **and** stored coordinates on both, **and** same event state, **and** geodesic distance ≤ radius.
2. `unresolved` or missing coordinates → **not eligible**. No default to “same city string” once radius matching is the rule.
3. Fit Score must not treat a failed geocode as mid-score proximity. Today it awards **10 points** when the event has no location or the Professional has no city (“absent information is not a bad answer”). That is acceptable for a **rank**, not for **eligibility**. Eligibility stays a hard gate.
4. Tests: failed geocode, ZIP-too-broad, and missing event point must **not** appear in an in-range result set.
5. Lists that cannot yet filter by miles (until this is built) must not pretend they did.

---

## What is already decided vs what this does not decide

- **R38** same-state remains the first gate. Live comparison is Professional **account state** vs **event state** (two-letter `state` fields — there is no `state_id`).
- **Q2 Option B** (unclipped radius + state filter) is approved in the 13 Aug review. Not built: there is no radius yet.
- **Q10** was later **signed off** in that same review (Coverage Map + 20-mile directory-only cap, never matching). **Not built.** Live homepage counts **base city** only, threshold **2**, hardcoded. Q8’s approved admin threshold of **5** is also not built.
- **Q5, Q6, Q7** were still OPEN in that review. They are answered above. The review’s closing line that “every open item is resolved” covers Q2/Q8/Q10/Q11, not Q5–Q7.
- **No radius / Coverage Map implementation** until Q5–Q7 are stamped (origin + distance + geocode failure). Q10 cannot be built without those.

---

## Q9 — Account statuses that count toward the city directory

**PM rule (approved):** only Active and Eligible Professionals count. Suspended / Incomplete / Unapproved / otherwise ineligible must not count.

**Developer action:** map that rule onto the real account model. Structured: actual field → counts today? → should count under Q9? → reason.

### There is no Active / Suspended / Incomplete / Unapproved enum

A Professional account is not one status column. Eligibility is a **set of flags**. Influencers have `pending / approved / rejected`; Professionals do not.

### Mapping

| Actual field | Values | Counts toward homepage city total **today**? | Count under Q9 (recommended)? | Reason |
|---|---|---|---|---|
| Professional role | Spatie role `professional` | Yes — required | **Yes — required** | Clients and influencers must not count. |
| Soft-deleted account | `deleted_at` set | No (automatic) | **No** | Account is gone. |
| Login lock (R56) | `login_locked_at` set after 3 bad passwords | **Yes (gap)** | **No** | They cannot sign in, so they cannot receive work. Closest thing to “Suspended.” |
| Deletion requested | `deletion_requested_at` set | **Yes (gap)** | **No** | Leaving the platform. |
| Service-area status | `supported` / `coming_soon` | **Yes, both (gap)** | **Only `supported`** | Coming-soon accounts cannot transact. |
| Registered state | two-letter launch state, or blank | Blank excluded; out-of-list excluded | **Launch state required** | Same-state rule: blank matches nobody. |
| City | name or blank | Blank excluded from city pages | **Required for a city page** | Cannot count toward a city with no city. |
| Availability | `available` / `busy` / `not_available` (or empty) | **Yes, all (gap)** | **Recommend exclude `not_available` only** | Busy can still receive work. `not_available` is the self-serve “not taking work” flag. Not a suspension. |
| Trade licence / insurance / workers’ comp | none / pending / verified | **Yes, all** | **NEED MORE INFORMATION** | These are optional badges, not an account-approval workflow. Do not treat “unverified” as “Unapproved” unless product locks that. |
| Address-check status | pending, verified, needs correction, manual review, blocked, etc. | **Yes, all** | **NEED MORE INFORMATION** | Paid address check is still off. `registration_blocked` should not count if it is ever used. |
| Membership plan | none / active / cancelled | **Yes, all** | **NEED MORE INFORMATION** | Receiving work is not currently gated on a paid plan. |
| Email verified | timestamp or empty | **Yes, both** | **NEED MORE INFORMATION** | Not a marketplace gate today. |

**What the homepage actually counts today:** Professional role + non-blank city + launch state. Threshold **2**, hardcoded. It does **not** exclude login-locked, deletion-requested, coming-soon, or `not_available`.

**Recommended Q9 filter when the directory is rebuilt** (still needs stamp on the NEED MORE INFORMATION rows):

Professional role, not deleted, not login-locked, not deletion-requested, service area `supported`, launch state + city present. Exclude `not_available`. Do **not** require verification badges or a paid plan unless the PM stamps that.

### NEED MORE INFORMATION (Q9)

| ID | Question |
|---|---|
| Q9-A | Must trade-licence / insurance be verified to count? (Today: no.) |
| Q9-B | Must they have an active membership? (Today: no.) |
| Q9-C | Exclude `not_available`? (Recommend yes.) |
| Q9-D | Must email be verified? (Today: no.) |

---

## Summary for the stamp

| Q | Developer position | Needs a number or product call? |
|---|---|---|
| **Q5** | Identify existing fields (done). None is a Service Origin. **Add one dedicated origin** (plus radius). V1 = one origin per account. Do not use billing (it is not in the model). Do not silently reuse business `address`. | Q5-A radius default; Q5-B public vs private street; Q5-C may origin ≠ verified business address. |
| **Q6** | Geodesic Haversine / `ST_Distance_Sphere` on stored coordinates. Bounding-box + state index. **No** mapping API on search. Census geocode on save; optional Google fallback. Driving/time/fees later. | None required to approve the method. |
| **Q7** | Explicit `exact` / `zip` / `unresolved`. Fail closed. ZIP centroid table required (does not exist today). Broad ZIP → unresolved. Separate UI for “could not place location” vs “no professionals in range.” | Q7-A broad-ZIP cutoff (150 sq mi / half-radius proposed). |
| **Q9** | There is **no** Active/Suspended/Incomplete/Unapproved enum. Map flags as above. Homepage today is too loose (role + city + state only). | Q9-A verification required?; Q9-B membership?; Q9-C exclude not_available?; Q9-D email verified? |
