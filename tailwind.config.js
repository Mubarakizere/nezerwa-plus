/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class', // ✅ manual toggle mode (no longer follows system)
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
      colors: {
        primary: {
          DEFAULT: '#2563eb', // blue-600
          dark: '#1d4ed8',    // blue-700
          light: '#3b82f6',   // blue-500
        },
        success: '#16a34a',
        danger: '#dc2626',
        warning: '#ca8a04',
        neutral: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          600: '#475569',
          900: '#0f172a',
        },
      },
      boxShadow: {
        soft: '0 2px 8px rgba(0,0,0,0.04)',
        medium: '0 4px 12px rgba(0,0,0,0.06)',
        strong: '0 6px 20px rgba(0,0,0,0.1)',
      },
      borderRadius: {
        lg: '0.5rem',
        xl: '0.75rem',
        '2xl': '1rem',
      },
    },
  },
  plugins: [],
};
