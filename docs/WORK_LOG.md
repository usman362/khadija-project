# Work log

Everything done from 2026-11-10 onward. Ali gave the client the update up to
09.11, so this file starts after that line and is what the next client update
is written from.

Newest first. Each entry: what changed, why it mattered, and whether it is
live. Written in plain words, because it is read by people who do not read
code.

---

## 2026-11-10

### Affiliate test login
A working affiliate account can now be made with one command, on this machine
or on the live server. Signing in opens the full affiliate portal: dashboard,
referral centre, commissions, payouts, analytics, badges.

**Why it was not a two-minute job.** Three separate things have to line up or
the portal opens and does nothing, and each fails silently:
the account only gets its permissions when the application is *approved*; a
pending affiliate earns no commission however many people use their link; and
an account with no state on file is blocked from transacting.

Tested by signing in. Live on this machine; the command is ready to run on the
live server whenever wanted.

### About page, the picture at the top
Two fixes, both live.

The words in the graphic were misspelled: it read "PAR" instead of "GR", and
two of the four lines were nonsense. The cause was that the graphic was an AI
generated picture with the words baked into it, so they could not be
corrected. The words are now real text and will stay sharp at any size.

The first attempt cropped the photo too tightly and cut the laptop out. Recut
so the whole desk is in frame.

---

## Still waiting on the client

- **Duplicate services.** 360 Photo Booths and 360 Video Booths are the same
  thing entered twice, and so are Funeral Officiants and Funeral Officiants &
  Ceremony Leaders. Waiting on which name to keep in each pair.
- **Influencer hiring.** Peter confirmed "Both" means either way, and that the
  same-state rule applies. The remaining work is that influencers cannot be
  hired at all yet.
- **GigSalad layouts.** Waiting on whether specific pages should be followed,
  and on the exact wording of the designer's venue question.
