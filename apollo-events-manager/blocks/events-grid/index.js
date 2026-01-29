/**
 * Apollo Events Grid Block - Editor Interface
 *
 * Gutenberg block for displaying events in a grid layout.
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
    ToggleControl
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Events Grid Icon
 */
const EventsGridIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor" />
        <rect x="14" y="3" width="7" height="7" rx="1" fill="currentColor" />
        <rect x="3" y="14" width="7" height="7" rx="1" fill="currentColor" />
        <rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor" />
    </svg>
);

/**
 * Event Card Preview Component
 */
const EventCardPreview = ( { event, showImage, showDate, showLocation } ) => {
    const title = event.title?.rendered || event.title || __( 'Event Title', 'apollo-events-manager' );
    const image = event.featured_media_url || event._event_banner || '';
    const date = event.meta?._event_start_date || event._event_start_date || '';
    const location = event.meta?._event_location || event._event_location || '';

    return (
        <article className="apollo-event-card">
            { showImage && (
                <div className="apollo-event-card__image">
                    { image ? (
                        <img src={ image } alt={ title } />
                    ) : (
                        <div className="apollo-event-card__image-placeholder" />
                    ) }
                </div>
            ) }
            <div className="apollo-event-card__content">
                <h3 className="apollo-event-card__title" dangerouslySetInnerHTML={ { __html: title } } />
                <div className="apollo-event-card__meta">
                    { showDate && date && (
                        <span className="apollo-event-card__meta-item">
                            <i className="ri-calendar-line" />
                            { date }
                        </span>
                    ) }
                    { showLocation && location && (
                        <span className="apollo-event-card__meta-item">
                            <i className="ri-map-pin-line" />
                            { location }
                        </span>
                    ) }
                </div>
            </div>
        </article>
    );
};

