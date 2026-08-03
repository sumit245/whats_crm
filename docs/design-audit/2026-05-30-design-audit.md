# DnD Connect Design Audit — 2026-05-30

Brand kit: [brandkit-3x3.png](./brandkit-3x3.png)

## Summary

Prior polish added partial CSS tokens but left **Bootstrap Extended** (`#3461ff` primary) and mixed button classes (`btn-primary` / `btn-success` / `btn-info`). Design System v2 fixes root causes and standardizes light + dark themes.

## Root causes (fixed)

| Issue | Fix |
|-------|-----|
| Blue primary on Flows/Agents | `dnd-buttons.css` + `bootstrap-extended.css` primary → `#128c7e` |
| Green only on Chat (`btn-success`) | Blade migration to `btn-primary`; success aliased to brand in CSS |
| Phonebook Clear grey vs Delete red | Both bulk actions use `btn-danger` for clear; delete all unchanged |
| Strong header border | Hairline shadow (`--dnd-header-separator`), no bottom border |
| semi-dark hybrid header/sidebar | Default `light-theme`; `localStorage` `dnd-theme`; semi-dark maps to dark tokens |
| Notification unreadable | `material-icons` → Bootstrap Icons; dropdown token colors; tighter badge |
| Placeholders / contrast | `dnd-forms.css` + `--dnd-text-placeholder` |
| Settings off-green/blue mix | Restructured page; all actions `btn-primary` / `btn-danger` |
| Document title `Agents &amp; Teams` | `html_entity_decode` on `<title>` |

## Browser verification (light theme)

| Route | Status | Notes |
|-------|--------|-------|
| `/en/home` | Pass | Unified chrome; muted status badges; green widget icons |
| `/en/chat` | Pass | New Chat uses primary (green) |
| `/en/agents` | Pass | New Team + Add Agent both primary; page title shows "Agents & Teams" |
| `/en/flows` | Not re-checked this session | Uses `btn-primary` in Blade |
| `/en/phonebook` | Code fixed | Clear = danger; import = outline |
| `/en/user/settings` | Code fixed | Card layout; primary buttons |

Notification dropdown: readable in snapshot (`test_flow_template PENDING → APPROVED`).

## Remaining follow-ups (P2)

- **Dark theme toggle**: Use theme customizer "Dark" or set `localStorage.setItem('dnd-theme','dark')` and reload. Re-verify contrast on legacy pages (file-manager, autoreply, LFM vendor views).
- **Ajax partials** under `views/ajax/` may still use old button classes; CSS aliases cover most cases.
- **Campaign create** wizard: confirm step UI in browser after cache bust.
- **Screenshots**: Automated capture timed out; re-capture manually if needed for stakeholder deck.

## CSS load order

`dnd-tokens` → `dnd-components` → `dnd-forms` → `dnd-buttons` → `dnd-pages` → `dashboard.css` → `dnd-chrome`

## Sign-off checklist

- [x] Primary buttons use brand green globally
- [x] No default semi-dark on first paint
- [x] Header uses shadow separator, not heavy border
- [x] Notification bell icons and badge positioning
- [x] Form placeholder tokens in both themes
- [x] Semantic destructive actions on phonebook
- [x] Design rule v2 in `.cursor/rules/dnd-design-system.mdc`
- [ ] Full dark-theme walk (user: toggle Dark in customizer)
- [ ] Hard refresh (Ctrl+Shift+R) after deploy
