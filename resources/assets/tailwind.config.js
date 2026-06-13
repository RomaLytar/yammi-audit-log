module.exports = {
  darkMode: 'class',
  content: {
    relative: true,
    files: ['../views/**/*.blade.php'],
  },
  safelist: [
    // Toggled from JS, so the scanner cannot see them on an element.
    'rotate-180', 'hidden', 'dark',
    'animate-fade-in', 'animate-slide-down', 'animate-pulse-soft',
    // Anomaly badge tones are interpolated in anomalies.blade.php
    // (bg-{tone}/10 text-{tone} ring-{tone}/30), so the scanner cannot resolve
    // them. Keep every tone the AnomaliesViewModel can emit.
    ...['warning', 'destructive', 'info', 'muted-foreground'].flatMap((tone) => [
      `bg-${tone}/10`, `text-${tone}`, `ring-${tone}/30`,
    ]),
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter Variable', 'Inter var', 'Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
      },
      colors: {
        border: 'hsl(var(--border))', input: 'hsl(var(--input))', ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))', foreground: 'hsl(var(--foreground))',
        primary: { DEFAULT: 'hsl(var(--primary))', foreground: 'hsl(var(--primary-foreground))' },
        secondary: { DEFAULT: 'hsl(var(--secondary))', foreground: 'hsl(var(--secondary-foreground))' },
        destructive: { DEFAULT: 'hsl(var(--destructive))', foreground: 'hsl(var(--destructive-foreground))' },
        success: { DEFAULT: 'hsl(var(--success))', foreground: 'hsl(var(--success-foreground))' },
        warning: { DEFAULT: 'hsl(var(--warning))', foreground: 'hsl(var(--warning-foreground))' },
        info: { DEFAULT: 'hsl(var(--info))', foreground: 'hsl(var(--info-foreground))' },
        muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
        accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
        popover: { DEFAULT: 'hsl(var(--popover))', foreground: 'hsl(var(--popover-foreground))' },
        card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
        brand: { DEFAULT: 'hsl(var(--brand))', foreground: 'hsl(var(--brand-foreground))' },
      },
      borderRadius: { xl: 'calc(var(--radius) + 4px)', lg: 'var(--radius)', md: 'calc(var(--radius) - 2px)', sm: 'calc(var(--radius) - 4px)' },
      boxShadow: { xs: '0 1px 2px 0 rgb(0 0 0 / 0.04)', glow: '0 0 0 1px hsl(var(--ring) / 0.15), 0 8px 24px -8px hsl(var(--ring) / 0.25)' },
      keyframes: {
        'fade-in': { from: { opacity: '0', transform: 'translateY(-2px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        'slide-down': { from: { opacity: '0', transform: 'translateY(-6px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        'pulse-soft': { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.55' } },
      },
      animation: { 'fade-in': 'fade-in .2s ease-out', 'slide-down': 'slide-down .22s ease-out', 'pulse-soft': 'pulse-soft 1.8s ease-in-out infinite' },
    },
  },
};