/**
 * Events Grid Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        layout,
        columns,
        limit,
        category,
        type,
        sounds,
        season,
        orderby,
        order,
        featured,
        showImage,
        showDate,
        showLocation,
        showExcerpt,
        showPagination,
    } = attributes;

    const [ events, setEvents ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    // Fetch taxonomy terms.
    const categories = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'taxonomy', 'event_listing_category', {
            per_page: -1,
            hide_empty: false,
        } ) || [];
    }, [] );

    const types = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'taxonomy', 'event_listing_type', {
            per_page: -1,
            hide_empty: false,
        } ) || [];
    }, [] );

    const soundsTerms = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'taxonomy', 'event_sounds', {
            per_page: -1,
            hide_empty: false,
        } ) || [];
    }, [] );

    const seasons = useSelect( ( select ) => {
        return select( 'core' ).getEntityRecords( 'taxonomy', 'event_season', {
            per_page: -1,
            hide_empty: false,
        } ) || [];
    }, [] );

    // Fetch events for preview.
    useEffect( () => {
        setLoading( true );
        setError( null );

        const params = new URLSearchParams( {
            per_page: limit,
            orderby: orderby === 'event_date' ? 'meta_value' : orderby,
            order: order,
            _embed: true,
        } );

        if ( orderby === 'event_date' ) {
            params.append( 'meta_key', '_event_start_date' );
        }

        if ( category ) {
            params.append( 'event_listing_category', category );
        }

        if ( type ) {
            params.append( 'event_listing_type', type );
        }

        if ( featured ) {
            params.append( 'meta_key', '_event_featured' );
            params.append( 'meta_value', '1' );
        }

        apiFetch( { path: `/wp/v2/event_listing?${ params.toString() }` } )
            .then( ( response ) => {
                setEvents( response );
                setLoading( false );
            } )
            .catch( ( err ) => {
                setError( err.message );
                setLoading( false );
            } );
    }, [ limit, category, type, orderby, order, featured ] );

    const blockProps = useBlockProps( {
        className: `apollo-events-grid-block layout-${ layout }`,
    } );

    // Build category options.
    const categoryOptions = [
        { value: '', label: __( 'All Categories', 'apollo-events-manager' ) },
        ...categories.map( ( cat ) => ( {
            value: cat.id.toString(),
            label: cat.name,
        } ) ),
    ];

    // Build type options.
    const typeOptions = [
        { value: '', label: __( 'All Types', 'apollo-events-manager' ) },
        ...types.map( ( t ) => ( {
            value: t.id.toString(),
            label: t.name,
        } ) ),
    ];

    // Build sounds options.
    const soundsOptions = [
        { value: '', label: __( 'All Sounds', 'apollo-events-manager' ) },
        ...soundsTerms.map( ( s ) => ( {
            value: s.id.toString(),
            label: s.name,
        } ) ),
    ];

    // Build season options.
    const seasonOptions = [
        { value: '', label: __( 'All Seasons', 'apollo-events-manager' ) },
        ...seasons.map( ( s ) => ( {
            value: s.id.toString(),
            label: s.name,
        } ) ),
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout Settings', 'apollo-events-manager' ) } initialOpen={ true }>
                    <SelectControl
                        label={ __( 'Layout', 'apollo-events-manager' ) }
                        value={ layout }
                        options={ [
                            { value: 'grid', label: __( 'Grid', 'apollo-events-manager' ) },
                            { value: 'list', label: __( 'List', 'apollo-events-manager' ) },
                            { value: 'carousel', label: __( 'Carousel', 'apollo-events-manager' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    { layout === 'grid' && (
                        <RangeControl
                            label={ __( 'Columns', 'apollo-events-manager' ) }
                            value={ columns }
                            onChange={ ( value ) => setAttributes( { columns: value } ) }
                            min={ 1 }
                            max={ 4 }
                        />
                    ) }
                    <RangeControl
                        label={ __( 'Number of Events', 'apollo-events-manager' ) }
                        value={ limit }
                        onChange={ ( value ) => setAttributes( { limit: value } ) }
                        min={ 1 }
                        max={ 24 }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Filter Settings', 'apollo-events-manager' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Category', 'apollo-events-manager' ) }
                        value={ category }
                        options={ categoryOptions }
                        onChange={ ( value ) => setAttributes( { category: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Type', 'apollo-events-manager' ) }
                        value={ type }
                        options={ typeOptions }
                        onChange={ ( value ) => setAttributes( { type: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Sounds', 'apollo-events-manager' ) }
                        value={ sounds }
                        options={ soundsOptions }
                        onChange={ ( value ) => setAttributes( { sounds: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Season', 'apollo-events-manager' ) }
                        value={ season }
                        options={ seasonOptions }
                        onChange={ ( value ) => setAttributes( { season: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Featured Only', 'apollo-events-manager' ) }
                        checked={ featured }
                        onChange={ ( value ) => setAttributes( { featured: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Order Settings', 'apollo-events-manager' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Order By', 'apollo-events-manager' ) }
                        value={ orderby }
                        options={ [
                            { value: 'event_date', label: __( 'Event Date', 'apollo-events-manager' ) },
                            { value: 'date', label: __( 'Publish Date', 'apollo-events-manager' ) },
                            { value: 'title', label: __( 'Title', 'apollo-events-manager' ) },
                            { value: 'rand', label: __( 'Random', 'apollo-events-manager' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { orderby: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Order', 'apollo-events-manager' ) }
                        value={ order }
                        options={ [
                            { value: 'asc', label: __( 'Ascending', 'apollo-events-manager' ) },
                            { value: 'desc', label: __( 'Descending', 'apollo-events-manager' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { order: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Display Settings', 'apollo-events-manager' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Image', 'apollo-events-manager' ) }
                        checked={ showImage }
                        onChange={ ( value ) => setAttributes( { showImage: value } ) }
                    />
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
                        label={ __( 'Show Excerpt', 'apollo-events-manager' ) }
                        checked={ showExcerpt }
                        onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Pagination', 'apollo-events-manager' ) }
                        checked={ showPagination }
                        onChange={ ( value ) => setAttributes( { showPagination: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { loading && (
                    <Placeholder
                        icon={ <EventsGridIcon /> }
                        label={ __( 'Events Grid', 'apollo-events-manager' ) }
                    >
                        <Spinner />
                    </Placeholder>
                ) }

                { error && (
                    <Placeholder
                        icon={ <EventsGridIcon /> }
                        label={ __( 'Events Grid', 'apollo-events-manager' ) }
                        instructions={ error }
                    />
                ) }

                { ! loading && ! error && events.length === 0 && (
                    <Placeholder
                        icon={ <EventsGridIcon /> }
                        label={ __( 'Events Grid', 'apollo-events-manager' ) }
                        instructions={ __( 'No events found. Try adjusting your filter settings.', 'apollo-events-manager' ) }
                    />
                ) }

                { ! loading && ! error && events.length > 0 && (
                    <div
                        className={ `apollo-events-grid apollo-events-grid--cols-${ columns } apollo-events-grid--${ layout }` }
                        style={ {
                            display: layout === 'list' ? 'flex' : 'grid',
                            flexDirection: layout === 'list' ? 'column' : undefined,
                            gridTemplateColumns: layout === 'grid' ? `repeat(${ columns }, 1fr)` : undefined,
                            gap: '1.5rem',
                        } }
                    >
                        { events.map( ( event ) => (
                            <EventCardPreview
                                key={ event.id }
                                event={ event }
                                showImage={ showImage }
                                showDate={ showDate }
                                showLocation={ showLocation }
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
    icon: <EventsGridIcon />,
    edit: Edit,
    save: () => null, // Server-side rendered.
} );
