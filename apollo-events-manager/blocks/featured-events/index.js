/**
 * Apollo Featured Events Block - Editor Interface
 *
 * Gutenberg block for displaying featured events in a carousel.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 */

import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
    PanelBody,
    Placeholder,
    RangeControl,
    SelectControl,
    Spinner,
    ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Featured Events Icon
 */
const FeaturedEventsIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor" />
    </svg>
);

/**
 * Featured Event Card Preview
 */
const FeaturedEventCard = ( { event, showDate, showLocation, showTicketButton } ) => {
    const title = event.title?.rendered || __( 'Featured Event', 'apollo-events-manager' );
    const image = event.meta?._event_banner || '';
    const date = event.meta?._event_start_date || '';
    const location = event.meta?._event_location || '';

    return (
        <div className="apollo-featured-event-card">
            <div className="apollo-featured-event-card__image">
                { image ? (
                    <img src={ image } alt={ title } />
                ) : (
                    <div className="apollo-featured-event-card__placeholder">
                        <i className="ri-calendar-event-line" />
                    </div>
                ) }
                <div className="apollo-featured-event-card__overlay">
                    <span className="apollo-featured-event-card__badge">
                        <i className="ri-star-fill" />
                        { __( 'Destaque', 'apollo-events-manager' ) }
                    </span>
                    <div className="apollo-featured-event-card__content">
                        <h3
                            className="apollo-featured-event-card__title"
                            dangerouslySetInnerHTML={ { __html: title } }
                        />
                        <div className="apollo-featured-event-card__meta">
                            { showDate && date && (
                                <span className="apollo-featured-event-card__date">
                                    <i className="ri-calendar-line" />
                                    { date }
                                </span>
                            ) }
                            { showLocation && location && (
                                <span className="apollo-featured-event-card__location">
                                    <i className="ri-map-pin-line" />
                                    { location }
                                </span>
                            ) }
                        </div>
                        { showTicketButton && (
                            <div className="apollo-featured-event-card__actions">
                                <span className="apollo-btn apollo-btn--primary">
                                    { __( 'Ver Evento', 'apollo-events-manager' ) }
                                </span>
                            </div>
                        ) }
                    </div>
                </div>
            </div>
        </div>
    );
};

/**
 * Featured Events Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        layout,
        limit,
        autoplay,
        autoplaySpeed,
        showDots,
        showArrows,
        showDate,
        showLocation,
        showTicketButton,
        aspectRatio,
    } = attributes;

    const [ events, setEvents ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    // Fetch featured events.
    useEffect( () => {
        setLoading( true );
        setError( null );

        apiFetch( {
            path: `/wp/v2/event_listing?per_page=${ limit }&meta_key=_event_featured&meta_value=1&orderby=meta_value&order=desc`,
        } )
            .then( ( response ) => {
                setEvents( response );
                setLoading( false );
            } )
            .catch( ( err ) => {
                setError( err.message );
                setLoading( false );
            } );
    }, [ limit ] );

    const blockProps = useBlockProps( {
        className: `apollo-featured-events-block layout-${ layout }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout Settings', 'apollo-events-manager' ) } initialOpen={ true }>
                    <SelectControl
                        label={ __( 'Layout', 'apollo-events-manager' ) }
                        value={ layout }
                        options={ [
                            { value: 'carousel', label: __( 'Carousel', 'apollo-events-manager' ) },
                            { value: 'grid', label: __( 'Grid', 'apollo-events-manager' ) },
                            { value: 'hero', label: __( 'Hero (Single)', 'apollo-events-manager' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    <RangeControl
                        label={ __( 'Number of Events', 'apollo-events-manager' ) }
                        value={ limit }
                        onChange={ ( value ) => setAttributes( { limit: value } ) }
                        min={ 1 }
                        max={ 10 }
                    />
                    <SelectControl
                        label={ __( 'Aspect Ratio', 'apollo-events-manager' ) }
                        value={ aspectRatio }
                        options={ [
                            { value: '21/9', label: '21:9 (Ultrawide)' },
                            { value: '16/9', label: '16:9 (Widescreen)' },
                            { value: '4/3', label: '4:3 (Standard)' },
                            { value: '1/1', label: '1:1 (Square)' },
                        ] }
                        onChange={ ( value ) => setAttributes( { aspectRatio: value } ) }
                    />
                </PanelBody>

                { layout === 'carousel' && (
                    <PanelBody title={ __( 'Carousel Settings', 'apollo-events-manager' ) } initialOpen={ false }>
                        <ToggleControl
                            label={ __( 'Autoplay', 'apollo-events-manager' ) }
                            checked={ autoplay }
                            onChange={ ( value ) => setAttributes( { autoplay: value } ) }
                        />
                        { autoplay && (
                            <RangeControl
                                label={ __( 'Autoplay Speed (ms)', 'apollo-events-manager' ) }
                                value={ autoplaySpeed }
                                onChange={ ( value ) => setAttributes( { autoplaySpeed: value } ) }
                                min={ 1000 }
                                max={ 10000 }
                                step={ 500 }
                            />
                        ) }
                        <ToggleControl
                            label={ __( 'Show Dots', 'apollo-events-manager' ) }
                            checked={ showDots }
                            onChange={ ( value ) => setAttributes( { showDots: value } ) }
                        />
                        <ToggleControl
                            label={ __( 'Show Arrows', 'apollo-events-manager' ) }
                            checked={ showArrows }
                            onChange={ ( value ) => setAttributes( { showArrows: value } ) }
                        />
                    </PanelBody>
                ) }

                <PanelBody title={ __( 'Display Options', 'apollo-events-manager' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Date', 'apollo-events-manager' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Location', 'apollo-events-manager' ) }
                        checked={ showLocation }
                        onChange={ ( value ) => setAttributes( { showLocation: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Ticket Button', 'apollo-events-manager' ) }
                        checked={ showTicketButton }
                        onChange={ ( value ) => setAttributes( { showTicketButton: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { loading && (
                    <Placeholder
                        icon={ <FeaturedEventsIcon /> }
                        label={ __( 'Featured Events', 'apollo-events-manager' ) }
                    >
                        <Spinner />
                    </Placeholder>
                ) }

                { error && (
                    <Placeholder
                        icon={ <FeaturedEventsIcon /> }
                        label={ __( 'Featured Events', 'apollo-events-manager' ) }
                        instructions={ error }
                    />
                ) }

                { ! loading && ! error && events.length === 0 && (
                    <Placeholder
                        icon={ <FeaturedEventsIcon /> }
                        label={ __( 'Featured Events', 'apollo-events-manager' ) }
                        instructions={ __( 'No featured events found. Mark events as featured in the event editor.', 'apollo-events-manager' ) }
                    />
                ) }

                { ! loading && ! error && events.length > 0 && (
                    <div
                        className={ `apollo-featured-events apollo-featured-events--${ layout }` }
                        style={ { '--aspect-ratio': aspectRatio } }
                    >
                        { events.slice( 0, layout === 'hero' ? 1 : limit ).map( ( event ) => (
                            <FeaturedEventCard
                                key={ event.id }
                                event={ event }
                                showDate={ showDate }
                                showLocation={ showLocation }
                                showTicketButton={ showTicketButton }
                            />
                        ) ) }
                    </div>
                ) }
            </div>
        </>
    );
};

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
    icon: <FeaturedEventsIcon />,
    edit: Edit,
    save: () => null,
} );
