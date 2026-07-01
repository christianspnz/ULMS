/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./pages/**/*.php",
    "./pages/**/*.js",
    "./pages/**/*.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        pallete: { white  : '#FFFFFF', offwhite: '#F5F5F5', black: '#000000', gray: '#8B8B8B', lightgray: '#E0E0E0', darkgray: '#4F4F4F', red: '#C30008' },
      },
      fontFamily: {
        'lexend': ['Lexend', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

