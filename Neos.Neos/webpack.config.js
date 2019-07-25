const debug = process.env.NODE_ENV !== 'production';
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const webpack = require('webpack');

const javascriptConfig = {
  context: __dirname,
  devtool: debug ? 'inline-sourcemap' : false,
  entry: {
    Main: [
        './Resources/Public/JavaScript/index.js',
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
      }
    ],
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: "jquery",
      jQuery: "jquery"
    })
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
        Main: [
          './Resources/Private/Styles/Neos.scss',
        ],
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

  javascriptConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
  stylesConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
}

function buildConfig(env) {
  if (env === 'js') {
    return javascriptConfig;
  } else if (env === 'styles') {
    return stylesConfig;
  } else {
    console.log("Wrong webpack build parameter. Possible choices: 'js' or 'styles'.")
  }
}

module.exports = buildConfig;
