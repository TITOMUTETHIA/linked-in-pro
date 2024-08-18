import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        
      ],

    theme: {
        extend: {
            colors: {
                "black": "#060606",
                'primary': '#3490dc',
                'secondary': '#ffed4a',
                'danger': '#e3342f',
            },
            fontFamily: {
                "hanken-grotesk": ["Hanken Grotesk", "sans-serif"],
            },
            fontSize: {
                "2xs": ".625rem", // 10px
            }
            
        },
    },

    plugins: [],
};
