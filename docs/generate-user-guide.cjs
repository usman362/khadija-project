const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageBreak, PageNumber, TableOfContents, LevelFormat,
} = require("docx");

// ── CONSTANTS ──
const BLUE = "2563EB";
const DARK = "0F172A";
const GRAY = "64748B";
const LIGHT_BLUE = "DBEAFE";
const LIGHT_GRAY = "F1F5F9";
const WHITE = "FFFFFF";

const PAGE_W = 12240;
const PAGE_H = 15840;
const MARGIN = 1440;
const CONTENT_W = PAGE_W - MARGIN * 2; // 9360

const border = { style: BorderStyle.SINGLE, size: 1, color: "CBD5E1" };
const borders = { top: border, bottom: border, left: border, right: border };
const cellM = { top: 60, bottom: 60, left: 100, right: 100 };

// ── HELPERS ──
function h1(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_1, spacing: { before: 360, after: 200 }, children: [new TextRun({ text, bold: true, size: 36, font: "Arial", color: DARK })] });
}
function h2(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_2, spacing: { before: 280, after: 160 }, children: [new TextRun({ text, bold: true, size: 28, font: "Arial", color: BLUE })] });
}
function h3(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_3, spacing: { before: 200, after: 120 }, children: [new TextRun({ text, bold: true, size: 24, font: "Arial", color: DARK })] });
}
function p(text, opts = {}) {
  return new Paragraph({ spacing: { after: 120 }, ...opts, children: [new TextRun({ text, size: 22, font: "Arial", color: opts.color || "334155", ...opts.run })] });
}
function bold(text) { return new TextRun({ text, bold: true, size: 22, font: "Arial", color: DARK }); }
function normal(text) { return new TextRun({ text, size: 22, font: "Arial", color: "334155" }); }
function mixed(...parts) { return new Paragraph({ spacing: { after: 120 }, children: parts }); }

function headerCell(text, w) {
  return new TableCell({
    borders, width: { size: w, type: WidthType.DXA },
    shading: { fill: BLUE, type: ShadingType.CLEAR },
    margins: cellM,
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, size: 20, font: "Arial", color: WHITE })] })]
  });
}
function cell(text, w, opts = {}) {
  return new TableCell({
    borders, width: { size: w, type: WidthType.DXA },
    shading: opts.shade ? { fill: LIGHT_GRAY, type: ShadingType.CLEAR } : undefined,
    margins: cellM,
    children: [new Paragraph({ children: [new TextRun({ text, size: 20, font: "Arial", color: "334155", ...opts.run })] })]
  });
}
function table2(headers, rows, colWidths) {
  const tw = colWidths.reduce((a, b) => a + b, 0);
  return new Table({
    width: { size: tw, type: WidthType.DXA },
    columnWidths: colWidths,
    rows: [
      new TableRow({ children: headers.map((h, i) => headerCell(h, colWidths[i])) }),
      ...rows.map((row, ri) => new TableRow({
        children: row.map((c, ci) => cell(c, colWidths[ci], { shade: ri % 2 === 1 }))
      }))
    ]
  });
}
function pb() { return new Paragraph({ children: [new PageBreak()] }); }
function spacer() { return new Paragraph({ spacing: { after: 80 }, children: [] }); }

// ── NUMBERING ──
const numbering = {
  config: [
    {
      reference: "bullets", levels: [{
        level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 720, hanging: 360 } } }
      }]
    },
    {
      reference: "numbers", levels: [{
        level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 720, hanging: 360 } } }
      }]
    },
    {
      reference: "sub-bullets", levels: [{
        level: 0, format: LevelFormat.BULLET, text: "\u25E6", alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 1080, hanging: 360 } } }
      }]
    },
  ]
};

function bullet(text) {
  return new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 60 }, children: [normal(text)] });
}
function num(text) {
  return new Paragraph({ numbering: { reference: "numbers", level: 0 }, spacing: { after: 60 }, children: [normal(text)] });
}
function subBullet(text) {
  return new Paragraph({ numbering: { reference: "sub-bullets", level: 0 }, spacing: { after: 40 }, children: [normal(text)] });
}

