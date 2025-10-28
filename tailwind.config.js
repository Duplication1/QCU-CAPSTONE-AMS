/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./view/**/*.php",
    "./view/**/*.html",
    "./assets/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#667eea',
        secondary: '#764ba2',
      },
    },
  },
  plugins: [],
}
