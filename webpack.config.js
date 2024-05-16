const path = require('path');

module.exports = {
    entry: path.resolve(__dirname, './js/main.js'),
    mode: 'production',
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'bundle.js',
    },
};

