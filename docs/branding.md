# FileWarden — Brand & Design Guidelines

**Project:** FileWarden for WordPress  
**Version:** 1.0  
**Status:** Initial brand guidelines  
**License:** GPL-2.0-or-later (project code)  
**Maintainer:** MikaPST

---

## 1. Brand Identity

### Product name

**FileWarden**

Product description: **Secure document management for WordPress.**

Tagline: **Your files. Your control.**

FileWarden is a free and open-source WordPress plugin designed to provide secure, modern and accessible document management without restricting essential security features behind a paid subscription.

Its philosophy is inspired by community-driven open-source projects such as Vaultwarden.

### Core values

- **Security by design:** security is a fundamental requirement, not a premium feature.
- **Freedom:** users retain control over their files and infrastructure.
- **Accessibility:** the plugin must remain approachable for both technical and non-technical users.
- **Transparency:** the source code and development process are open.
- **Simplicity:** powerful features should remain easy to understand and use.

### Brand personality

The FileWarden identity should feel:

- Trustworthy and protective.
- Modern and professional.
- Approachable rather than intimidating.
- Minimalist and recognizable.
- Consistent across WordPress, GitHub, documentation and the official website.

---

## 2. Official Color Palette

The official brand palette consists of five colors.

| Name | HEX | Primary usage |
| --- | --- | --- |
| Midnight Navy | `#0B162C` | Primary dark background, dark surfaces |
| Deep Navy | `#1C2942` | Secondary dark surfaces, navigation |
| Slate Blue | `#3B556D` | Supporting elements, secondary accents |
| Turquoise | `#5FC2BA` | Primary brand accent, highlights |
| White | `#FFFFFF` | Light surfaces, text on dark backgrounds |

### CSS brand tokens

```css
:root {
  --fw-brand-midnight: #0B162C;
  --fw-brand-navy: #1C2942;
  --fw-brand-slate: #3B556D;
  --fw-brand-turquoise: #5FC2BA;
  --fw-brand-white: #FFFFFF;
}
```

These are **brand tokens**, not a complete interface color system.

Functional colors for errors, warnings, success states, disabled controls, focus indicators, borders and backgrounds must be defined separately.

### Color usage guidelines

- Use Midnight Navy and Deep Navy to establish the product's visual identity.
- Use Turquoise selectively for important interactive elements and brand highlights.
- Prefer high-contrast combinations for readable content.
- Do not use Turquoise as small text on white backgrounds without verifying accessibility.
- Avoid introducing unrelated accent colors without design approval.
- Preserve the official brand colors when creating FileWarden marketing materials.

### Color accessibility

All interfaces should target WCAG 2.2 Level AA where applicable.

- Normal text should meet a minimum contrast ratio of 4.5:1.
- Large text should meet a minimum contrast ratio of 3:1.
- Essential graphical elements and UI component boundaries should meet a minimum contrast ratio of 3:1.
- Color must never be the only method of communicating information.

---

## 3. Official Logo

### Logo concept

The official FileWarden logo follows the **Guardian** concept.

It consists of:

1. A softly rounded protective shield.
2. A stylized folder representing document management.
3. A central padlock representing access control and confidentiality.
4. A navy and turquoise visual treatment with subtle gradients.

The logo must communicate both **document organization** and **security**.

### Logo usage rules

- Preserve the original proportions.
- Do not stretch, rotate or distort the logo.
- Do not change its official colors without approval.
- Do not add shadows, outlines or effects that alter the original identity.
- Maintain adequate clear space around the symbol.
- Ensure the logo remains recognizable at small sizes.
- Use the transparent version when placing the logo over colored backgrounds.

### Logo variants

The following variants are recommended:

| Asset | Purpose |
| --- | --- |
| `filewarden-logo.svg` | Vector logo for websites and scalable interfaces |
| `filewarden-logo-4096.png` | High-resolution master raster asset |
| `filewarden-logo-1024.png` | GitHub, presentations and general branding |
| `filewarden-logo-512.png` | WordPress plugin icon and application assets |

Only include assets that have actually been created and validated.

An SVG containing an embedded PNG must not be described as a fully vectorized logo.

### Asset directory

Official brand assets should be stored in:

`assets/branding/`

The original approved PNG should remain available as the visual reference for future exports.


### Favicon and Application Icons

FileWarden provides official favicon and application icon assets derived from the approved Guardian logo.

All assets must preserve the official colors, proportions and visual identity.

| Asset | Dimensions | Usage |
| --- | --- | --- |
| `favicon.ico` | 16, 32, 48 px | Browser compatibility |
| `favicon-16x16.png` | 16 × 16 | Browser tabs |
| `favicon-32x32.png` | 32 × 32 | Browser tabs |
| `apple-touch-icon.png` | 180 × 180 | Apple devices |
| `android-chrome-192x192.png` | 192 × 192 | Android and PWA |
| `android-chrome-512x512.png` | 512 × 512 | Android and PWA |

