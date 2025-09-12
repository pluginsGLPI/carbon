/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const { globSync } = require('glob');
const libOutputPath = 'public/lib';
const picsOutputPath = 'public/images';

const config = {
    plugins: [new MiniCssExtractPlugin()],
    entry: function () {
            // Create an entry per *.js file in lib/bundle directory.
            // Entry name will be name of the file (without ext).
            let entries = {};

            const files = globSync(path.resolve(__dirname, 'lib/bundles') + '/!(*.min).js');
            for (const file of files) {
                entries[path.basename(file, '.js')] = file;
            }

            return entries;
        },
    mode: 'production',
    output: {
        path: path.resolve(__dirname, libOutputPath),
        // filename: 'bundle.js',
        clean: true,
        publicPath: '',
    },

    module: {
        rules: [
            {
                // Config for pics loading
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]',
                },
            },
            {
                // Config for css loading
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader']
            }
        ],
    },
};

const copyPatterns = [];
copyPatterns.push({
    from:   path.resolve(__dirname, 'pics'),
    to:     path.resolve(__dirname, picsOutputPath),
    filter: (resourcePath) => {
        return /\.(svg|png|gif|jpe?g)$/i.test(path.basename(resourcePath));
    },

});

config.plugins.push(new CopyWebpackPlugin({patterns:copyPatterns}));

module.exports = config;
