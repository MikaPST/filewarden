# FileWarden — Brand & Design Guidelines

## Corrections de préproduction v19 — 28 novembre 2026

Le tableau Documents reste une liste : chevron unique fin à droite du libellé (gris au repos, navy et orienté selon le tri actif), sans raccourci Type ; espace réservé aux quatre actions et marge entre accès effectif et actions. Les liens Annuler des modales sont centrés sous la validation, avec marge basse et un seul conteneur de défilement. Les pictogrammes fonctionnels de sous-dossiers héritent de la couleur des liens de la palette publique, sans recoloration des icônes de fichiers. Les trois cartes d’accès refusé s’alignent sur le séparateur et la marque avec marges identiques. La pastille sombre représente background/surface/accent (#101a2b / #192840 / #76d6ce) ; Personnalisé continue de refléter les couleurs saisies.

## Lot autorisé 0.1.19-dev

Voir [le périmètre des 16 points](lot-v0.1.19-dev.md) et [les notes du candidat](releases/v0.1.19-dev.md). Les contrôles d’accès et capacités restent inchangés. La migration additive de schéma 8 ajoute une date d’arrivée nullable ; aucune date historique inconnue n’est reconstruite. Le manifeste 3 inclut huit composants, avec import des anciens manifestes 1/2. Les paquets de configuration seule peuvent être appliqués sur une bibliothèque existante sans modifier documents, dossiers ni politiques ; une restauration de bibliothèque exige toujours une destination vide. Le chiffrement, les validations serveur, la confirmation, le verrou d’écriture et la reprise transactionnelle restent requis. Les mots de passe et clés du site source ne sont jamais exportés.

Les maquettes validées définissent les raffinements d’interface v19 et les palettes publiques FileWarden sombre/Terracotta ; leurs dix couleurs sont consignées dans le périmètre. Ces palettes publiques ne changent ni les assets officiels, ni les couleurs internes de marque. Le nom d’auteur est Michaël PASTOR.


## Nouvelle passe UI et sauvegarde sélective — 0.1.18-dev

Lot validé par le propriétaire le 23 novembre 2026 : recherche/filtres homogènes Documents/Corbeille, cartes mobiles de corbeille, états publics vides centrés, destination lisible, suppressions multiples confirmées et modales d’action. Le Diagnostic différencie le module Sodium absent et ouvre l’assistant volontaire de conversion des trois tables WordPress nécessaires à la restauration, sans conversion au clic du lien. Sauvegarde complète ou transfert simplifié, options authentifiées indépendantes et restauration privée à l’administration sans reprise des droits ; attribution globale des créateurs par défaut dans l’interface. Voir [le guide sauvegarde](backup.md) et [le périmètre de correction](lot-ui-v0.1.18-dev.md). Résultats de cette passe dans `docs/validation/v0.1.18-dev-ui-pass-local.json` ; les preuves des anciens ZIP restent historiques.


**Project:** FileWarden for WordPress  
**Version:** 1.1  
**Status:** Approved brand guidelines  
**License:** GPL-2.0-or-later (project code)  
**Maintainer:** Michaël PASTOR

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
|---|---|---|
| Midnight Navy | `#0B162C` | Primary dark background, dark surfaces |
| Deep Navy | `#1C2942` | Secondary dark surfaces, navigation and primary actions |
| Slate Blue | `#3B556D` | Supporting elements, secondary accents and permission management |
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

### Semantic UI Colors

In addition to the five official brand colors, FileWarden uses semantic interface colors to distinguish actions and communicate their importance.

**Semantic colors are part of the interface design system and must not be considered additional official brand colors.**

| Name | HEX | Primary usage |
|---|---|---|
| Danger Red | `#B42318` | Destructive actions, deletion confirmations and critical errors |
| Classification Amber | `#805400` | Recovery/classification and retained-access information icons, counters and supporting text |
| Amber Surface | `#FFF6E8` | Subtle background paired with Classification Amber for the recovery collection and information disclosure |
| Deep Navy | `#1C2942` | Primary actions, document and folder creation, publication |
| Slate Blue | `#3B556D` | Permission management and sensitive non-destructive actions |
| Turquoise | `#5FC2BA` | Positive accents, highlights and progress indicators |

The use of semantic colors must remain consistent throughout the FileWarden administration interface.

### Semantic Action Tokens

```css
:root {
  --fw-action-primary: var(--fw-brand-navy);
  --fw-action-permissions: var(--fw-brand-slate);
  --fw-action-danger: #B42318;
  --fw-action-accent: var(--fw-brand-turquoise);
  --fw-recovery-tone: #805400;
  --fw-recovery-surface: #FFF6E8;
}
```

### Action Button Guidelines

The following conventions define the approved appearance of common FileWarden administration actions.

| Action | Color | Icon |
|---|---|---|
| Create folder | Deep Navy `#1C2942` | `folder-plus` |
| Add documents | Deep Navy `#1C2942` | `file-plus-2` |
| Publish folder | Deep Navy `#1C2942` | None |
| Edit permissions | Slate Blue `#3B556D` | `shield-check` |
| Delete folder | Danger Red `#B42318` | `trash-2` |
| Confirm deletion | Danger Red `#B42318` | `trash-2` |

#### Button implementation requirements

- Use consistent colors for identical actions throughout the administration interface.
- Primary actions must use Deep Navy unless a specific semantic state requires another color.
- Permission-management actions must use Slate Blue.
- Destructive actions must use Danger Red.
- The "Publish folder" button must not include an icon.
- Buttons must provide accessible hover, focus, active, disabled and loading states.
- Use clear labels and appropriate pictograms; color alone must never communicate an action's meaning.
- Preserve existing authorization checks and confirmation requirements.
- Do not introduce arbitrary button colors outside the approved design system.
- Use centralized CSS variables rather than hardcoded colors in individual components.

### Color usage guidelines

- Use Midnight Navy and Deep Navy to establish the product's visual identity.
- Use Turquoise selectively for important interactive elements and brand highlights.
- Prefer high-contrast combinations for readable content.
- Do not use Turquoise as small text on white backgrounds without verifying accessibility.
- Avoid introducing unrelated accent colors without design approval.
- Preserve the official brand colors when creating FileWarden marketing materials.
- Reserve Danger Red for destructive actions and critical error states.

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
|---|---|
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
|---|---|---|
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

### Official Brand Typography

The official FileWarden brand typeface is **Manrope**.

Manrope is an open-source sans-serif typeface distributed under the SIL Open Font License (OFL).

#### Typography Weights

- **Bold (700):** Official FileWarden wordmark and main headings.
- **SemiBold (600):** Section headings, navigation and prominent UI labels.
- **Regular (400):** Body text, descriptions and documentation.

#### Usage Guidelines

- Use Manrope Bold (700) for the FileWarden wordmark.
- Preserve consistent letter spacing and proportions.
- Do not modify existing approved logo assets.
- Respect native WordPress administration typography where appropriate.
- Prioritize readability and accessibility.

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
- Apply the approved semantic action colors consistently across all administration screens.

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
- Permission-management actions.
- Destructive actions and deletion confirmations.
- Hover, active, selected and disabled states.
- Success, warning and error feedback.
- Keyboard focus indicators.

Semantic tokens must support accessible light and dark variants.

The approved action tokens are defined in **Section 2 — Semantic Action Tokens**.

### Notification tokens (approved UI/UX batch 0.1.14-dev)

Four functional notification types use separate tokens scoped to the FileWarden notification area: success `--fw-notice-success` (`#167348`), information `--fw-notice-info` (`#235CB4`), warning `--fw-notice-warning` (`#925400`), and error `--fw-notice-error` (`#B42318`). These semantic colors implement the owner's approved green/blue/orange/red notification request; they do not change the official brand palette. Pair each with a distinct icon, a readable title and description. Use the same tone for its regressive progress bar, with a subtle unbordered track. Persistent notifications have no visible time bar.

The approved corrective batch uses a nonmodal fixed notification layer at the upper right, below the WordPress admin bar, without resizing the workspace. Keep owned classes independent of generic WordPress notices and preserve visibility in the native preview dialog's top layer. Showing a notification must not steal keyboard focus.

The Documents welcome uses an 80-pixel filled folder illustration in Navy/Slate with a turquoise tab; it is an interface icon, not a replacement logo. The guided tree keeps its existing hierarchy with slightly denser rows. Settings use the approved compact vertical rail: icon and label, Navy/white selection, and the category description immediately below the heading in the right-hand panel.

For 0.1.15-dev, the owner supplied replacement artwork for this welcome folder. Only `assets/illustrations/folder-welcome.png` is retained and connected to the interface/runtime archive. The supplied SVG copy was removed on request because it embedded the identical PNG rather than vector paths. Preserve the PNG's original colors, proportions, alpha channel and 80-pixel display box. This illustration does not replace the official logo, which is separately displayed beside the top-left title block using the unchanged official asset.

### Naming conventions

- Prefix FileWarden CSS variables with `--fw-`.
- Prefer semantic names for component styling.
- Avoid coupling component behavior to a specific hex color.
- Keep brand tokens separate from user-configurable frontend display tokens.
- Do not duplicate semantic color definitions across individual components.

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

Semantic interface colors may be extended only when required by a documented functional need and approved by the maintainer.

### AI development instructions

AI coding agents, including Codex, must consult this document before implementing or modifying FileWarden visual components.

They must:

- Reuse established design tokens.
- Respect the official logo.
- Preserve accessibility requirements.
- Keep frontend displays customizable.
- Apply approved semantic colors and action button conventions.
- Avoid introducing unapproved brand colors or design systems.
- Clearly distinguish implemented features from planned features.

---

**FileWarden — Your files. Your control.**

### Approved administration refinements — 0.1.15-dev

Use the original official 512px logo in the shared administration header (56px desktop, 44px mobile), preserving colors, transparency and proportions. The welcome illustration uses only the owner-provided PNG in assets/illustrations at 80px; it is not a logo. The WordPress menu uses the approved **outline folder-lock** functional icon with native WordPress menu-state colors, including folded mode. Do not substitute the discarded filled-folder experiments.

Sources use local Lucide laptop-minimal/library-big icons at 20px with 10px spacing. Task navigation uses compact underlined tabs and no visible “Navigation FileWarden” heading. The health summary keeps existing storage-state colors, keyboard-operable details and essential warnings visible when collapsed.

Only simplified audit records use scoped semantic tones: information #8CB9FF, configuration/access #5FC2BA, confirmed additions/restorations #80D5A3, trash/destructive requests #FFC078, permanent deletion and failure/refusal #FFAAA3, unknown #B0C2DC, on Midnight Navy. Pair tones with explicit category labels and distinct icons; a deletion is not an error and a request is not completion. These are functional UI colors, not new official brand colors. Purge controls use the existing Danger Red and white trash-2 icon. New Lucide source assets are pinned to upstream 0.468.0 and retain the bundled Lucide license.


### Approved administration refinements — 0.1.16-dev

This owner-approved revision supersedes the 0.1.15 header sizing: unchanged official logo in a 14px square beside the 14px FILEWARDEN wordmark, centered on the same line; keep the existing page-title size on a separate line below. No official asset is redrawn.

Search, Media Library selection and Open audit log use Turquoise (#5FC2BA) with Navy (#1C2942) text/icons and a Slate border; exports use a quiet tinted surface with Navy and the common download icon. Permission and destructive roles retain Slate and Danger Red. Preview/download controls use refined local outline icons, quiet backgrounds, turquoise hover, tooltips and coarse-pointer targets. Active task underline remains Navy. Selected settings categories use Navy background/border and white text/icons. Remove profile-derived mouse rings only inside FileWarden; preserve a visible Navy keyboard-focus outline.

Recovery uses a dark amber (#805400)/white solid numeric badge, absent at zero. The integrated formats notice starts collapsed at every viewport; ZIP/macros guidance remains outside its disclosure. Hide it in explicitly enabled all-format mode. The opt-in warning uses the approved orange warning tone, with an accessible confirmation dialog and a persistent reminder after save. Locale selection uses the local languages icon. Preserve frontend site customization.

### Approved refinements — 0.1.17-dev

Owner-approved Nature palette update (22 November 2026): interface background #EDF3EE. Other Nature colors and the official brand palette are unchanged. The preset applies through the existing explicit palette/save controls; keep saved custom site appearances intact.

The final owner-approved adjustments (22 November 2026) place Publication's Navy-underlined tabs outside the settings cards, beneath the heading and spanning the configuration/output columns. A compact Shortcode card sits above the shared preview, with WordPress Shortcode block / Elementor Shortcode widget guidance and a copy icon/confirmation. Contextual integration controls follow the preview mode without resetting hidden values: list/grid for authorized, unavailable layout and visitor sign-in for denied; the folder and shared identity/colors remain available. Color palette replaces Starting template. Retained-access information disclosures use Classification Amber on Amber Surface, shared with recovery through the existing semantic tokens; keep their keyboard/touch behavior and Navy focus. Verify contrast; these are functional colors supporting the five official brand colors.

This revision supersedes the preceding tiny header: restore the original 56px official logo (44px mobile), beside the 20px FILEWARDEN wordmark (18px mobile), vertically centered together. Keep the 27px page title (23px mobile) below this identity row. Search, Media Library selection and Open audit log now reuse the exports' quiet tinted background/Navy text and their subtle hovered background; do not use a solid turquoise fill for these controls.

The guided folder rail is 290px on desktop, 270px with an inspector and 245px at intermediate widths, then stacked on mobile. Fine-pointer folder rows use a compact 34px minimum; coarse pointers retain 44px targets. Preserve wrapped names and hierarchy guides. Documents/Trash remain navigation; contextual creation actions appear below the existing search. Retained access notes are per-file accessible information disclosures.

Public appearance is independent of administration branding: a single persistent configuration for all publication frames and viewers, default FileWarden, with approved Neutral and Nature presets and a Custom card reflecting all ten current colors. Allow strict color tokens, a plain public name and validated local PNG/JPEG logo; never recolor the official logo or the actual file-type assets. Color changes affect functional controls only. The approved corrective lot supersedes strict vertical centering: natural-height grid cards use 16px padding, horizontally centered icon/name/metadata and actions at the bottom of each row, without an upper spacer. Contrast validation of implemented text pairs is not a full accessibility certification. Publication uses two Navy-underlined local tabs, compact segmented preview controls, bounded subfolder links and a lightly surfaced breadcrumb with a non-link current folder.


### Approved refinements — 0.1.18-dev

Use the owner's exact dedicated type icons and the AUDIO/video updates, distinguish PPT/PPTX and JPG/JPEG, remove the generic Presentation/Archive assets and retain FILE for unknown/no extension. New artwork does not change accepted formats. Rename uses a small outline pencil, with an accessible name; selections use the existing secondary controls and confirmed-review dialog.

The text-readability panel presents the eight actual server/client text pairs, explicit AA/AAA/incomplete/improvement states and an eight-segment indicator. Reuse notice success #167348 and Classification Amber #805400 as functional tones, never as replacement brand colors. This score does not certify whole-site accessibility. Backup settings reuse the vertical category rail, Navy-underlined local tabs, notices and native keyboard-operable controls. Keep secret inputs and private job details out of notifications, URLs and public pages.

### Approved document sheet — 0.1.19-dev

Reuse the existing centered native action dialog, original type assets, Navy primary controls and outline Preview. Use natural content height with one dialog scroll region, compact adjacent Preview/Download, divided Description/Information/Effective access sections, and a keyboard-operable access disclosure. Retained-rule and duplicate warnings reuse Classification Amber and its existing surface. Upload descriptions belong to each queue item; warnings remain visible for both computer and Media sources. Do not add persistent duplicate badges or public description rendering in this lot. Preserve the semantic list table and mobile adaptations.

### Approved preproduction refinements — same-name/access/modal lot

Understand access uses illustrated steps from the actual chain: icon, source title, right-aligned rule and a short explanatory line. Restrictive predicates use Slate/soft surface; retained rules use existing Classification Amber. Keep all ancestors, native disclosure semantics and mobile wrapping. Shared fw-modal-close applies the same quiet borderless Navy cross, 8px radius, turquoise-tinted hover and visible keyboard focus in Rename/Information/action dialogs, preserving 34px fine and 44px coarse targets. Copy/Move destination uses a 14px semi-bold label, 10px label/select gap and 18px before actions. Same-name copy suggestions must be visible before confirmation; no silent overwrite or persistent duplicate badge. Existing official type assets are unchanged.
