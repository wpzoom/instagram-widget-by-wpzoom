import { isUndefined, pickBy } from 'lodash';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, Spinner, Placeholder } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { widget } from '@wordpress/icons';
 
registerBlockType( 'wpzoom/instagram-block', {
	apiVersion: 2,
	title: 'Instagram',
	icon: 'megaphone',
	category: 'widgets',
	attributes: {
		feed: {
			type: 'integer',
			default: -1,
		},
	},

	edit: function ( props ) {
		const {
			attributes: { feed },
			setAttributes,
			className,
		} = props;
		const blockProps = useBlockProps();
		const {
			feedsList,
		} = useSelect(
			( select ) => {
				const { getEntityRecords } = select( coreStore );
				const feeds = getEntityRecords(
					'postType',
					'wpz-insta_feed',
					pickBy(
						{
							per_page: -1,
						},
						( value ) => ! isUndefined( value )
					)
				);

				return {
					feedsList: ! Array.isArray( feeds )
						? feeds
						: feeds.map( ( feed ) => {
							return {
								value: feed.id,
								label: 'meta' in feed && '_wpz-insta_feed-title' in feed.meta ? feed.meta['_wpz-insta_feed-title'] : __( '(No title)', 'instagram-widget-by-wpzoom' ),
							};
						} ),
				}
			}
		);
		const hasFeeds = !! feedsList?.length;

		if ( ! hasFeeds ) {
			return (
				<div { ...blockProps }>
					<Placeholder icon={ widget } label={ __( 'Instagram Feed' ) }>
						{ ! Array.isArray( feedsList ) ? (
							<Spinner />
						) : (
							__( 'You must create some feeds to use this block properly.', 'instagram-widget-by-wpzoom' )
						) }
					</Placeholder>
				</div>
			);
		}

		return (
			<div { ...blockProps }>
				{
					<InspectorControls>
						<PanelBody title={ __( 'Feed settings', 'instagram-widget-by-wpzoom' ) }>
							<SelectControl
								label={ __( 'Feed to Display', 'instagram-widget-by-wpzoom' ) }
								value={ feed }
								options={ [
									{
										label: __( '-- Select a Feed --', 'instagram-widget-by-wpzoom' ),
										value: -1,
										disabled: true,
										hidden: true,
									},
									...feedsList
								] }
								onChange={ ( newFeed ) => {
									setAttributes( { feed: newFeed } );
								} }
							/>
						</PanelBody>
					</InspectorControls>
				}
				<ServerSideRender
					block="wpzoom/instagram-block"
					attributes={ props.attributes }
					EmptyResponsePlaceholder={ () => (
						<span>{ __( 'Instagram: No feed to show.', 'instagram-widget-by-wpzoom' ) }</span>
					) }
				/>
			</div>
		);
	},
} );
