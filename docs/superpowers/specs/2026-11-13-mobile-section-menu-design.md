# Mobile Section Menu Design

## Intent

On small screens, replace the always-visible tab row with a full-width control labeled with the current section. Tapping it reveals the section links in a vertical panel that slides down. Keep the existing desktop tab navigation.

## Goals and constraints

- Use the shared header so behavior is consistent on all authenticated pages.
- Keep the same destinations, active-page marker, and accessible navigation label.
- Use the existing CSS and JavaScript; add no dependencies.
- Keep links visible if JavaScript is unavailable.
- Respect reduced-motion settings.

## Approaches

- **Chosen: one responsive navigation with a mobile toggle.** Preserves one set of links and existing destinations; the toggle appears only on mobile.
- A separate mobile copy of the links would duplicate markup and risk inconsistent active states.
- A select menu would be compact but would not provide the requested slide-down reveal.

## Design decisions

The mobile control shows the active section name and an expand/collapse chevron. It controls the existing shared navigation links, which form a vertical, animated panel on mobile. Desktop tabs remain visible in their current layout. The panel closes when a link is chosen, when the user clicks outside it, or presses Escape. JavaScript enables the collapsed state progressively so links remain available without it.
