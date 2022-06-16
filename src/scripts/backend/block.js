import { isUndefined, pickBy } from 'lodash';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import CustomServerSideRender from './custom-server-side-render';
import {
	__experimentalHeading as Heading,
	Flex,
	FlexItem,
	Icon,
	PanelBody,
	SelectControl,
	Spinner,
	Placeholder,
	Card,
	CardHeader,
	CardBody
} from '@wordpress/components';

const {fetch: origFetch} = window;
window.fetch = async (...args) => {
	const requestUrl = args.length > 0 ? args[0] : '';
	const response = await origFetch(...args);

	if ( requestUrl.includes( 'wpzoom/instagram-block' ) ) {
		response
			.clone()
			.json()
			.then( body => window.setTimeout( () => window.wpzInstaFrontendInit(), 300 ) )
			.catch( err => console.error( err ) )
		;
	}

	return response;
};

registerBlockType( 'wpzoom/instagram-block', {
	apiVersion: 2,
	title: 'Instagram Feed by WPZOOM',
	icon: 'instagram',
	category: 'wpzoom-blocks',
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
					pickBy( { per_page: -1 }, ( value ) => ! isUndefined( value ) )
				);

				return {
					feedsList: ! Array.isArray( feeds )
						? feeds
						: feeds.map( ( feed ) => {
							return {
								value: feed.id,
								label: 'title' in feed && 'rendered' in feed.title ? feed.title.rendered : __( '(No title)', 'instagram-widget-by-wpzoom' ),
							};
						} ),
				}
			}
		);
		const hasFeeds = !! feedsList?.length;

		if ( ! hasFeeds ) {
			return (
				<div { ...blockProps }>
					<Placeholder icon="instagram" label={ __( 'Instagram Feed by WPZOOM' ) }>
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
										label: __( '\u2014 Select a Feed \u2014', 'instagram-widget-by-wpzoom' ),
										value: -1,
										disabled: true,
										hidden: true,
									},
									...feedsList
								] }
								onChange={ ( newFeed ) => {
									setAttributes( { feed: Number( newFeed ) } );
								} }
							/>
						</PanelBody>
					</InspectorControls>
				}
				{
					feed > 0 ?
						(
							<CustomServerSideRender
								block="wpzoom/instagram-block"
								attributes={ props.attributes }
								EmptyResponsePlaceholder={ () => (
									<span>{ __( 'Instagram: No feed to show.', 'instagram-widget-by-wpzoom' ) }</span>
								) }
							/>
						)
					:
						(
							<Card size="large">
								<CardHeader>
									<Flex align="center" justify="start" gap={ 2 } wrap={ true }>
										<Icon icon="instagram" />
										<Heading level="5">{ __( 'Instagram Feed by WPZOOM', 'instagram-widget-by-wpzoom' ) }</Heading>
									</Flex>
								</CardHeader>
								<CardBody>
									<SelectControl
										className='wpzoom-instagram-widget-select-feed'
										value={ feed }
										options={ [
											{
												label: __( '\u2014 Select a Feed to Display \u2014', 'instagram-widget-by-wpzoom' ),
												value: -1,
												disabled: true,
												hidden: true,
											},
											...feedsList
										] }
										onChange={ ( newFeed ) => {
											setAttributes( { feed: Number( newFeed ) } );
										} }
									/>
								</CardBody>
							</Card>
						)
				}
			</div>
		);
	},
} );
