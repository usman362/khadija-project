# Work log

Everything done from 2026-11-10 onward. Ali gave the client the update up to
09.11, so this file starts after that line and is what the next client update
is written from.

Newest first. Each entry: what changed, why it mattered, and whether it is
live. Written in plain words, because it is read by people who do not read
code.

---

## 2026-11-13

### The demo professional had never done a day's work
Ali put the professional dashboard beside the reference screen and said the
data did not match and the colours were not the same. He was right about both,
but the two halves had different causes.

The data first. Every panel on the page was already built to read a real
professional's business: a revenue goal, the months their work falls in,
today's schedule, what is waiting, what they are owed. The account anybody
signs in to demonstrate with had no bookings at all, so the page did exactly
what it should do for somebody who has done no work. It said "Nothing booked
for today", "Not enough of your own bookings yet", "You have not set a goal",
and printed $0.00 four times. Against a reference screen showing a working
season, that reads as an unfinished page rather than an empty account.

The demonstration account now has a year behind it: around forty jobs dated
across twelve months with a deliberate May to September season, three gigs
today at different hours, work still ahead with one of them short of crew,
money earned and money withdrawn, bids won and bids open, two reviews with one
unanswered, and a profile with every setup step finished. None of it is drawn
on the page. Every figure the dashboard prints is worked out from those records
by the same code that would work it out for a real professional, so if the
arithmetic were wrong the demonstration would show it wrong.

### Nine in the evening was not today
Setting the demonstration day up turned over a real fault. Times are stored in
UTC and read on Eastern time, which is five hours behind it, so a gig at nine
in the evening is written down as two o'clock the following morning. The
dashboard asked for today's work by comparing the stored date against today's
date, which put every evening job on tomorrow's schedule and left it off
today's.

On a marketplace whose work is almost entirely evening work, that is most of
the day. A professional with a nine o'clock job opened the page on the day of
it and was told nothing was booked. The same question was being asked in four
other places, including the check for whether somebody is already booked that
day, where an afternoon job and an evening job on the same day did not register
as a clash at all. All five now ask for the day as the pair of moments either
side of it on our own clock.

### A month's takings, counted from the wrong date
The revenue goal and "earned this month" were measured from the date a booking
record was created rather than the date the work happened. A wedding booked in
September and performed in November counted as September's money, so somebody
who had finished three jobs this month could be told they had earned nothing
this month and were behind on a target they had already met. Both figures now
count completed work by the month the job falls in, which is also the basis the
"when your work falls" chart has always used.

### A chart that contradicted the numbers printed beside it
The ring on the Bid Intelligence card was six fixed slices written into the
stylesheet: a third blue, a quarter purple, and so on, identical on every
account every day. The figures listed beside it were real. An account with two
bids, both open, showed a six way split next to a list saying one hundred
percent open. The ring is now cut from the same counts the list prints, so the
two halves of the card cannot disagree, and an account with no bids draws no
ring at all.

### A feed of work, all of it closed
The Opportunity Feed asked only whether a request had been published and not
yet awarded. It had no notion of a bidding window having shut, so requests
whose deadline passed in August were still being offered in November. The card
holds six rows, so six long dead requests filled it and pushed every live one
out, and the page then had to print "Closed to bids" against every line of a
card whose entire job is work you can still take. The feed now shows only
requests still accepting proposals.

### The colours
The reference screen reads its state off colour and ours was flat white boxes
with grey borders. Each of the four cards across the top already carried a
colour in the code and the page had simply never drawn it. Those are back, with
a mark on each. Things waiting now carry a symbol for what kind of thing they
are and their urgency as a coloured tag rather than bare text. The revenue bar
is green when the month is keeping up and amber when it is not, with how far
ahead or behind, and how much of the month has gone. The season chart tells its
busy months from its quiet ones. Rows in the gig list take their colour from
their own status, so a cancelled job stops wearing the colour that means it
went well. Today's schedule is a line down the day with each job's length
rather than two times side by side. The money card leads with the total and
breaks it down underneath, which is the order somebody asks it in.

