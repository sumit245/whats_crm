# DnD Connect Design System v2 — Sign-off

## Before testing

1. Hard refresh: Ctrl+Shift+R (clears cached CSS).
2. Optional: `php artisan view:clear` on server.

## Light theme

- [ ] Dashboard: green Add Device; muted blast badges; no blue widgets
- [ ] Chat: + New Chat green (same as Flows New flow)
- [ ] Flows: + New flow green
- [ ] Agents: + New Team and + Add Agent same green
- [ ] Phonebook: Clear Phonebook red (same family as Delete All)
- [ ] Settings: Change Password / Save green; inputs readable
- [ ] Header: no heavy line under navbar; bell badge close to icon
- [ ] Notifications dropdown: icons visible, text readable

## Dark theme

1. Open theme customizer → Dark (or Semi-Dark, maps to dark).
2. Reload page.

- [ ] Sidebar, header, footer same dark family (no white header strip)
- [ ] Form inputs: light text on dark surface; placeholders visible
- [ ] Modals: inputs not black-on-black

## Theme persistence

- [ ] Switch Light → reload → stays light
- [ ] Switch Dark → reload → stays dark

## Files changed (reference)

- `public/themes/mpwa/css/dnd-tokens.css`, `dnd-forms.css`, `dnd-buttons.css`, `dnd-chrome.css`
- `public/themes/mpwa/assets/css/bootstrap-extended.css` (primary hex)
- `resources/themes/mpwa/views/components/layout-dashboard.blade.php`, `header.blade.php`
- Pages: chat, agents, phonebook, settings, home, campaign/create, contacts/import, admin/user tickets, themes
