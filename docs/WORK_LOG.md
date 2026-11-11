# Work log

Everything done from 2026-11-10 onward. Ali gave the client the update up to
09.11, so this file starts after that line and is what the next client update
is written from.

Newest first. Each entry: what changed, why it mattered, and whether it is
live. Written in plain words, because it is read by people who do not read
code.

---

## 2026-11-12

### Affiliate side: Resources removed
The Resources section is gone from the affiliate portal: the sidebar group and
its five pages (Getting Started, Resource Library, Academy, Tutorials, Featured
Articles). The owner's call, nothing of value in it.

Removed completely rather than hidden, including the table that stored the
content. That part is on purpose. When Co-Op was retired in July the screens
went and the storage was left behind as "harmless", and two weeks later the
database still held eight rows of a product that no longer existed. Nothing
here belonged to any one affiliate, only general guide text, so there was
nothing to keep.

Checked by tests and live on this machine.

### The bidding, agreements and payments plan: reviewed
Read the 14 page plan and checked every part of it against what the site
actually does today. The short version is that most of it is already built, and
the problem it opens with cannot happen here.

That problem is a client thinking a $300 price and a $100 first payment are the
same number. On this site they are four separate figures in four separate
places: the client's budget estimate, the professional's offer, the agreed
price, and the amount due today with the balance on a date. Also already
working: a price change needs both signatures, editing a live request is free
while reposting is paid, and every movement of money is written down and never
overwritten.

Genuinely missing, and worth a decision rather than a guess: paying in three or
more stages (today it is a deposit and then the balance, which is what the plan
itself recommends as the default), and the arrangement that would let the site
hold money between the two.

### Disputes: answered what is in place
Disputes are built and in use on both sides. A client or professional can file
one, attach evidence, and watch its stage; the admin side can classify, assign,
decide and close. A case covers one service, not the whole event, so a problem
with the caterer does not freeze the photographer's payment.

Two things inside it are deliberately unfinished and both are waiting on a
decision, not on work: there are no time limits yet (how long each side has to
respond), and there are no dispute fees at all. The fee idea in the owner's
message, where each side pays to escalate and the winner is refunded, does not
exist yet anywhere.

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
