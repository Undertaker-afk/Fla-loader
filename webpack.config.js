const config = require('flarum-webpack-config')();

// Override output path to match extend.php expectations
config.output.path = require('path').resolve(__dirname, 'js/dist');

module.exports = config;
