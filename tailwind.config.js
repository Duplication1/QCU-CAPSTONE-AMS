/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./view/**/*.php",
    "./view/**/*.html",
    "./assets/**/*.js",
  ],
  safelist: [
    // Notification chip classes
    'bg-green-50', 'text-green-800', 'border-green-400',
    'bg-red-50', 'text-red-800', 'border-red-400',
    'bg-yellow-50', 'text-yellow-800', 'border-yellow-400',
    'bg-blue-50', 'text-blue-800', 'border-blue-400',
    'bg-gray-50', 'text-gray-800', 'border-gray-400',
    // Modal classes
    'bg-green-100', 'text-green-600',
    'bg-red-100', 'text-red-600',
    'bg-yellow-100', 'text-yellow-600',
    'bg-blue-100', 'text-blue-600',
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
