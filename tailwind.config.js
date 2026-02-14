/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./includes/**/*.php",
    "./modules/**/*.php",
    "!./node_modules/**",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}