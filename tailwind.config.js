/** @type {import('tailwindcss').Config} */

const novaTailwindConfig = require("../../laravel/nova/tailwind.config.js");

module.exports = {
    ...novaTailwindConfig,
    content: ["./resources/**/*.{js,vue}"],
    important: ".image-gallery-field",
};
