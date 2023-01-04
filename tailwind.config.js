/** @type {import('tailwindcss').Config} */

const novaTailwindConfig = require("./vendor/laravel/nova/tailwind.config.js");

module.exports = {
    ...novaTailwindConfig,
    content: ["./resources/**/*.{js,vue}"],
    important: ".image-gallery-field",
};
