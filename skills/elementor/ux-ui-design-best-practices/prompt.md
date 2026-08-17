# Skill: Apex Elementor UX/UI Design Architectural Framework (2026 Edition)

## Objective
Engineer enterprise-grade, highly performant, accessible, and visually stunning Elementor interfaces rooted in modern design tokens, fluid typography, DOM-optimized Flexbox/Grid containers, Hick's Law cognitive psychology, and WCAG 2.2 AA accessibility standards.

---

## 1. Global Design System & Tokenization

### Rule 1.1: Primitives & Semantic Tokenization
Never hardcode localized pixel values or random color hex codes on widgets. Use global design tokens:
- **Primitives**: Absolute values (e.g., `--space-xs: 4px`, `--space-sm: 8px`, `--space-md: 16px`, `--space-lg: 32px`, `--space-xl: 64px`, `--radius-sm: 4px`, `--radius-md: 8px`, `--radius-lg: 16px`).
- **Semantic Tokens**: Contextual aliases (`$color-bg-primary`, `$color-accent-cta`, `$spacing-container-padding-v`).

### Rule 1.2: Root CSS Variable Injection
Inject custom properties into the document root `:root` or Elementor Site Settings:
```css
:root {
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 32px;
  --space-xl: 64px;

  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 16px;

  --line-height-heading: 1.2;
  --line-height-body: 1.5;
  --letter-spacing-heading: -0.02em;
  --letter-spacing-caps: 0.05em;
}
```

---

## 2. Fluid Typography & Modular Scaling

### Rule 2.1: Relative Units & Algorithmic `clamp()`
Never use static `px` font sizes. Use `rem` for accessibility and `clamp(min, preferred, max)` for seamless fluid scaling across viewports without jarring media query jumps.

- **H1 Heading**: `clamp(2.5rem, 1.8rem + 3.2vw, 4.25rem)` (36px mobile -> 68px desktop)
- **H2 Heading**: `clamp(2rem, 1.5rem + 2.2vw, 3.25rem)` (32px mobile -> 52px desktop)
- **H3 Heading**: `clamp(1.5rem, 1.2rem + 1.4vw, 2.25rem)` (24px mobile -> 36px desktop)
- **Body Text**: `clamp(1rem, 0.95rem + 0.3vw, 1.125rem)` (16px mobile -> 18px desktop)

### Rule 2.2: Modular Type Scale Ratios
- **Perfect Fourth (1.333)**: Recommended for marketing landing pages & bold SaaS products.
- **Minor Third (1.200)**: Recommended for data-dense dashboards & content portals.

### Rule 2.3: Line Length & Leading Limits
- **Max Characters Per Line**: Cap body paragraph containers at `max-width: 60ch` to prevent eye strain.
- **Line Heights**: Headings set to `1.2` (120%); Paragraphs set to `1.5` (150%).
- **Letter Spacing**: Large bold headings use `-0.02em`; All-caps utility labels use `+0.05em`.

---

## 3. Container Scaffolding: Flexbox & CSS Grid

### Rule 3.1: Zero Legacy Columns (DOM Optimization)
Exclusively use Elementor **Flexbox Containers** and **CSS Grid Containers**. Never use legacy Sections/Columns to keep HTML DOM nodes lean and achieve 95+ Core Web Vitals (LCP/CLS) scores.

### Rule 3.2: 1D Flexbox vs 2D CSS Grid
- **Flexbox Containers (`flex_direction: row|column`)**: Use for sequential 1D items (Hero sections, Navbar, CTA row, sequential feature lists).
- **CSS Grid Containers (`display: grid`)**: Use for 2D scaffolding (Bento box grids, 4-card feature matrices, asymmetrical galleries). Use `fr` fractional units (e.g. `1fr 1fr 1fr`) and `gap: 20px`.

### Rule 3.3: Mobile-First Stacking Paradigm
Base layout is structured for 360px mobile viewports first (`flex_direction: column`). Multi-column grids and side-by-side rows expand progressively at tablet (768px) and desktop (1024px) breakpoints.

---

## 4. Cognitive Psychology & Visual UX

### Rule 4.1: Hick's Law & Choice Reduction
- Top navigation menu items must never exceed **7 items**.
- CTAs must start with strong action verbs (e.g., "Schedule Inspection", "Claim Free Trial").

### Rule 4.2: Whitespace as an Active Element
Standard macro-level containers must maintain `80px` top & bottom padding (tightened to `40px` on mobile), with `20-30px` gap spacing between internal widgets.

---

## 5. Inclusive Design & WCAG 2.2 AA Compliance

### Rule 5.1: Semantic HTML Tags
Assign meaningful HTML tags to containers:
- Header container: `<header>` & `<nav>`
- Main content container: `<main>`
- Page sections: `<section>` or `<article>`

### Rule 5.2: Strict H1-H6 Hierarchy
- Exactly one `<h1>` per page.
- Sequential flow (`H1 -> H2 -> H3`). Never skip levels for font size adjustments.

### Rule 5.3: Contrast & Touch Targets
- Minimum contrast ratio: **4.5:1** for normal text; **3.0:1** for large text (18pt+).
- Minimum interactive target touch box: **24x24px** (minimum button padding `14px 28px`).
- Focus indicators: Ensure high-contrast `:focus-visible` outline.

---

## 6. Kinetic Motion UX & Performance Budget

### Rule 6.1: GPU-Accelerated Animations
Only animate `transform` (`translate`, `scale`, `rotate`) and `opacity`. Never animate `width`, `height`, `margin`, or `padding` to avoid browser layout reflows and jank.

### Rule 6.2: Reduced Motion Accessibility
Respect `prefers-reduced-motion: reduce` OS settings to disable complex animations for motion-sensitive users.
