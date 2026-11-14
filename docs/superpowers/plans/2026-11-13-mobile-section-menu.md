# Mobile Section Menu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make shared admin tabs a current-section menu that slides open on mobile while preserving desktop tabs.

**Architecture:** Add a mobile-only button before the existing shared navigation and label it from the server's active route. Extend the existing header script to add accessible toggle, outside-click, and Escape behavior; CSS will animate the existing links only on mobile and honor reduced motion.

**Tech Stack:** PHP templates, CSS, vanilla JavaScript.

**Spec:** `docs/superpowers/specs/2026-11-13-mobile-section-menu-design.md`

## Global Constraints

- Keep the same destinations, active-page marker, and accessible navigation label.
- Add no dependencies.
- Keep links visible if JavaScript is unavailable.
- Respect reduced-motion settings.

## Review Focus

- Desktop widths: the toggle stays hidden and the current tab layout remains intact.
- Mobile without JavaScript: all navigation links remain visible.
- Keyboard use: Enter/Space toggles, Escape closes and restores focus.
- Motion preference: reduced-motion removes the slide animation.

---

### Task 1: Add responsive shared section menu

**Files:**
- Modify: `root/app/Views/layouts/header.php`
- Modify: `root/public/assets/css/styles.css`
- Modify: `root/public/assets/js/header-scripts.js`

- [ ] Add the current-section toggle and connect it to the existing nav.
- [ ] Add mobile-only slide-down styling while preserving the desktop nav and no-JavaScript links.
- [ ] Add accessible toggle, outside-click, and Escape behavior to the shared header script.
- [ ] Inspect the rendered header at desktop and mobile sizes.
