/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/**/*.{vue,js,ts}',
  ],
  theme: {
    extend: {
      colors: {
        // Rosa - cor de marca (mesma linha do cliente), usado em CTAs, links, logo.
        brand: {
          50: '#FDF3F6',
          100: '#FBE4EC',
          200: '#F6C7D9',
          300: '#EFA0BE',
          400: '#E672A0',
          500: '#D94E85',
          600: '#C13570',
          700: '#A0275C',
          800: '#7D1F49',
          900: '#5C1837',
        },
        // Verde-sálvia - cor de contraste (selos, badges, destaques secundarios).
        accent: {
          50: '#F3F6F1',
          100: '#E4EBDF',
          200: '#C9D6C0',
          300: '#A7BC99',
          400: '#839D72',
          500: '#647D54',
          600: '#4F6543',
          700: '#3E4F35',
          800: '#2F3C29',
          900: '#232D1F',
        },
      },
      fontFamily: {
        display: ['"Playfair Display"', 'serif'],
        sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
