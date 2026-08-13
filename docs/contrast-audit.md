# Colour contrast audit — brand palette vs WCAG 2.1 AA

Measured 2026-08-13 on the rendered page at 1280×800, light theme, by computing
the real foreground and background of every text node — not by reading the
design tokens. A token is a promise; what matters is what lands on screen.

This closes the last open recommendation from the WCAG 2.1 AA pass (row 180 /
task 6). That pass fixed structure — labels, focus order, alt text, target
sizes. It did **not** check colour, and colour is where the brand palette
fails.

## How this was measured, and two mistakes worth recording

The first version of the checker read only `background-color`. Every button on
this site has a **gradient**, which sets `background-image` — so white text on
an orange gradient measured as white-on-white and reported a contrast of 1.0.
Five of the twelve "failures" in that first run were fake.

The second version read gradients but took the **darkest** colour stop as the
worst case. That is backwards for white text: the *lightest* stop is the hard
one. The primary button reported 3.56 when its real figure is **2.26**.

Both mistakes flattered the result. Neither would have been visible in a
screenshot.

## Findings — light theme, landing page

Normal text needs 4.5:1. Large text (≥24px, or ≥18.66px bold) needs 3:1.

| What | Foreground | Background | Ratio | Needs | Short by |
|---|---|---|---|---|---|
| **"Sign Up" — primary button** | `#ffffff` | `#fb923c` | **2.26** | 4.5 | −2.24 |
| Rating stars | `#f59e0b` | `#ffffff` | 2.15 | 4.5 | −2.35 |
| "BEST VALUE" badge | `#ffffff` | `#10b981` | 2.54 | 4.5 | −1.96 |
| "NEW" badge | `#ffffff` | `#f97316` | 2.80 | 4.5 | −1.70 |
| "Your Vision." hero, 54px | `#f97316` | `#ffffff` | 2.80 | 3.0 | −0.20 |
| Value-band labels | `#ffffff` | `#ea580c` | 3.56 | 4.5 | −0.94 |
| "Find Talent" button text | `#ea580c` | `#ffffff` | 3.56 | 4.5 | −0.94 |
| Role card heading | `#ffffff` | `#3b82f6` | 3.68 | 4.5 | −0.82 |
| Footer copyright | `#6b7896` | `#0f1b35` | 3.87 | 4.5 | −0.63 |

The primary button is the one that matters. It is the most-clicked control on
the site, and at 2.26:1 its label is roughly half as legible as the standard
requires.

### Not verified

One block — the "Ready to Create Unforgettable Events?" call to action —
reported white-on-white, which is almost certainly the checker failing to find
a background painted by a pseudo-element rather than a real failure. It is
listed here as unverified rather than counted as a defect.

## The fix, and why it needs the Owner

Every failure above is white text on a brand colour that is too light, or a
brand colour used as text on white. There are only two remedies: darken the
colour, or stop using white on it. Both change how the brand looks, so this is
the Owner's call rather than a silent correction.

The smallest change that passes, keeping the same hues:

| Token | Now | Proposed | With white |
|---|---|---|---|
| Orange (white text on it) | `#fb923c` → `#ea580c` | `#c2410c` | 2.26 → **5.18** |
| Green (white text on it) | `#10b981` | `#047857` | 2.54 → **5.48** |
| Blue (white text on it) | `#3b82f6` | `#2563eb` | 3.68 → **5.17** |

These are the same colours one step darker on the standard ramp — orange-700,
emerald-700, blue-600. The lighter shades stay exactly as they are everywhere
they are used as a *background behind dark text*, as a border, or as a tint;
only the pairing with white text changes.

Two of the rows above are not colour changes at all:

- **Rating stars** — if a numeric rating sits beside them ("4.8 ★★★★★"), the
  stars are decorative and the ratio does not apply. Confirm the number is
  always present and mark them `aria-hidden`.
- **Hero "Your Vision."** at 2.80 against a 3.0 requirement is 0.2 short and is
  large display text. `#ea580c` clears it without a visible change at that size.

## What is not covered here

- Dark theme has not been measured. It should be, before launch, because the
  same tokens are re-pointed there and a pass in light does not imply a pass in
  dark.
- Only the landing page was swept. The dashboards reuse these tokens, so the
  same pairings will recur, but the sweep should be repeated per portal.
- A manual screen-reader pass is still outstanding and cannot be automated.