// ── DOCUMENT ──
const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 36, bold: true, font: "Arial", color: DARK },
        paragraph: { spacing: { before: 360, after: 200 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: "Arial", color: BLUE },
        paragraph: { spacing: { before: 280, after: 160 }, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, font: "Arial", color: DARK },
        paragraph: { spacing: { before: 200, after: 120 }, outlineLevel: 2 } },
    ]
  },
  numbering,
  sections: [
    // ── COVER PAGE ──
    {
      properties: {
        page: { size: { width: PAGE_W, height: PAGE_H }, margin: { top: MARGIN, right: MARGIN, bottom: MARGIN, left: MARGIN } }
      },
      children: [
        new Paragraph({ spacing: { before: 4000 }, children: [] }),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 200 }, children: [
          new TextRun({ text: "GigResource", size: 72, bold: true, font: "Arial", color: BLUE })
        ]}),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 100 }, children: [
          new TextRun({ text: "Platform User Guide", size: 48, font: "Arial", color: DARK })
        ]}),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 600 }, border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: BLUE, space: 1 } }, children: [
          new TextRun({ text: "Complete Feature Documentation for Administrators, Clients & Professionals", size: 24, font: "Arial", color: GRAY })
        ]}),
        new Paragraph({ spacing: { before: 2000 }, alignment: AlignmentType.CENTER, children: [
          new TextRun({ text: "Version 1.0  |  April 2026", size: 22, font: "Arial", color: GRAY })
        ]}),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 200 }, children: [
          new TextRun({ text: "Confidential \u2014 For Client Use Only", size: 20, font: "Arial", color: GRAY, italics: true })
        ]}),
      ]
    },

    // ── TABLE OF CONTENTS ──
    {
      properties: {
        page: { size: { width: PAGE_W, height: PAGE_H }, margin: { top: MARGIN, right: MARGIN, bottom: MARGIN, left: MARGIN } }
      },
      headers: {
        default: new Header({ children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ text: "GigResource User Guide", size: 18, font: "Arial", color: GRAY, italics: true })] })] })
      },
      footers: {
        default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: "Page ", size: 18, font: "Arial", color: GRAY }), new TextRun({ children: [PageNumber.CURRENT], size: 18, font: "Arial", color: GRAY })] })] })
      },
      children: [
        h1("Table of Contents"),
        new TableOfContents("Table of Contents", { hyperlink: true, headingStyleRange: "1-3" }),
        pb(),

        // ── 1. PLATFORM OVERVIEW ──
        h1("1. Platform Overview"),
        p("GigResource is an event booking marketplace that connects clients who need event services with verified professionals who provide them. The platform also includes an influencer referral program for growth."),
        h2("User Roles"),
        table2(["Role", "Description", "Dashboard URL"], [
          ["Client", "Posts events, hires professionals, manages bookings", "/client/dashboard"],
          ["Professional", "Offers services, browses gigs, submits proposals, tracks earnings", "/professional/dashboard"],
          ["Influencer", "Refers new users via unique link, earns commissions", "/influencer/dashboard"],
          ["Admin", "Manages the entire platform: users, events, payments, settings", "/dashboard (admin view)"],
        ], [1800, 5000, 2560]),
        pb(),

        // ── 2. REGISTRATION & AUTH ──
        h1("2. User Registration & Authentication"),
        h2("How to Register"),
        num("Visit the website and click the registration button in the top-right corner."),
        num("Choose your role: 'Join as Professional' or 'Join as Client'."),
        num("Fill in your name, email address, and create a password (minimum 8 characters)."),
        num("Click Register. You will be redirected to your dashboard."),
        spacer(),
        h2("Login & Logout"),
        bullet("Login at /login with your email and password."),
        bullet("Logout via the user card at the bottom of the sidebar."),
        bullet("Failed login attempts are recorded in the Activity Log for security."),
        spacer(),
        h2("Password Reset"),
        bullet("Click 'Forgot Your Password?' on the login page."),
        bullet("Enter your email \u2014 a reset link will be sent."),
        bullet("Click the link, set a new password, and log in."),
        pb(),

        // ── 3. PROFILE MANAGEMENT ──
        h1("3. Profile Management"),
        p("Both clients and professionals have a tabbed profile page accessible from the sidebar under 'Profile & Settings'."),
        h2("Profile Tabs"),
        table2(["Tab", "Available For", "What It Contains"], [
          ["General Info", "Both", "Name, email, phone, date of birth, gender, address, bio"],
          ["Company Info", "Client", "Company name, website, industry"],
          ["Professional Info", "Professional", "Hourly rate, availability, experience, skills, languages"],
          ["Portfolio", "Professional", "Portfolio links and certifications"],
          ["Social Links", "Both", "LinkedIn, Twitter/X, Facebook, Instagram URLs"],
          ["Notifications", "Both", "Email notification toggles for bookings, messages, events, marketing"],
          ["Change Password", "Both", "Current password + new password form"],
          ["Account Modes", "Both", "Enable/switch between Client and Professional modes"],
          ["Danger Zone", "Both", "Account deletion request form"],
        ], [2200, 1800, 5360]),
        spacer(),
        h2("Avatar / Profile Photo"),
        bullet("Click the camera icon on your profile picture to upload a new photo."),
        bullet("Accepted formats: JPG, PNG, WebP (max 2MB)."),
        bullet("Click 'Remove photo' to revert to the auto-generated initials avatar."),
        pb(),

        // ── 4. PROFILE SWITCHING ──
        h1("4. Profile Switching (Client \u2194 Professional)"),
        p("Like Freelancer or Upwork, a single user can act as both a Client and a Professional. You start with one role and can enable the other anytime."),
        h2("Enabling the Second Role"),
        num("Go to your Profile page."),
        num("Click the 'Account Modes' tab."),
        num("You will see two cards: Client Mode and Professional Mode."),
        num("Click 'Become a Professional' (or 'Become a Client')."),
        num("A confirmation modal will appear explaining what you will get."),
        num("Click confirm. Your new role is instantly enabled and you are redirected to that dashboard."),
        spacer(),
        h2("Switching Between Modes"),
        p("Once you have both roles enabled, a quick-switch button appears in the top-right of your navigation bar:"),
        bullet("Blue dot + 'CLIENT' label \u2014 you are currently in Client mode."),
        bullet("Green dot + 'PROFESSIONAL' label \u2014 you are currently in Professional mode."),
        bullet("Click 'Switch to Professional' or 'Switch to Client' to instantly swap."),
        spacer(),
        mixed(bold("Note: "), normal("The active mode is session-based. When you log out and back in, you will land on your primary (original) role.")),
        pb(),

        // ── 5. PAYMENT INTEGRATION ──
        h1("5. Payment Integration (Stripe & PayPal)"),
        p("The platform supports two payment gateways for processing transactions:"),
        table2(["Gateway", "Used For", "Configuration"], [
          ["Stripe", "Card payments for subscriptions and reactivation fees", "Settings \u2192 Payment Settings \u2192 Stripe section"],
          ["PayPal", "PayPal account payments for same purposes", "Settings \u2192 Payment Settings \u2192 PayPal section"],
        ], [1800, 4000, 3560]),
        spacer(),
        h2("Admin Configuration"),
        bullet("Navigate to Settings \u2192 Payment Settings in the admin panel."),
        bullet("Choose the Active Gateway (Stripe or PayPal)."),
        bullet("Set Mode: Test (sandbox) for development, Live for production."),
        bullet("Enter your API keys for the chosen gateway."),
        bullet("Set the default currency (e.g., USD, EUR, GBP)."),
        pb(),

        // ── 6. MEMBERSHIP PLANS ──
        h1("6. Membership Plans & Billing"),
        h2("For Users"),
        num("Navigate to Membership Plans from the sidebar."),
        num("Browse available plans with feature comparisons."),
        num("Click 'Switch to [Plan Name]' to subscribe."),
        num("A confirmation modal shows the plan name, price, and billing cycle."),
        num("Click confirm to proceed to the payment page (Stripe or PayPal)."),
        num("After payment, your subscription activates immediately and a confirmation email is sent."),
        spacer(),
        h2("Cancelling a Plan"),
        bullet("At the bottom of the Membership Plans page, click 'Cancel Plan'."),
        bullet("A confirmation modal explains that features remain until the billing period ends."),
        bullet("After confirmation, the subscription is cancelled."),
        spacer(),
        h2("For Admin"),
        bullet("Manage plans at Administration \u2192 Manage Plans."),
        bullet("Create, edit, or delete plans with custom features, pricing, and duration."),
        pb(),

        // ── 7. EVENT MANAGEMENT ──
        h1("7. Event Management"),
        h2("For Clients"),
        bullet("Post new events with title, description, dates, categories, and location."),
        bullet("View your events in a list or calendar view."),
        bullet("Filter by status (pending, published, confirmed, completed, cancelled) or category."),
        bullet("Publish events to make them visible to professionals in the marketplace."),
        spacer(),
        h2("For Professionals"),
        bullet("'My Gigs' tab shows events assigned to you."),
        bullet("'Browse' tab shows the marketplace \u2014 published events you can apply to."),
        bullet("Submit proposals on events you are interested in."),
        bullet("Track stats: total gigs, active, upcoming, completed."),
        spacer(),
        h2("For Admin"),
        bullet("Full control over all events (create, edit, delete any event)."),
        bullet("Stats dashboard showing counts by status."),
        bullet("Advanced filters: search, status, source, published state, date range, sorting."),
        bullet("Assign clients, suppliers, and categories to events."),
        spacer(),
        h2("Event Categories (Admin Only)"),
        bullet("Found in the sidebar under Event Management \u2192 Event Categories."),
        bullet("Supports parent/child tree structure (e.g., 'Wedding' \u2192 'Photography', 'Catering')."),
        bullet("Each category can have a cover image, thumbnail, icon, and sort order."),
        bullet("Toggle active/inactive. Used on the public Events & Categories page."),
        pb(),

        // ── 8. BOOKING SYSTEM ──
        h1("8. Booking System"),
        h2("Booking Lifecycle"),
        table2(["Status", "Meaning", "Who Changes It"], [
          ["Requested", "Client has initiated a booking", "System (auto on create)"],
          ["Confirmed", "Both parties agreed (or AI agreement fully accepted)", "Client/Supplier/System"],
          ["Completed", "Event is done, work delivered", "Client or Admin"],
          ["Cancelled", "Booking was cancelled by either party", "Client, Supplier, or Admin"],
        ], [1800, 4500, 3060]),
        spacer(),
        bullet("All status changes are recorded in the Agreement Log for audit."),
        bullet("When a booking is cancelled, both the client and professional receive an email notification (the person who cancelled does not get the email)."),
        bullet("When a booking is completed and the client was referred by an influencer, the influencer automatically earns a commission."),
        pb(),

        // ── 9. AI AGREEMENTS ──
        h1("9. AI Agreements"),
        p("The platform can generate AI-powered agreements between clients and professionals based on their chat history."),
        h2("How It Works"),
        num("A client and professional must have an active conversation about a booking."),
        num("Either party (or the admin) clicks 'Generate AI Agreement'."),
        num("The system uses AI to create a formal agreement based on the discussion."),
        num("Both parties review and accept the agreement."),
        num("When both accept, the status becomes 'Fully Accepted' and the booking is auto-confirmed."),
        num("Either party can reject with a reason, and a new version can be regenerated."),
        pb(),

        // ── 10. INFLUENCER PROGRAM ──
        h1("10. Influencer Program"),
        h2("Step 1: Apply"),
        bullet("Visit /join-as-influencer (public page, accessible from the main website)."),
        bullet("Fill in: full name, email, social media links, audience description, monthly reach."),
        bullet("Submit the application. Status becomes 'Pending'."),
        spacer(),
        h2("Step 2: Admin Reviews"),
        bullet("Admin navigates to Influencers page in the admin panel."),
        bullet("Reviews pending applications with social links and audience details."),
        bullet("Approve: influencer role is assigned, referral link becomes active."),
        bullet("Reject: application is declined, no role assigned."),
        spacer(),
        h2("Step 3: Share Referral Link"),
        bullet("Once approved, the influencer gets a unique link: /ref/{8-character-code}"),
        bullet("This link is displayed on their Influencer Dashboard and can be copied with one click."),
        bullet("Share on social media, email campaigns, blogs, WhatsApp, etc."),
        spacer(),
        h2("Step 4: Earn Money"),
        mixed(bold("Signup Bonus: "), normal("$5.00 for each new user who registers through the referral link.")),
        mixed(bold("Booking Commission: "), normal("15\u201330% of the booking price when a referred user's booking is completed.")),
        spacer(),
        h2("Commission Tiers (Auto-Upgrade)"),
        table2(["Tier", "Commission Rate", "Required Referrals"], [
          ["Starter", "15%", "0+ (everyone starts here)"],
          ["Rising", "20%", "11+ successful referrals"],
          ["Pro", "25%", "26+ successful referrals"],
          ["Elite", "30%", "51+ successful referrals"],
        ], [2000, 3680, 3680]),
        p("Tiers upgrade automatically as your referral count grows. Higher tiers earn more on every future booking."),
        spacer(),
        h2("Influencer Dashboard"),
        bullet("Stats overview: total earnings, available balance, paid out, referral count, current tier."),
        bullet("Referrals tab: full history of signup bonuses and booking commissions."),
        bullet("Payouts tab: request withdrawals and view payout history."),
        spacer(),
        h2("Payout Withdrawal"),
        num("Go to Influencer Dashboard \u2192 Payouts tab."),
        num("Enter amount ($50 minimum), payout method (PayPal/Bank/Other), and account details."),
        num("Submit. The amount is reserved from your available balance."),
        num("Admin reviews the request and either marks it as Paid or Rejects it."),
        num("If rejected, the reserved amount is returned to your balance."),
        num("Email notifications are sent at each step."),
        pb(),

        // ── 11. E-SIGNATURE ──
        h1("11. E-Signature System"),
        p("Users can electronically sign the Privacy Policy and AI Usage Agreement directly on the website."),
        h2("How to Sign"),
        num("Visit /privacy-policy or /ai-agreement."),
        num("Scroll to the bottom of the page."),
        num("If you are not logged in, you will see a prompt to log in first."),
        num("If logged in, choose your signature method:"),
        subBullet("Type Signature: type your full name (displayed in a cursive font)."),
        subBullet("Draw Signature: draw your signature on the canvas using mouse or touch."),
        num("Review the metadata strip (your name, date, policy version)."),
        num("Click 'I Agree & Sign'."),
        spacer(),
        h2("After Signing"),
        bullet("A green badge appears confirming your signature with date, method, and version."),
        bullet("Your signature is stored with your IP address and user agent for legal compliance."),
        bullet("If the policy is updated (new version), you may need to sign again."),
        pb(),

        // ── 12. ACCOUNT DELETION & REACTIVATION ──
        h1("12. Account Deletion & Reactivation"),
        h2("Requesting Account Deletion"),
        num("Navigate to Profile \u2192 Danger Zone tab."),
        num("Read the warning information carefully."),
        num("Fill in: reason for leaving (optional), current password, and type 'DELETE' in capitals."),
        num("Click 'Request Account Deletion'."),
        num("Your account is now scheduled for permanent deletion in 60 days."),
        spacer(),
        h2("The 60-Day Grace Period"),
        bullet("Your account is immediately locked \u2014 no bookings, messages, or other actions are possible."),
        bullet("When you log in, you see the Restore page with a countdown showing days remaining."),
        bullet("You can restore your account at any time during this period."),
        spacer(),
        h2("Restoring Your Account"),
        mixed(bold("If reactivation fee is disabled: "), normal("Click 'Restore My Account' \u2014 your account is restored instantly for free.")),
        mixed(bold("If reactivation fee is enabled ($4.99 default): "), normal("Choose 'Pay with Card (Stripe)' or 'Pay with PayPal'. Complete the payment, and your account is restored. A confirmation email is sent.")),
        spacer(),
        h2("After 60 Days (No Action Taken)"),
        bullet("An automated system runs daily and permanently anonymizes expired accounts."),
        bullet("Your name becomes 'Deleted User', email is scrubbed, avatar is deleted."),
        bullet("Bookings and messages are preserved for audit but your identity is removed."),
        bullet("You can no longer log in. This action is irreversible."),
        spacer(),
        h2("Admin Controls"),
        bullet("View all deletion requests at Administration \u2192 Deletion Requests."),
        bullet("See status badges: days remaining (blue/orange) or 'Expired' (red)."),
        bullet("Restore any account instantly (no fee required for admin)."),
        bullet("Configure the reactivation fee at Settings \u2192 Account Deletion."),
        pb(),

        // ── 13. EMAIL NOTIFICATIONS ──
        h1("13. Automated Email Notifications"),
        p("The platform sends branded emails automatically at key moments:"),
        table2(["Email", "When It Is Sent", "Sent To"], [
          ["Payout Request Received", "Influencer submits a withdrawal request", "Influencer"],
          ["Payout Processed", "Admin marks a payout as paid", "Influencer"],
          ["Payout Declined", "Admin rejects a payout request", "Influencer"],
          ["Booking Cancelled", "A booking status changes to cancelled", "Client & Professional"],
          ["Payment Confirmation", "Membership subscription payment succeeds", "Paying user"],
          ["Account Reactivation", "Account reactivation payment succeeds", "Reactivated user"],
        ], [2800, 4000, 2560]),
        spacer(),
        bullet("All emails include: branded header, status banner, transaction details, action button, and footer."),
        bullet("Email failures are logged but never interrupt the actual operation."),
        pb(),

        // ── 14. ACTIVITY LOGGING ──
        h1("14. Activity Logging (Security Audit)"),
        p("All security-sensitive actions are automatically recorded for admin review."),
        h2("Actions Tracked"),
        table2(["Action", "Description", "Data Captured"], [
          ["Login", "User successfully logged in", "User ID, IP, user agent, timestamp"],
          ["Logout", "User signed out", "Same as above"],
          ["Failed Login", "Incorrect credentials entered", "Attempted email, IP, user agent"],
          ["Password Changed", "User changed password from profile", "User ID, IP, timestamp"],
          ["Password Reset", "User reset password via email link", "User ID, IP, timestamp"],
          ["Role Enabled", "User enabled Client or Professional mode", "User ID, new role, timestamp"],
        ], [2200, 3600, 3560]),
        spacer(),
        h2("Admin View"),
        bullet("Navigate to Administration \u2192 Activity Log."),
        bullet("Stat cards show totals for each action type (clickable to filter)."),
        bullet("Filter by: user name/email, action type, IP address, date range."),
        bullet("Table shows user avatar, name, color-coded action badge, IP, and user agent."),
        bullet("Deleted users still appear in logs with a 'deleted' badge."),
        bullet("Logs are kept forever \u2014 no automatic cleanup."),
        pb(),

        // ── 15. BLOG MANAGEMENT ──
        h1("15. Blog Management"),
        h2("Admin: Managing Blog Posts"),
        num("Navigate to Blog Management \u2192 Posts in the admin sidebar."),
        num("Click 'New Post' to create an article."),
        num("Fill in: title, slug (auto-generated if blank), excerpt, and content (rich text editor)."),
        num("Upload a featured image (JPG, PNG, WebP, max 4MB)."),
        num("Set category, SEO fields (meta title and description), and status."),
        num("Status options: Draft (not visible), Published (live on website), Archived (hidden)."),
        num("Set a publish date for scheduled publishing, or leave blank for immediate."),
        num("Click Save. The post is now live (if published)."),
        spacer(),
        h2("Admin: Managing Blog Categories"),
        bullet("Navigate to Blog Management \u2192 Categories."),
        bullet("Create categories with name, description, active toggle, and sort order."),
        bullet("Edit or delete categories. Posts in a deleted category become uncategorized."),
        spacer(),
        h2("Public Blog Pages"),
        mixed(bold("/blog "), normal("\u2014 Blog listing page with search bar, category filter pills, 'Most Popular' section, and 3-column card grid with pagination.")),
        mixed(bold("/blog/{slug} "), normal("\u2014 Full article view with author info, reading time, view count, featured image, rich content, social share buttons, and related posts.")),
        bullet("Blog link is visible in the main navigation bar and website footer."),
        pb(),

        // ── 16. ADMIN NAVIGATION ──
        h1("16. Admin Dashboard & Navigation"),
        p("The admin sidebar is organized into logical sections:"),
        table2(["Section", "Items"], [
          ["Main", "Dashboard"],
          ["Event Management", "Events, Bookings, Event Categories, AI Agreements, Messages"],
          ["Account", "My Profile"],
          ["Billing", "Membership Plans, Payment History"],
          ["Administration", "Manage Plans, Agreement Log, Users, Deletion Requests, Activity Log, Influencers, Roles, Permissions, FAQ Management, Policy Pages, Blog Management"],
          ["Settings", "Payment Settings, OpenAI Settings, reCAPTCHA Settings, Account Deletion"],
        ], [2400, 6960]),
        spacer(),
        bullet("The 'Deletion Requests' link shows a red badge with the count of pending requests."),
        bullet("Blog Management expands into a submenu with Posts and Categories."),
        bullet("Settings expands into a submenu with four configuration pages."),
        pb(),

        // ── APPENDIX A: URL REFERENCE ──
        h1("Appendix A: URL Reference"),
        h2("Public Pages"),
        table2(["URL", "Page"], [
          ["/", "Landing Page"], ["/about-us", "About Us"], ["/events-categories", "Events & Categories"],
          ["/blog", "Blog Listing"], ["/blog/{slug}", "Blog Article Detail"],
          ["/privacy-policy", "Privacy Policy (with e-signature)"], ["/ai-agreement", "AI Usage Agreement (with e-signature)"],
          ["/payment-policy", "Payment Policy"], ["/cancellation-policy", "Cancellation Policy"],
          ["/join-as-influencer", "Influencer Application Form"], ["/ref/{code}", "Referral Link Landing"],
        ], [3500, 5860]),
        spacer(),
        h2("Client Dashboard"),
        table2(["URL", "Page"], [
          ["/client/dashboard", "Client Dashboard"], ["/client/events", "My Events"],
          ["/client/bookings", "My Bookings"], ["/client/messages", "Messages"],
          ["/client/profile", "Profile & Settings"],
        ], [3500, 5860]),
        spacer(),
        h2("Professional Dashboard"),
        table2(["URL", "Page"], [
          ["/professional/dashboard", "Professional Dashboard"], ["/professional/gigs", "My Gigs & Browse"],
          ["/professional/proposals", "Proposals"], ["/professional/earnings", "Earnings"],
          ["/professional/messages", "Messages"], ["/professional/profile", "Profile & Settings"],
        ], [3500, 5860]),
        spacer(),
        h2("Influencer Dashboard"),
        table2(["URL", "Page"], [
          ["/influencer/dashboard", "Influencer Dashboard"], ["/influencer/referrals", "Referral History"],
          ["/influencer/payouts", "Payout Requests"],
        ], [3500, 5860]),
        spacer(),
        h2("Admin Panel"),
        table2(["URL", "Page"], [
          ["/app/admin/events", "All Events (CRUD)"], ["/app/admin/categories", "Event Categories"],
          ["/app/admin/blog/posts", "Blog Posts"], ["/app/admin/blog/categories", "Blog Categories"],
          ["/app/influencers", "Influencer Management"], ["/app/influencers/payouts", "Payout Management"],
          ["/app/admin/deletion-requests", "Deletion Requests"], ["/app/admin/activity-logs", "Activity Log"],
          ["/app/admin/settings/payments", "Payment Settings"], ["/app/admin/settings/account-deletion", "Account Deletion Settings"],
          ["/app/users", "User Management"], ["/app/roles", "Role Management"], ["/app/permissions", "Permission Management"],
        ], [4200, 5160]),
        pb(),

        // ── APPENDIX B: CONFIGURATION DEFAULTS ──
        h1("Appendix B: Configuration Defaults"),
        table2(["Setting", "Default Value", "Where to Change"], [
          ["Reactivation Fee Enabled", "Yes", "Settings \u2192 Account Deletion"],
          ["Reactivation Fee Amount", "$4.99", "Settings \u2192 Account Deletion"],
          ["Reactivation Currency", "USD", "Settings \u2192 Account Deletion"],
          ["Influencer Signup Bonus", "$5.00", "config/influencer.php"],
          ["Min Payout Threshold", "$50.00", "config/influencer.php"],
          ["Referral Cookie Duration", "30 days", "config/influencer.php"],
          ["Payment Gateway", "Stripe", "Settings \u2192 Payment Settings"],
          ["Payment Mode", "Test (Sandbox)", "Settings \u2192 Payment Settings"],
          ["Payment Currency", "USD", "Settings \u2192 Payment Settings"],
          ["Account Purge Schedule", "Daily at 03:10 AM", "routes/console.php"],
          ["Deletion Grace Period", "60 days", "AccountDeletionController"],
        ], [2800, 2800, 3760]),
        spacer(),
        spacer(),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 600 }, border: { top: { style: BorderStyle.SINGLE, size: 6, color: BLUE, space: 1 } }, children: [
          new TextRun({ text: "End of Document", size: 22, font: "Arial", color: GRAY, italics: true })
        ]}),
        new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 200 }, children: [
          new TextRun({ text: "GigResource Platform User Guide v1.0 \u2014 April 2026", size: 20, font: "Arial", color: GRAY })
        ]}),
      ]
    },
  ]
});

// ── GENERATE ──
const outPath = "/Users/muhammadusman/Sites/khadija-project/docs/GigResource-User-Guide.docx";
Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync(outPath, buffer);
  console.log("User guide generated: " + outPath);
  console.log("Size: " + (buffer.length / 1024).toFixed(1) + " KB");
});