Official favicon assets are stored in `assets/favicon/`.

#### Usage requirements

- Always use approved favicon assets.
- Do not recreate or modify the official logo without explicit maintainer approval.
- Preserve the original colors, proportions and transparency where supported.
- Optimize image sizes without degrading visual quality.
- Maintain consistency across the official website, documentation and application interfaces.
- Do not generate replacement assets using AI when approved assets already exist.
- Any new favicon variants must be validated before inclusion.

---

## 4. Typography

The official brand typeface has not yet been selected.

Until a final decision is made:

- Prefer modern, readable sans-serif fonts.
- Prioritize accessibility and multilingual character support.
- Avoid decorative fonts in functional interfaces.
- Respect WordPress administration typography where appropriate.
- Use a consistent hierarchy of headings, labels and body text.

Typography choices for the marketing website may differ from those used inside the WordPress administration interface.

---

## 5. Interface Design Principles

FileWarden should offer a modern interface while remaining consistent with WordPress conventions.

### General principles

- Clean layouts with clear visual hierarchy.
- Consistent spacing, border radii and component dimensions.
- Responsive design for desktop, tablet and mobile.
- Clear navigation and predictable interactions.
- Accessible forms and keyboard navigation.
- Minimal visual clutter.
- Appropriate loading, empty, error and success states.

### Administration interface

The WordPress administration interface should:

- Integrate naturally into WordPress.
- Use the FileWarden brand identity without overriding global WordPress styles.
- Avoid unnecessary dependencies and heavy UI frameworks.
- Provide a modern document manager with clear navigation and actions.
- Support accessible light and dark themes where implemented.

### Frontend document displays

FileWarden will provide configurable document displays, including grids, lists and explorers.

**The official FileWarden branding must not be imposed on websites using the plugin.**

Website administrators must be able to customize frontend display colors, typography and visual settings according to their own site's identity.

The FileWarden brand palette should serve as the default design reference, not as an immutable frontend theme.

---

## 6. Design Tokens

UI implementations should use centralized design tokens instead of scattering hardcoded color values throughout the codebase.

### Example brand tokens

```css
:root {
  --fw-brand-midnight: #0B162C;
  --fw-brand-navy: #1C2942;
  --fw-brand-slate: #3B556D;
  --fw-brand-turquoise: #5FC2BA;
  --fw-brand-white: #FFFFFF;
}
```

### Semantic tokens

The interface should define semantic tokens for:

- Text and muted text.
- Page and component surfaces.
- Borders and separators.
- Primary and secondary actions.
- Hover, active, selected and disabled states.
- Success, warning and error feedback.
- Keyboard focus indicators.

Semantic tokens must support accessible light and dark variants.

### Naming conventions

- Prefix FileWarden CSS variables with `--fw-`.
- Prefer semantic names for component styling.
- Avoid coupling component behavior to a specific hex color.
- Keep brand tokens separate from user-configurable frontend display tokens.

---

## 7. Accessibility and Responsiveness

Accessibility is a core product requirement.

FileWarden interfaces should:

- Target WCAG 2.2 AA where applicable.
- Support keyboard-only navigation.
- Provide visible focus indicators.
- Use accessible labels for buttons and form controls.
- Respect reduced-motion preferences.
- Maintain readable layouts at different screen sizes.
- Avoid conveying information through color alone.
- Ensure adequate contrast in light and dark themes.

---

## 8. Brand Applications

The official FileWarden identity should remain consistent across:

- WordPress plugin administration screens.
- WordPress.org plugin listing and promotional assets.
- GitHub repository and documentation.
- Release announcements.
- Future official FileWarden website.
- Screenshots, presentations and tutorials.

Marketing visuals may use gradients and decorative elements, but functional interfaces should prioritize clarity, performance and accessibility.

---

## 9. Governance and Maintenance

This document is the primary source of truth for FileWarden branding and visual identity.

### Change management

Any change to the following requires maintainer approval:

- Official logo.
- Brand colors.
- Brand name or tagline.
- Core visual identity.
- Official typography, once selected.

Minor implementation adjustments may be made to improve accessibility, responsiveness or WordPress compatibility, provided they do not alter the approved brand identity.

### AI development instructions

AI coding agents, including Codex, must consult this document before implementing or modifying FileWarden visual components.

They must:

- Reuse established design tokens.
- Respect the official logo.
- Preserve accessibility requirements.
- Keep frontend displays customizable.
- Avoid introducing unapproved brand colors or design systems.
- Clearly distinguish implemented features from planned features.

---

**FileWarden — Your files. Your control.**