### The finished setup bar was the one state we never drew
The "finish setting up" row disappeared the moment the last step was done, so a
professional who had completed everything watched it vanish rather than be told
they were ready. It now stays and turns green, listing what is done with a tick
against each, which is what the reference screen shows.

### Each opportunity now says why it suits you
The Opportunity Feed was a list of rows with a fit percentage on the right. It
told a professional how well a job suited them without telling them why, which
is a number to be taken on trust. The reference screen puts three cards across
instead, and under each job three plain statements: whether it is work they
offer, whether it is inside the distance they travel, and who posted it.

All three are answered from records. The services line is already the
difference between the feed's two halves. The travel line measures the
distance between where they work out of and where the job is, against the
radius they set themselves, and the same two points the board uses to decide
whether they may see the job at all, so a card cannot say twenty miles beside
a board that refused the same job. A job whose address could not be placed
says the distance is not known, rather than printing nought miles, which would
claim it was next door.

The reference calls the third line "Verified Client". There is no client badge
on this platform and inventing one would repeat the fault that the
verification rules exist to prevent, so the card says the strongest thing that
is true: that this professional has worked for them before, that the client's
identity has been checked, or that they are new. New is a fact about a client,
not a warning about one.

Each card also carries the services the job names, the date with how far off
it is, the budget, and one button. Three fit the column on a wide screen, two
on a laptop and one on a phone, and anything past three is a line to the board
rather than a longer list.

### Demo requests that were all the same sentence
Each demonstration professional was posting the same three job titles on the
same three dates for the same three budgets. With fifteen of them, one
professional's feed showed six rows all reading "Birthday party, 60 guests" at
ninety eight percent. They now draw from a wider list, each account from a
different point in it, with its own dates and budgets.

Not live yet.


### The Leads CRM ran off the side of the screen
Ali sent a photograph of the Leads list with the right-hand columns cut off the
screen and the whole page sliding sideways. The table is eight columns of small
labels, and every one of those labels was set never to break onto a second
line, so a single row wanted about 1,431 pixels. On anything narrower than a
very wide monitor the table grew straight through the card it sits in and took
the page with it: Budget, Stage, Priority and Last contacted were simply off
the screen, and so was the card's own edge.

Two changes. A label inside a table cell is allowed to wrap now, which brought
a row down to 976 pixels, and the table has a scroll of its own inside the
card, so on a narrow window it is the table that moves and never the page. On a
phone the rows still stack into blocks as before. The list now fits a 1,280
laptop with all eight columns showing.

### A page title with another page's subtitle under it
The blue bar at the top of every professional page showed the page name with
"Your business at a glance, Nov 13" beneath it. That line belongs to the
dashboard. Twenty-one professional pages name themselves and only six give a
subtitle of their own, so fifteen of them were carrying the dashboard's
greeting under their own title. The greeting now appears only on the dashboard.

The same bar hid its text entirely on any window under 1,200 pixels, leaving a
blue slab wrapped around a search box and nothing saying what you were looking
at. The page name is the part worth keeping, so the subtitle gives way first
and the search box after it. On a phone the bar now reads "Leads CRM".

### Proposals & Bids: three controls that did not do what they looked like
Ali asked whether anything on this screen was extra, whether the card was
right, and whether the buttons work. Three things were wrong.

**Export gave you everything.** Whatever the screen was showing, the button
handed back every proposal on the account. The note in the code above the
button said it sent the list as filtered, which made it worse: the claim was
written and not kept. The filtering now lives in one place and both the list
and the export ask it, and the button carries the filters in its link.

**"More Filters" opened nothing.** It was styled as a button, it carried a
count, and it was a span with no behaviour behind it. There were no more
filters to open; every filter the page has is on that row. The count was
really saying how many filters are on, so it says that in words, beside the
Clear that turns them off.

