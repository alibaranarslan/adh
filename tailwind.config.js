import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./vendor/filament/**/*.blade.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        adh: {
          navy: "#1A1A2E",
          blue: "#16213E",
          red: "#C62828",
          "red-light": "#EF9A9A",
          bg: "#FAFAFA",
          text: "#212121",
          gray: "#757575",
          "gray-light": "#F5F5F5",
          border: "#E0E0E0",
        },
      },
      fontFamily: {
        serif: ["Lora", "Georgia", "serif"],
        sans: ["Inter", "system-ui", "sans-serif"],
      },
    },
  },
  plugins: [typography],
};
