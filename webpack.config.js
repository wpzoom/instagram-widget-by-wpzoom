const path = require( 'path' );
const glob = require( 'glob' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const postcssConfig = require( './postcss.config' );

const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );
const nodeSassGlobImporter = require( 'node-sass-glob-importer' );
const CopyPlugin = require( 'copy-webpack-plugin' );

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
							sassOptions: {
								importer: nodeSassGlobImporter(),
							},
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
	],
};