**The same question, asked three ways.** "All Proposal Statuses" and "All
Client Activity" said nothing the row of tabs beneath them does not already
say: Replied is the Needs My Response tab, no reply yet is Awaiting Client,
Accepted and Declined are tabs by those names. One screen with three
vocabularies for one question is three chances to disagree with itself. Both
dropdowns are gone. Finished work was the one thing only they offered, so it
is a tab now. What is left filters a different thing each: event type, a time
window, and the search box.

And the four cards along the top had the fault the Leads pipeline cards had
before they were rebuilt, which was missed at the time: everything piled
against the left edge with the right half of each card blank. Two rows across
the full width now, same as the Leads ones.

### Controls that could not do anything
Working outward from the Proposals screen, the same question was asked of
every professional page: is there a control here that cannot do what it looks
like it does?

- The Gig Operations Hub's gigs tab had two boxes reading "All Time", one over
  Gig Overview and one over Earnings Summary. Each had a single option, no
  name and no form to submit to. There was no period to choose.
- Five filters could only ever offer their own "all" line, because they are
  built from the account's own records and the account had none of that kind
  yet: event type on Leads, Proposals and Payments, and place and tag on My
  Clients. A filter with one option filters nothing, so they wait until there
  is a choice to make.

Checked and found honest: the payout method on Transactions is part of the
Request Payout form, not a filter, and the content filter beside it does
narrow the list.

### A whole job, walked from the professional's side
Ali asked for the real round trip tested: a client posts an event, a
professional bids, and nothing breaks for either of them.

There was already a walk of the client's path, but where the professional
should bid it wrote the bid straight into the database. So the bid wizard
itself, the six screens a professional actually fills in, had never been
walked by anything.

It is walked now, through the real routes, in forty-nine steps: the client
posts the request through their own wizard, it reaches the board and the
leads list, the professional fills in price, availability, plan, terms, files
and review, the bid appears on My Bids and in Proposals, the client compares
and starts the agreement, both sides accept and sign, a booking exists, every
professional screen that should know about it opens, the professional marks
it delivered, and the money is on the ledger. All forty-nine pass.

### Deep pass over every professional screen
Ali asked for a proper pass: every page, every width, every three-dot menu,
nothing left to report twice. Twenty-six pages were checked at seven window
widths, from a 390px phone to a 1600px monitor, 182 checks in all. What it
found:

**Three-dot menus that were links.** Priority Actions, the Gig Operations Hub
overview and the Hub's gigs list each drew a three-dot "more actions" control
and made it a link to the same place as the button beside it. Clicking it went
straight there instead of opening anything. The Hub's two are real menus now;
Priority Actions has one destination and nothing else to offer, so the
duplicate is gone rather than made into a menu of one item.

**A menu that opened where you could not see it.** Proposals & Bids built its
own menu, and the card its table scrolls inside cut the menu off: on the lower
rows you saw a sliver and thought the button was dead. All four menus share one
implementation now, which places the popup so that nothing can clip it.

**Three dead controls in Messages.** A telephone, a video camera and a
three-dot menu sat at the top of every conversation. None of them did
anything, and the first two offered calls this platform does not make.

**Five pages that did not say what they were.** Calendar, Team, Messages,
Reports and Reviews showed "Welcome back" in the blue bar instead of their own
name, because they never set one and the bar fell back to the dashboard's
greeting. All twenty-four professional pages name themselves now.

**Four tables wider than the window.** Leads at 1280, My Clients and Payments &
Payouts at 1024, and the Payments table again on a phone, where nine fixed
column widths ignored the instruction to stack. Each one took the whole page
into a sideways scroll with its right-hand columns off the screen. They scroll
inside their own card now. The filter row on My Services did the same thing
from 1150px down, and wraps instead.

No page has a horizontal scroll at any of the seven widths. No page logs a
script error. No image is missing.

### Every "Open the request" on the Leads CRM was a dead end
Worth saying on its own, because it is the biggest thing the pass turned up.

Every link on the Leads CRM that offered to open a request answered 403 or 410.
All seven of them. Six were requests for services the professional does not
offer, which the rules bar them from bidding on, and one had closed.

