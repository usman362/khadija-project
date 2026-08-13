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

### The call-to-action band — a real fault, found by chasing the "unverified"

The CTA reported white-on-white, and that part was indeed the checker: its
background is a `::before` gradient, which the walker did not read.

Reading it properly turned up something the sweep would otherwise have missed.
The overlay is **semi-transparent over an admin-replaceable photograph**, so
its contrast depends on the picture. Composited against a bright photo, the
orange end gave **2.89:1** — failing even the 3.0 large-text threshold — while
against a dark photo it passed at 5.03. Nobody would have known until someone
swapped the image.

The overlay is now `rgba(15,27,53,.94) → rgba(29,78,216,.90) → rgba(154,52,18,.90)`,
whose worst case over a pure-white photo is 14.56 / 5.47 / 5.92 — all clear of
4.5 whatever picture an admin uploads.

## Status: FIXED 2026-08-13

All nine failures below are resolved and re-measured at zero remaining on the
landing page in light theme. The Owner approved the change via Ali. What
follows is the record of what was wrong and what was altered.

## The fix

Every failure above is white text on a brand colour that is too light, or a
brand colour used as text on white. There are only two remedies: darken the
colour, or stop using white on it.

The change made, keeping the same hues one step darker on the standard ramp:

| Token | Was | Now | With white |
|---|---|---|---|
| Orange under white text | `#fb923c` → `#ea580c` | `#c2410c` → `#9a3412` | 2.26 → **5.18** |
| Green under white text | `#10b981` | `#047857` | 2.54 → **5.48** |
| Blue under white text | `#3b82f6` | `#2563eb` → `#1d4ed8` | 3.68 → **5.17** |

These live as `--orange-onwhite`, `--blue-onwhite` and `--green-onwhite`, used
*only* where white text sits on the colour. `--orange` and `--blue` themselves
are untouched, so every tint, border, and dark-text-on-light use looks exactly
as it did. Gradients needed both stops moved, not just the dark end — the light
stop is the one white text has to survive.

The plan-badge colour map was the same problem in data rather than CSS: it is
admin-chosen and carries white text, and four of its seven entries failed.
"BEST VALUE" on `#10b981` measured 2.54:1. Each entry moved one step darker.

Two of the rows above are not colour changes at all:

- **Rating stars** — they repeat a rating already written beside them, so they
  are decoration and the ratio does not apply to them. Marked `aria-hidden`,
  which also stops a screen reader announcing "black star" five times.
- **Hero "Your Vision."** was 0.2 short at display size. Moved to `--orange-dark`
  (3.56), which is not a visible change at 54px.

## What is not covered here

- Dark theme has not been measured. It should be, before launch, because the
  same tokens are re-pointed there and a pass in light does not imply a pass in
  dark.
- Only the landing page was swept. The dashboards reuse these tokens, so the
  same pairings will recur, but the sweep should be repeated per portal.
- A manual screen-reader pass is still outstanding and cannot be automated.
