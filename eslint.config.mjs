import js from "@eslint/js";
import globals from "globals";

export default [
  {
    ignores: ["**/vendor/**", "**/node_modules/**", "**/.git/**", "root/public/assets/lib/**"],
  },
  js.configs.recommended,
  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "module",
      globals: globals.browser,
    },
    rules: {
      "no-unused-vars": "warn",
      "no-console": "off",
    },
  },
];
