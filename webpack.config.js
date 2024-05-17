const { type } = require('os');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    watch: true,
    plugins: [new MiniCssExtractPlugin()],
    entry: path.resolve(__dirname, './js/main.js'),
    mode: 'production',
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'bundle.js',
        clean: true,
    },

    // Config for pics loading
    module: {
        rules: [
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]',
                },
            },
    // Config for css loading
            {
                test : /\.css$/,
                use : [MiniCssExtractPlugin.loader, 'css-loader']
            }
        ],
    },
};

