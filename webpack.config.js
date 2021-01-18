/**
 * Webpack 5
 */
const dirscript = "./public/webpack";
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const TerserJSPlugin = require("terser-webpack-plugin");

module.exports = {
  mode: "production",
  entry: {
    app: path.resolve(__dirname, dirscript, "./src/app.js"),
  },
  output: {
    path: path.resolve(__dirname, dirscript, "./dest"),
    filename: "[name].min.js",
    publicPath: "./",
  },
  module: {
    rules: [
      {
        enforce: "pre",
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        loader: "eslint-loader",
      },
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader",
          options: {
            presets: ["@babel/preset-env", "@babel/preset-react"],
          },
        },
      },
      {
        test: /\.(sa|sc|c)ss$/i,
        use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
      },
      {
        test: /\.(gif|png|jpe?g|eot|wof|woff|woff2|ttf|svg)$/i,
        use: [
          {
            loader: "url-loader",
            options: {
              limit: 1024 * 100,
              name: "[contenthash].[ext]",
              outputPath: "_output",
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: "[name].min.css",
    }),
  ],
  optimization: {
    minimizer: [new TerserJSPlugin({}), new OptimizeCSSAssetsPlugin({})],
  },
  resolve: {
    extensions: [".js", ".jsx"],
  },
  performance: {
    assetFilter: function (assetFilename) {
      return assetFilename.endsWith(
        ".js"
      );
    },
  },
  target: ["web", "es5"],
};
