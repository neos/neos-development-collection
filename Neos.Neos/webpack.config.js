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
    ],
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
        test    : /\.(gif|png|jpg|svg)$/,
        include : __dirname + '/Resources/Public/Images/',
        loader  : 'url-loader?limit=30000&name=images/[name].[ext]'
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
    new webpack.ProvidePlugin({
      $: "jquery",
      jQuery: "jquery"
    }),
    new MiniCssExtractPlugin({filename: '../Styles/[name].css'})
  ],
  optimization: {
    minimizer: []
  },
  performance: {
    hints: debug ? 'warning' : false
  }
};

const stylesConfig = {
    context: __dirname,
    devtool: debug ? 'inline-sourcemap' : false,
    entry: {
        Login: [
            './Resources/Private/Styles/Login.scss',
        ],
        Error: [
            './Resources/Private/Styles/Error.scss',
        ],
        RawContentMode: [
            './Resources/Private/Styles/RawContentMode.scss',
        ],
        Welcome: [
            './Resources/Private/Styles/Welcome.scss',
        ]
    },
    output: {
        path: __dirname + '/Resources/Public/Styles',
        filename: '[name].css'
    },
    module: {
        rules: [
        {
            test    : /\.(gif|png|jpg|svg)$/,
            include : __dirname + '/Resources/Public/Images/',
            loader  : 'url-loader?limit=30000&name=images/[name].[ext]'
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
        new MiniCssExtractPlugin({filename: './[name].css'})
    ],
    optimization: {
        minimizer: []
    },
    performance: {
        hints: debug ? 'warning' : false
    }
};


if (!debug) {
  const terserOptions = {
    terserOptions: {
        sourceMap: true,
        warnings: false,
        parse: {},
        compress: {},
        mangle: true,
        keep_fnames: true
    }
  };

  webpackConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
  stylesConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
}

module.exports = webpackConfig;
// module.exports = stylesConfig;
