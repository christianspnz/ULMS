/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./pages-learner/**/*.php",
    "./pages-manager/**/*.php",
    "./pages-admin/**/*.php",
    "./pages-superadmin/**/*.php",
    "./php/**/*.php",
    "./js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        pallete: { white  : '#FFFFFF', offwhite: '#F5F5F5', black: '#000000', gray: '#8B8B8B', lightgray: '#E0E0E0', darkgray: '#4F4F4F', red: '#C30008' },
      },
      fontFamily: {
        eurostile: ['Eurostile', 'sans-serif'],
        'eurostile-medium': ['Eurostile Medium', 'sans-serif'],
        'eurostile-bold': ['Eurostile Bold', 'sans-serif'],
        'eurostile-black': ['Eurostile Black', 'sans-serif'],
        'eurostile-heavy': ['Eurostile Heavy', 'sans-serif'],

        'eurostile-cond': ['Eurostile Cond', 'sans-serif'],
        'eurostile-cond-heavy': ['Eurostile Cond Heavy', 'sans-serif'],

        'eurostile-extd': ['Eurostile Extd', 'sans-serif'],
        'eurostile-extd-medium': ['Eurostile Extd Medium', 'sans-serif'],
        'eurostile-extd-black': ['Eurostile Extd Black', 'sans-serif'],
        'eurostile-extd': ['"Eurostile Extd"', 'sans-serif'],
      },
      keyframes: {
          marquee: {
              from: {
                  transform: "translateX(0)"
              },
              to: {
                  transform: "translateX(-50%)"
              }
          },
          "marquee-reverse": {
              from: {
                  transform: "translateX(-50%)"
              },
              to: {
                  transform: "translateX(0)"
              }
          }
      },
      animation: {
          marquee: "marquee var(--duration) linear infinite",
          "marquee-reverse":
              "marquee-reverse var(--duration) linear infinite"
      },
    },
  },
  plugins: [],
}

