# Work log

Everything done from 2026-11-10 onward. Ali gave the client the update up to
09.11, so this file starts after that line and is what the next client update
is written from.

Newest first. Each entry: what changed, why it mattered, and whether it is
live. Written in plain words, because it is read by people who do not read
code.

---

## 2026-11-12

### The inbox on a laptop and on a phone
Four faults on the Messages page, all of them about width.

The page has three columns: the list of conversations, the conversation, and
the panel about the person you are talking to. They were meant to become one
column on a narrow screen and never did, because of how the rule was written.
On a smaller laptop the middle column, which is the conversation itself, was
about forty pixels wide with one word to a line and the right-hand panel drawn
on top of it.

The search box in the orange bar kept a fixed width even when the bar had
shrunk around it, so on a tablet it hung out of the bar and over the icons
beside it.

On a phone the "Become a Professional" button ran off the left edge of the
screen with its first letters cut away. It is now just its icon there, and
still labelled for anyone using a screen reader.

Also on a phone, "Awaiting your reply" was cut to "Awaiting your repl". It
wraps to a second line now.

Checked at five widths from a wide desktop down to a phone.

### A card that printed one number and drew another
The Spending Trend panel showed a large figure with a line under it. The figure
was the total agreed across every booking; the line was money actually paid. So
it read seven thousand six hundred dollars above a line sitting flat on zero,
and nothing on the card said those were different questions.

The card now shows what it draws: paid to date, counted the same way as the
Paid tile at the top of the same page.

### "Message Grand"
A button on each booking said "Message" followed by the first word of the
professional's name. That reads as a first name, and here a professional is as
often a business, so a booking with Grand Oak Banquet Hall offered to "Message
Grand". The button says Message; the card names them in full two lines above.

### The client was greeted by name, and the name was cut off
The orange bar at the top of every client page said "Welcome back, ..." and
stopped. The name, which is the only reason that sentence exists, was the part
that did not fit.

The bar shares one line with the search box, the Post an Event button and the
account controls, and the search was holding a fixed width whatever else needed
it, so the greeting absorbed the whole shortage. The greeting now uses the
first name, and the search gave up sixty pixels.

Worth saying because the first attempt was wrong: letting the search shrink
instead looked like the obvious fix, and it was exactly the arrangement the
owner complained about in September, when the search was a different size on
every page. A test written at the time caught it. The search is still one width
everywhere.

Checked at five screen widths down to a phone.

### The dashboard disagreed with itself
The dashboard page works out six of its own figures rather than being handed
them, and those six did not follow the rule the rest of the page had just been
given. Two of them sat two lines apart on the same card, counting different
sets of bookings.

All six now count what everything else counts. Also corrected: the breakdown of
what kinds of event a client has run was counting an event whose only finished
booking was one they had supplied to themselves.

### Numbers that disagreed with the page they linked to
On the profile panel, "Bookings" and "Payments" each showed a number beside a
link, and neither matched what was behind the link. Bookings counted a
different set of rows than the Bookings page lists. Payments counted payment
records, while the Payments page shows one line per booking, and paying for an
agreement creates two records, the deposit and our fee. So the panel said two
where the page showed one.

Both now ask exactly the question their own page answers.

### Hiring yourself is not a booking
A booking where the client is also the professional is leftover test data. The
site already left those out of the Bookings, Spending and Payments lists, and
new ones are refused outright. Four more places were still counting them: the
dashboard tiles and its recent list, the figures shared between the dashboard
and a client's public profile, the client report's money totals, and the prompt
asking somebody to review a booking they supplied to themselves.

All of them leave them out now. Two places had also written the rule out by
hand instead of using the shared one, and the hand-written version quietly
dropped bookings that have no professional assigned yet, which are real
bookings waiting for one.

### Every detail page checked
There was a walk-through test that opens every client page and checks none of
them break, but it skipped the pages that need a specific thing to open: an
event, an agreement, a transaction, a conversation. Those are the heaviest
pages on the site and the ones this month's money work touched.

They are now opened and checked too, twelve of them, against one real set of
records. All twelve open cleanly.

### Paying the deposit no longer shows as paying nothing
On the Payments and Spending pages, a client who had just paid a deposit saw
"Paid $0", with the whole price of the booking sitting under "agreed, not yet
paid". On the page somebody checks before paying again.

Both pages sorted a booking's whole price by the state of the booking, which
was correct while the site took no money at all. It takes a deposit now, at the
end of the agreement, so the deposit is counted as paid and the remainder as
still owed. The price that was agreed is unchanged, as it should be: only what
is left to pay moves.

A booking already finished keeps counting once. Its full price is already
counted as paid, and adding the deposit again would have reported two thousand
five hundred dollars paid on two thousand dollars of work.

### Three tests that were failing before any of this
All three had been failing on the main copy of the site and none of them had
found a real fault. Each was checking the spelling of something rather than the
thing itself: two were looking for names on the Event Types page that were
renamed when that page was rebuilt, and one was counting twelve help questions
under four topic names that were relabelled on 6 November, so it found three.

Corrected to match, and the counting one now checks that no question is hidden
outside the topics the help page offers, which is the fault it was meant to
catch.

### The money record was keeping our fee and not the client's money
Every booking writes a running record of what was charged, so that a total is
always the list added up and never a single number somebody can overwrite. It
was recording the $4.99 we charge for the agreement, and nothing else.

So a two thousand dollar booking had four dollars and ninety nine cents written
against it, and the question "what did this booking come to" answered zero.
Both ways of making a booking had the same hole: finalising an agreement, and
buying a package.

Now a funded agreement writes two lines, the deposit as money received and the
remainder as money still owed, and they add up to the price both sides signed.
Buying a package writes its price as owed, because nothing is charged at that
moment. Our own fee stays separate, which is correct: it is ours, not the
professional's.

Nothing on screen reads this record yet, which is exactly why it was never
noticed, and why it was worth fixing before anything does. Checked by six tests
and by the 411 existing ones.

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

## On hold: professional and affiliate side

The agreed order of work is the client side first. These are reviewed, written
down and understood, and none of them is started.

- **Bidding, agreements and payments.** The whole of the November plan. What is
  genuinely missing from it is paying in three or more stages, and the
  arrangement that would let the site hold money between them. Everything else
  in the plan already exists.
- **Disputes.** Built on both sides already. What is unfinished inside it is
  the time limits for each side to respond and the dispute fee, and both are
  waiting on a decision rather than on work.
- **Influencer hiring.** Influencers still cannot be hired at all. The rules
  for it are settled; the screens are not built.

## Still waiting on the client

- **Duplicate services.** 360 Photo Booths and 360 Video Booths are the same
  thing entered twice, and so are Funeral Officiants and Funeral Officiants &
  Ceremony Leaders. Waiting on which name to keep in each pair.
- **Influencer hiring.** Peter confirmed "Both" means either way, and that the
  same-state rule applies. The remaining work is that influencers cannot be
  hired at all yet.
- **GigSalad layouts.** Waiting on whether specific pages should be followed,
  and on the exact wording of the designer's venue question.
