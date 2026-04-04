const fs = require('fs');
const postcss = require('postcss');
const tailwindcss = require('@tailwindcss/postcss');

(async () => {
    try {
        const inputPath = './assets/css/src/input.css';
        const outputPath = './assets/css/tailwind.css';
        const input = fs.readFileSync(inputPath, 'utf8');
        const result = await postcss([tailwindcss]).process(input, {
            from: inputPath,
            to: outputPath,
            map: false
        });
        fs.writeFileSync(outputPath, result.css);
        console.log('Successfully compiled Tailwind CSS to', outputPath);
    } catch (err) {
        console.error('Error compiling Tailwind CSS:', err);
        process.exit(1);
    }
})();
