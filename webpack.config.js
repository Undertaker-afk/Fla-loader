const config = require('flarum-webpack-config')();

// Override output path to match extend.php expectations.
// The default flarum-webpack-config outputs to 'dist/', but this extension's
// extend.php references 'js/dist/' for the compiled JavaScript files.
config.output.path = require('path').resolve(__dirname, 'js/dist');

module.exports = config;
