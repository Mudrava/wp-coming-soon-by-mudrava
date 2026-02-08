/**
 * Webpack configuration for the Coming Soon by Mudrava plugin.
 *
 * Extends the default @wordpress/scripts configuration with custom entry
 * points for the React settings application and the frontend stylesheet.
 * Block entry points are discovered automatically by wp-scripts.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'settings/index': path.resolve( __dirname, 'src/settings/index.js' ),
		'frontend/coming-soon': path.resolve(
			__dirname,
			'src/frontend/coming-soon.scss'
		),
	},
};
