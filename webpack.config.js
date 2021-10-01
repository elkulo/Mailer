/**
 * Webpack 5
 *
 * @version 2021.10.01
 */
const dirscript = "./public/webpack";

/* Plugins */
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserJSPlugin = require("terser-webpack-plugin");
const ESLintPlugin = require("eslint-webpack-plugin");

module.exports = {
  mode: "production",
  entry: {
    app: path.resolve(__dirname, dirscript, "./src/app.js"),
    csrf: path.resolve(__dirname, dirscript, "./src/csrf.js"),
  },
  output: {
    path: path.resolve(__dirname, dirscript, "./dest"),
    filename: "[name].min.js",
    publicPath: "auto",
    assetModuleFilename: "_output/[hash][ext]",
  },
  module: {
    rules: [
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
    new ESLintPlugin(),
    new MiniCssExtractPlugin({
      filename: "[name].min.css",
    }),
  ],
  optimization: {
    minimizer: [new TerserJSPlugin(), new CssMinimizerPlugin()],
  },
  resolve: {
    extensions: [".js"],
  },
  performance: {
    assetFilter: function (assetFilename) {
      return assetFilename.endsWith(".js");
    },
  },
};
