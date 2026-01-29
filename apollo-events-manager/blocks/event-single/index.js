/**
 * Apollo Event Single Block - Editor Interface
 *
 * Gutenberg block for displaying a single event with full details.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 */

import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
    ComboboxControl,
    PanelBody,
    Placeholder,
    SelectControl,
    Spinner,
    ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Event Single Icon
 */
const EventSingleIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" strokeWidth="2" fill="none" />
        <line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" strokeWidth="2" />
        <circle cx="12" cy="14" r="2" fill="currentColor" />
    </svg>
);

/**
 * Event Single Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        eventId,
        showBanner,
        showDate,
        showLocation,
        showMap,
        showDJs,
        showTimetable,
        showTickets,
        showShare,
        layout,
    } = attributes;

    const [ event, setEvent ] = useState( null );
    const [ loading, setLoading ] = useState( false );
    const [ eventOptions, setEventOptions ] = useState( [] );

    // Fetch events for selection.
    useEffect( () => {
        apiFetch( { path: '/wp/v2/event_listing?per_page=100&orderby=title&order=asc' } )
            .then( ( response ) => {
                const options = response.map( ( e ) => ( {
                    value: e.id,
                    label: e.title.rendered,
                } ) );
                setEventOptions( options );
            } )
            .catch( () => {
                setEventOptions( [] );
            } );
    }, [] );

    // Fetch selected event.
    useEffect( () => {
        if ( ! eventId ) {
            setEvent( null );
            return;
        }

        setLoading( true );
        apiFetch( { path: `/wp/v2/event_listing/${ eventId }` } )
            .then( ( response ) => {
                setEvent( response );
                setLoading( false );
            } )
            .catch( () => {
                setEvent( null );
                setLoading( false );
            } );
    }, [ eventId ] );

    const blockProps = useBlockProps( {
        className: `apollo-event-single-block layout-${ layout }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Event Selection', 'apollo-events-manager' ) } initialOpen={ true }>
                    <ComboboxControl
                        label={ __( 'Select Event', 'apollo-events-manager' ) }
                        value={ eventId }
                        options={ eventOptions }
                        onChange={ ( value ) => setAttributes( { eventId: parseInt( value, 10 ) || 0 } ) }
                        onFilterValueChange={ () => {} }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Layout', 'apollo-events-manager' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Layout Style', 'apollo-events-manager' ) }
                        value={ layout }
                        options={ [
                            { value: 'full', label: __( 'Full Width', 'apollo-events-manager' ) },
                            { value: 'card', label: __( 'Card', 'apollo-events-manager' ) },
                            { value: 'compact', label: __( 'Compact', 'apollo-events-manager' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Display Options', 'apollo-events-manager' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Banner', 'apollo-events-manager' ) }
                        checked={ showBanner }
                        onChange={ ( value ) => setAttributes( { showBanner: value } ) }
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
                        label={ __( 'Show Map', 'apollo-events-manager' ) }
                        checked={ showMap }
                        onChange={ ( value ) => setAttributes( { showMap: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show DJs/Artists', 'apollo-events-manager' ) }
                        checked={ showDJs }
                        onChange={ ( value ) => setAttributes( { showDJs: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Timetable', 'apollo-events-manager' ) }
                        checked={ showTimetable }
                        onChange={ ( value ) => setAttributes( { showTimetable: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Tickets Button', 'apollo-events-manager' ) }
                        checked={ showTickets }
                        onChange={ ( value ) => setAttributes( { showTickets: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Share Buttons', 'apollo-events-manager' ) }
                        checked={ showShare }
                        onChange={ ( value ) => setAttributes( { showShare: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { ! eventId && (
                    <Placeholder
                        icon={ <EventSingleIcon /> }
                        label={ __( 'Event Single', 'apollo-events-manager' ) }
                        instructions={ __( 'Select an event to display from the sidebar.', 'apollo-events-manager' ) }
                    />
                ) }

                { eventId && loading && (
                    <Placeholder
                        icon={ <EventSingleIcon /> }
                        label={ __( 'Event Single', 'apollo-events-manager' ) }
                    >
                        <Spinner />
                    </Placeholder>
                ) }

                { eventId && ! loading && ! event && (
                    <Placeholder
                        icon={ <EventSingleIcon /> }
                        label={ __( 'Event Single', 'apollo-events-manager' ) }
                        instructions={ __( 'Event not found. It may have been deleted.', 'apollo-events-manager' ) }
                    />
                ) }

                { eventId && ! loading && event && (
                    <div className="apollo-event-single">
                        { showBanner && (
                            <div className="apollo-event-single__hero">
                                { event.meta?._event_banner ? (
                                    <img src={ event.meta._event_banner } alt={ event.title.rendered } />
                                ) : (
                                    <div className="apollo-event-single__hero-placeholder">
                                        <i className="ri-calendar-event-line" />
                                    </div>
                                ) }
                            </div>
                        ) }
                        <div className="apollo-event-single__content">
                            <h2
                                className="apollo-event-single__title"
                                dangerouslySetInnerHTML={ { __html: event.title.rendered } }
                            />
                            { showDate && event.meta?._event_start_date && (
                                <div className="apollo-event-single__date">
                                    <i className="ri-calendar-line" />
                                    <span>{ event.meta._event_start_date }</span>
                                    { event.meta?._event_start_time && (
                                        <span> - { event.meta._event_start_time }</span>
                                    ) }
                                </div>
                            ) }
                            { showLocation && event.meta?._event_location && (
                                <div className="apollo-event-single__location">
                                    <i className="ri-map-pin-line" />
                                    <span>{ event.meta._event_location }</span>
                                </div>
                            ) }
                            <p className="apollo-event-single__preview-note">
                                { __( 'Full event details will be displayed on the frontend.', 'apollo-events-manager' ) }
                            </p>
                        </div>
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
    icon: <EventSingleIcon />,
    edit: Edit,
    save: () => null,
} );
