const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const ayoolaDependencyExtractionWebpackPlugin = require('@ayoola/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@ayoola/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@ayoola/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@ayoola/blocks-registry': 'wc-blocks-registry',
	'@ayoola/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
module.exports = {
	...defaultConfig,
	entry: {
		'frontend/blocks': '/resources/js/frontend/index.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new ayoolaDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	]
};
