# Ownership Rules: AI-Derived vs User-Confirmed Data

This document defines how the system treats data that may come from AI (e.g. suggestions, drafts) versus data explicitly confirmed by users. It is part of the Month 1 non-negotiable architecture.

## Principles

1. **User-confirmed data** is the source of truth for commitments (agreements, bookings, message content). Only user-confirmed values are persisted as final state where it matters.
2. **AI-derived data** may be used for suggestions, defaults, or drafts. It must not be treated as confirmed until the user explicitly accepts or submits it.
3. **Auditability**: Where we later introduce AI suggestions, we will distinguish in storage or metadata whether a value was user-confirmed or AI-suggested (e.g. flags or separate draft tables).

## Current state (Month 1)

- **Events, bookings, messages:** Each has a `source` column (`user` | `ai` | `system`). Default is `user`. All creation flows set or default to `user`; API accepts optional `source` for future AI integration.
- **Roles and permissions:** Assigned by admins; no AI-driven assignment.

## Future placeholders

- **Event/booking drafts:** If we add AI-generated drafts, they will be stored as drafts (e.g. `status = draft` or a separate draft store) until the user confirms. Confirmed data will remain in the main event/booking records.
- **Message suggestions:** If we add AI-suggested replies, they will not be written to the immutable message log until the user sends the message. Only user-sent content is inserted into `messages`.
- **Agreement terms:** Any AI-suggested terms (e.g. for a booking or contract) will be stored as proposals until user confirmation; the confirmed version will be the one used for agreement orchestration.

## References

- Immutable message log: `docs/roles-permissions.md` (messages insert-only), `docs/architecture.md` (Messaging).
- Agreement orchestration: Booking status lifecycle; see `docs/architecture.md` (Clear separation).
