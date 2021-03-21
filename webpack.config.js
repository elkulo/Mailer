/**
 * Webpack 5
 *
 * @version 2021.03.22
 */
const dirscript = "./public/webpack";

/* Plugins */
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
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
    assetModuleFilename: "_output/[hash][ext]",
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
            presets: ["@babel/preset-env"],
          },
        },
      },
      {
        test: /\.(sa|sc|c)ss$/i,
        use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
      },
      {
        test: /\.(gif|png|jpe?g|eot|wof|woff|woff2|ttf|svg)$/i,
        type: "asset",
        parser: {
          dataUrlCondition: {
            maxSize: 1024 * 100 /* 100KB以上のファイルは書き出し */,
          },
        },
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: "[name].min.css",
    }),
  ],
  optimization: {
    minimizer: [new TerserJSPlugin({}), new CssMinimizerPlugin()],
  },
  resolve: {
    extensions: [".js", ".jsx"],
  },
  performance: {
    assetFilter: function (assetFilename) {
      return assetFilename.endsWith(".js");
    },
  },
  target: ["web", "es5"],
};
