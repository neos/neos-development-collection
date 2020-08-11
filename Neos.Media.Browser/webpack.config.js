const merge = require('webpack-merge');
const path = require('path');
const sharedWebpackConfig = require('@neos-project/build-essentials/src/webpack.config.js');
delete sharedWebpackConfig.__internalDependencies;

module.exports = merge(
    sharedWebpackConfig,
    {
        entry: {
            Main: [
                './packages/neos-media-browser/src/index.js'
            ]
        },

        output: {
            filename: 'JavaScript/[name].js',
            path: path.resolve(__dirname, './Resources/Public/')
        },

        resolve: {
            modules: [
                path.resolve(__dirname, './node_modules')
            ]
        }
    }
);
