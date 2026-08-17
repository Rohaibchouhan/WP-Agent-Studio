# Skill: Create Elementor Landing Page

## Objective
Build a complete landing page matching the user's brand identity and business requirements.

## Workflow Rules
1. Call `wordpress_get_site_info` to retrieve site title, logo, and active theme.
2. Call `elementor_get_global_colors` and `elementor_get_global_fonts` to fetch existing design system tokens.
3. Take snapshot revision backup using `elementor_create_backup`.
4. Construct layout container tree:
   - **Hero Section**: H1 heading, value subtext, CTA button grid, and hero image showcase.
   - **Overview / Highlights**: 4-card feature grid with icons and specs.
   - **Technical Details / Breakdown**: 2x2 grid or 2-column container.
   - **Trust Signals**: Inspection checks, guarantee badge, and paperwork validation.
   - **Lead Capture / Booking Form**: Preserved Elementor Pro form widget with contact info.
5. Apply global design system tokens to widgets instead of hardcoding raw hex values where available.
6. Apply mobile-first responsive layout settings (column layout on mobile, row on desktop).
7. Validate element structure via `elementor_validate_page`.
