const debug = process.env.NODE_ENV !== 'production';
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const webpack = require('webpack');

const webpackConfig = {
  context: __dirname,
  devtool: debug ? 'inline-sourcemap' : false,
  entry: {
    Main: [
        './Resources/Public/JavaScript/index.js',
        './Resources/Private/Styles/Neos.scss',
    ]
  },
  output: {
    path: __dirname + '/Resources/Public/JavaScript',
    filename: '[name].min.js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.scss$/,
        use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader'
          ]
      },
      {
        test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: '../Fonts/'
            }
          }
        ]
      }
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({filename: '../Styles/[name].css'})
  ],
  optimization: {
    minimizer: []
  },
  performance: {
    hints: debug ? 'warning' : false
  }
};


if (!debug) {
  webpackConfig.optimization.minimizer.push(
      new TerserPlugin({
          terserOptions: {
              sourceMap: true,
              warnings: false,
              parse: {},
              compress: {},
              mangle: true,
              keep_fnames: true
          }
      }),
  );
}

module.exports = webpackConfig;
