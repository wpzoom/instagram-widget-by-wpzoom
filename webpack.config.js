const path = require( 'path' );
const glob = require( 'glob' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const postcssConfig = require( './postcss.config' );

const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );
// node-sass-glob-importer removed - incompatible with modern sass-loader
const CopyPlugin = require( 'copy-webpack-plugin' );
const fs = require( 'fs' );

// Custom plugin to generate asset files for CSS-only entries
class GenerateCSSAssetFiles {
	apply(compiler) {
		compiler.hooks.afterEmit.tap('GenerateCSSAssetFiles', (compilation) => {
			const cssEntries = [
				'styles/frontend/index',
				'styles/frontend/preview',
				'styles/backend/index'
			];

			cssEntries.forEach(entry => {
				const assetPath = path.join(compiler.options.output.path, `${entry}.asset.php`);
				const cssPath = path.join(compiler.options.output.path, `${entry}.css`);
				
				// Generate a version hash based on CSS file content
				let version = 'css-' + Date.now();
				if (fs.existsSync(cssPath)) {
					const cssContent = fs.readFileSync(cssPath, 'utf8');
					version = 'css-' + require('crypto').createHash('md5').update(cssContent).digest('hex').substring(0, 12);
				}

				const assetContent = `<?php return array('dependencies' => array(), 'version' => '${version}');`;
				
				// Ensure directory exists
				const assetDir = path.dirname(assetPath);
				if (!fs.existsSync(assetDir)) {
					fs.mkdirSync(assetDir, { recursive: true });
				}
				
				fs.writeFileSync(assetPath, assetContent);
			});
		});
	}
}

const isProduction = process.env.NODE_ENV === 'production';

// Rewrite defaultConfig css loaders for own use
const cssLoaders = [
	{
		loader: MiniCssExtractPlugin.loader,
	},
	{
		loader: require.resolve( 'css-loader' ),
		options: {
			sourceMap: ! isProduction,
		},
	},
	{
		loader: require.resolve( 'postcss-loader' ),
		options: {
			postcssOptions: {
				...postcssConfig,
			},
			sourceMap: ! isProduction,
		},
	},
];

const srcdir = path.resolve( process.cwd(), 'src' );

module.exports = {
	...defaultConfig,

	entry: {
		'scripts/frontend/index': path.resolve( process.cwd(), 'src/scripts/frontend/index.js' ),
		'scripts/frontend/block': path.resolve( process.cwd(), 'src/scripts/frontend/block.js' ),
		'scripts/frontend/stories': path.resolve( process.cwd(), 'src/scripts/frontend/stories.js' ),
		'scripts/backend/index': path.resolve( process.cwd(), 'src/scripts/backend/index.js' ),
		'scripts/backend/block': path.resolve( process.cwd(), 'src/scripts/backend/block.js' ),
		'scripts/backend/cron-dismiss': path.resolve( process.cwd(), 'src/scripts/backend/cron-dismiss.js' ),
		'scripts/frontend/preview': path.resolve( process.cwd(), 'src/scripts/frontend/preview.js' ),
		'styles/frontend/index': path.resolve( process.cwd(), 'src/styles/frontend/index.scss' ),
		'styles/frontend/preview': path.resolve( process.cwd(), 'src/styles/frontend/preview.scss' ),
		'styles/backend/index': path.resolve( process.cwd(), 'src/styles/backend/index.scss' )
	},

	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'dist/' ),
	},

	// Skip splitChunks from defaultConfig
	optimization: {
		concatenateModules: defaultConfig.optimization.concatenateModules,
		minimizer: defaultConfig.optimization.minimizer,
	},

	module: {
		// Rewrite defaultConfig module rules
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: [
					require.resolve( 'thread-loader' ),
					{
						loader: require.resolve( 'babel-loader' ),
						options: {
							// Babel uses a directory within local node_modules
							// by default. Use the environment variable option
							// to enable more persistent caching.
							cacheDirectory:
								process.env.BABEL_CACHE_DIRECTORY || true,
						},
					},
				],
			},
			{
				test: /\.css$/,
				use: cssLoaders,
			},
			{
				test: /\.(sc|sa)ss$/,
				use: [
					...cssLoaders,
					{
						loader: require.resolve( 'sass-loader' ),
						options: {
							sourceMap: ! isProduction,
						},
					},
				],
			},
		],
	},

	stats: {
		...defaultConfig.stats,
		modules: false,
		warnings: false,
	},

	plugins: [
		...defaultConfig.plugins,
		new RemoveEmptyScriptsPlugin(),
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
		new RtlCssPlugin( {
			filename: '[name]-rtl.css',
		} ),
		new CopyPlugin( {
			patterns: [
				{
					from: path.resolve( process.cwd(), 'src/images' ),
					to: path.resolve( process.cwd(), 'dist/images' ),
				},
				{
					from: path.resolve( process.cwd(), 'src/scripts/library' ),
					to: path.resolve( process.cwd(), 'dist/scripts/library' ),
				},
				{
					from: path.resolve( process.cwd(), 'src/styles/library' ),
					to: path.resolve( process.cwd(), 'dist/styles/library' ),
				},
			],
		} ),
		new GenerateCSSAssetFiles(),
	],
};