Behind it was something worse. The Leads list already leaves out an open
request in another state, on the stated grounds that a lead you are not
allowed to take is not a lead. It never applied the same reasoning to the rule
about services. So the demonstration account was shown thirteen new leads worth
$5,995, and not one of them could be touched. The figure was not small and it
was not real.

The question "can this professional bid on this?" now has one home, and the
bid wizard, the Leads list and the dashboard's Bid button all ask it. Leads
that cannot be bid on go to the request instead of the bidding form, and the
dashboard says "Closed to bids" rather than offering a button that fails.

Two consequences worth knowing before the demonstration. The New Lead count
changes, because it was counting work the account could not take. And the
demonstration data never contained a single open request addressed to what
these accounts actually offer, so the seeder now creates three per
professional: real rows, in their state, in a service they list, on which the
bidding form opens.

Also: a closed request answered 410 and there was no page for it, so it came
out as a bare error screen. It has a page now.

### Grey initials hanging out of the avatar
On the same list, the client's initials sat grey in the top-left corner of the
blue disc with half the letters outside it. One line of styling for the text
under a client's name was written loosely enough to catch the disc as well, and
being the more specific of the two it won: the disc lost both its centring and
its white letter. The one line now says which spans it means. The initials are
white and centred again.

### The pipeline strip: two thirds of every card was blank
Ali asked whether the five stage cards were right. They were not. Each card
held four short lines stacked against its left edge, so on a wide screen the
right two thirds of every card was empty. The card is two rows spanning its
full width now: the stage name along the top, the count and the money along
the bottom.

The second thing was worse than the spacing. Each card carried a bare "(15%)",
"(20%)", "(100%)", which reads as that stage's share of the pipeline. It is
not a share, and the five of them come to 165. It is the weight: the part of a
lead's budget a stage at that point is counted for, which Peter set in October,
and the money printed just above it has already had it applied. The card says
"15% weight" now, and the line above the strip says the totals are the budgets
after it.

Live: not yet deployed.

---

## 2026-11-12 (professional side)

### Four screens that were built, working, and empty
Agreements, Messages, Disputes and Availability all opened correctly and said
there was nothing to show, because for the demonstration accounts there
genuinely was nothing. In a demonstration that is indistinguishable from
unfinished.

Each now has something real behind it, on every demonstration professional
rather than a chosen few:

- Three agreements at the three stages the page exists to tell apart: one
  waiting on the professional, one on the client, one signed by both.
- A message thread with a real client about a real booking, with the client
  speaking last, so the "awaiting your reply" count means something.
- One dispute, filed and responded to, at the first of the four stages.
- A working calendar: days marked available, days marked unavailable, days
  already booked, and days not answered yet, which an empty calendar cannot
  tell apart.

Two mistakes of my own on the way, both caught before they mattered. The
Agreements page reads a different record than the one I first filled, so three
agreements existed and the page still said none. And the calendar entries were
being created afresh on every run, which an existing test for exactly that
caught.

### The service card, the way the owner drew it
On My Services & Pricing, the service name now sits inside the coloured band
with the status beside it, and the category line underneath is gone.

His reason was a good one. The same service can belong to two different
headings, like 360 Photo Booths and 360 Video Booths, and printing the heading
under each made two separate services look like one entered twice.

Checked with the longest service name on the site, which wraps onto three
lines without pushing the status badge out of the card, and at phone width in
both light and dark.

The List View needed nothing: its headings are already the categories, so the
rows never repeated them. That view is effectively the third option he
offered.

Not done, and he needs to choose: breaking up the service titles that contain
an "&". That contradicts the first instruction on his own master list, which
was to keep the 241 titles unchanged, and it would also break every
professional's saved services and every package that names one.

### Demo packages could not be found in Package Search
Nearly every demo package was labelled with words that are not services, so
the page that gets demonstrated returned nothing whatever a client ticked. The
labels are real service names now, in the demo data and in the rows that
already existed.

### Every service now says who may offer it
The owner's master service list, built. Until now every service in the system
was silently a professional's: there was no way to say that Valet Parking is
not something an influencer offers, or that Social Media Takeovers is not
something a caterer offers.

