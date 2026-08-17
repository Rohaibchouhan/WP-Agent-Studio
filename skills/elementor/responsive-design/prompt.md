# Skill: Elementor Responsive Design Optimization

## Objective
Ensure all page containers and typography render cleanly across mobile (<=768px), tablet (769px-1024px), and desktop (>1024px) viewports.

## Rules
1. Flex containers must wrap automatically on mobile viewports (`flex_direction: column` on mobile, `row` on desktop).
2. Padding & Margins must adjust for smaller screens (max 20px padding on mobile, 40-70px on desktop).
3. Font sizes for H1 headings should scale to 28-32px on mobile screens to prevent overflow.
4. Images must maintain `width: 100%` on mobile with proportional height scaling.
