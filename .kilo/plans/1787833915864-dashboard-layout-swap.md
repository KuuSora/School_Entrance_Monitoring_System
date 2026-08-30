# Dashboard Live Preview Layout Re-arrange

## Goal
Re-arrange the Live Dashboard (`dashboardTab`) `preview-layout` to:
1. **Live Stats** on the **left** (returned to its original position).
2. **ID card** in the **middle**, shrunk to a **compact ~260px** so the three columns fit neatly side-by-side ("kaigo").
3. **Scan Log panel** on the **right** as the **fixed (sticky)** panel.

## Current state (from prior edits)
- HTML order in `index.php` `preview-layout`: `scanLogPanel` (aside) → `id-card-container` → `stats-column`.
- CSS `dashboard/style.css`:
  - `grid-template-columns: 380px 1fr 320px` (style.css:410)
  - `.preview-layout > .stats-column { position: sticky; top:20px; align-self:start; }` (style.css:442) — stats is currently the fixed panel.
  - `.preview-layout > .panel-fixed { position: static; ... }` (style.css:449) — panel is currently static on the left.
  - `.id-card-container .id-card { width: 380px; }` (style.css:432/433).

## Changes

### 1. `dashboard/index.php` — reorder `preview-layout` children
Move the `stats-column` block to be the **first** child, keep `id-card-container` **second**, and move `scanLogPanel` (`<aside id="scanLogPanel">`) to be the **third/last** child. No markup changes inside the blocks, only reordering.

### 2. `dashboard/style.css` — grid columns
Change (style.css:410):
```css
.preview-layout {
  display: grid;
  grid-template-columns: 300px 1fr 360px;
  gap: var(--space-md);
  align-items: flex-start;
}
```
(left = stats 300px, middle = id card area 1fr, right = fixed panel 360px)

### 3. `dashboard/style.css` — make Live Stats a normal left column
Replace the sticky rule (style.css:442) so stats is **not** sticky:
```css
/* Live stats: normal left column */
.preview-layout > .stats-column {
  min-width: 0;
}
```
(keep `.id-card-container` in the existing `.preview-layout > .stats-column, .preview-layout > .id-card-container { min-width:0 }` selector — leave that selector as-is.)

### 4. `dashboard/style.css` — make Scan Log panel the fixed (sticky) right panel
Replace the static rule (style.css:449) with a sticky right-panel version:
```css
/* Scan log panel docked as the fixed panel on the right */
.preview-layout > .panel-fixed {
  position: sticky;
  top: 20px;
  left: auto !important;
  bottom: auto;
  width: auto !important;
  height: auto;
  max-height: calc(100vh - var(--topbar-height) - 40px);
  border: none;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.06);
  z-index: auto;
  transition: none;
  overflow: hidden;
}
body.dark-mode .preview-layout > .panel-fixed { border: none; }
.preview-layout > .panel-fixed.panel-minimized { display: none; }
```

### 5. `dashboard/style.css` — shrink the ID card to ~260px (compact)
Change (style.css:432-433):
```css
.id-card-container .id-card {
  width: 260px;
  transition: box-shadow 0.3s ease;
}
```
(Optional, for proportion) also reduce photo + padding so it reads as compact:
```css
.id-card-container .id-card-photo { width: 72px; height: 72px; margin-bottom: 14px; }
.id-card-container .id-card-body { padding: 14px; }
```
If these sub-selectors don't exist yet, add them under the `.id-card-container` scope; otherwise adjust the existing ones.

### 6. Responsive (no functional change needed)
The existing `@media (max-width: 1200px)` already collapses `preview-layout` to a single column (`1fr`) and makes `.panel-fixed` static — this still works after the reorder (order becomes stats → id → panel on small screens). No edit required unless you want a different stack order.

## Affected files
- `dashboard/index.php` (reorder 3 blocks inside `#dashboardTab .preview-layout`)
- `dashboard/style.css` (grid columns, stats sticky removal, panel sticky, id-card width/size)

## Validation
- Open `dashboard/index.php` in a browser, log in, view **Live Preview** tab.
- Confirm left = Live Stats, middle = small (~260px) ID card, right = Scan Log panel that stays visible (sticky) while scrolling.
- Resize to <1200px wide: columns stack and the scan log remains usable.
- Verify dark mode still styles the panel and id card correctly.
- Confirm minimize button on the scan log panel still hides/shows it (floating button re-opens).

## Open questions / risks
- Exact ID card sub-element sizes (photo/padding) are suggestions; adjust to taste.
- If the 360px right panel feels too narrow for the 6-column log table, widen it (e.g., 380–420px) and reduce the stats column accordingly.
- "kaigo" interpreted as compact fit on one row; if you meant something else, adjust step 5.