His list turned out to be an exact export of what we already have, all 241
titles matching and each under the same heading, so nothing had to be renamed
or moved. What it adds is the answer to that question: 213 for professionals
only, 28 for either, and 15 new ones for influencers.

The 15 are switched off, along with the heading that holds them, because he
called them proposals for approval. They come on when they are approved and
when influencers can actually be hired, which is the same moment. Until then a
client choosing services is not shown a heading with nothing behind it.

A professional is now offered 241 services, an influencer 43, and a client
searching sees all of them, because a client is not offering the service, they
are looking for someone who does. The rule is enforced when services are
saved, not only hidden on the screen.

One correction to something I reported earlier: of the two pairs of duplicate
services I flagged, only one is really a duplicate. 360 Photo Booths and 360
Video Booths are different things, and the owner's list is right to keep both.
Funeral Officiants appears twice under two headings, and that one still needs
his decision.

### The professional side, checked page by page
Every page of the professional portal opened and measured at phone width, the
same check that found the faults on the client side.

One page was broken outright: My Packages returned an error to any
professional whose package listed a crew. The booking page had already hit the
same fault months ago and worked around it in place, so this one went on
failing. Both now read that information the same way, through one shared
piece of code, so a third page cannot repeat it. While fixing it, it turned
out the same field has been saved three different ways over the life of the
site, which is now handled in that one place rather than guessed at by each
page.

Three more pages ran off the side of a phone: My Services & Pricing, Proposals
& Bids, and Transactions. The Gig Operations Hub did too, and its wide table
now scrolls instead of spilling.

And one desktop fault found on the way: the filter row on Transactions put
every control on its own line, so a row of five filters was five rows.

### Checking in on the day
The Gig Operations Hub now opens with what is happening today, and the one
action that belongs to the day itself: the professional marks that they are on
site. The client sees it on their booking and can confirm it.

Recorded as two separate facts rather than one. The professional saying they
have arrived is a claim and the client agreeing is a confirmation, and if
there is ever an argument about a morning, the difference between those two is
the whole of it.

It can only be done on the day of the event, only by the two people involved,
and only once. A mistaken tap can be undone the same day; after that it is a
record of what happened rather than something to tidy up.

### A package can now be sold at more than one level
From the sprint checklist. A professional building a package could set one
price and say how to read it, hourly or flat or starting-at. They can now offer
two or three levels instead, like Silver and Gold, each with its own price and
a line saying what makes it different.

The client sees the levels on the package page, each with its own button, and
the booking form opens on whichever one they clicked with the total matching.
Changing their mind on the form changes the total as they do it.

One decision worth recording: once a package has levels, the price shown on
the cards and in search is taken from the cheapest level rather than typed
separately. Otherwise a professional could advertise a figure the package
cannot actually be bought at, which is the sort of thing a client notices at
exactly the wrong moment.

## 2026-11-12

### Pages running off the side of a phone
Two pages had content wider than the screen with no sign that anything was
missing: the Dashboard by about a centimetre, Hired Talent by rather more. Both
were the same mistake in the layout, where a column is told to take the space
available but its minimum is still "as wide as the contents need", so the
contents set the width and push the rest off the edge.

On Hired Talent the four tabs across the top also became four blank pills on a
phone: the rule meant to hide the small line under each label was hiding the
label and the icon too, so nothing told them apart. And the card header put the
photograph, the name and the buttons on one line whatever the width, leaving
the name about sixty pixels and the buttons off the screen.

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

- **Duplicate services.** Funeral Officiants and Funeral Officiants & Ceremony
  Leaders are the same thing entered twice: identical lists beneath them.
  Waiting on which name to keep. (360 Photo Booths and 360 Video Booths were
  reported as a pair as well and are not one: the lists beneath them differ.)
- **Influencer hiring.** Peter confirmed "Both" means either way, and that the
  same-state rule applies. The remaining work is that influencers cannot be
  hired at all yet.
- **GigSalad layouts.** Waiting on whether specific pages should be followed,
  and on the exact wording of the designer's venue question.
