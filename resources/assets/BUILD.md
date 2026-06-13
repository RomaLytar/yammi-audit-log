# Vendored dashboard assets

These files are served straight from the package by `AssetController` (no CDN,
no `vendor:publish`), so the admin dashboard works offline and needs no CSP
exceptions for third-party hosts.

| File | Source | How to refresh |
|------|--------|----------------|
| `dashboard.css` | Tailwind CSS 3.4.16, compiled against the Blade views | see below |
| `lucide.js` | `lucide@0.468.0` UMD build (unpkg) | re-download the pinned version |
| `inter-latin.woff2`, `inter-latin-ext.woff2` | `@fontsource-variable/inter@5.1.0` (jsDelivr) | re-download the pinned files |

## Rebuilding dashboard.css

After changing Tailwind classes in the Blade views, regenerate the stylesheet
with the pinned compiler and the committed config:

```
npx tailwindcss@3.4.16 \
  -c resources/assets/tailwind.config.js \
  -i <(printf '@tailwind base;\n@tailwind components;\n@tailwind utilities;\n') \
  -o resources/assets/dashboard.css --minify
```

The CSS-variable theme tokens (`:root` / `.dark`) and component classes
(`.al-input`, the `@font-face` rules) live in `resources/views/layouts/app.blade.php`,
not in the compiled file.
