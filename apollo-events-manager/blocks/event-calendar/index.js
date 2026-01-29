/**
 * Apollo Event Calendar Block - Editor Interface
 *
 * Gutenberg block for displaying events in a calendar view.
 *
 * @package Apollo_Events_Manager
 * @subpackage Blocks
 * @since 2.0.0
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
    PanelBody,
    SelectControl,
    ToggleControl
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Calendar Icon
 */
const CalendarIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2" fill="none" />
        <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" strokeWidth="2" />
        <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
);

/**
 * Calendar Preview Component
 */
const CalendarPreview = ( { showWeekdays, showEventCount } ) => {
    const weekdays = [ 'Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb' ];
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    // Get first day of month and number of days.
    const firstDay = new Date( currentYear, currentMonth, 1 ).getDay();
    const daysInMonth = new Date( currentYear, currentMonth + 1, 0 ).getDate();

    // Generate calendar days.
    const days = [];
    for ( let i = 0; i < firstDay; i++ ) {
        days.push( { day: '', empty: true } );
    }
    for ( let i = 1; i <= daysInMonth; i++ ) {
        days.push( {
            day: i,
            isToday: i === today.getDate(),
            hasEvents: [ 5, 12, 15, 20, 25 ].includes( i ), // Mock events.
            eventCount: [ 5, 15, 25 ].includes( i ) ? 2 : 1,
        } );
    }

    const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];

    return (
        <div className="apollo-event-calendar">
            <div className="apollo-event-calendar__header">
                <button className="apollo-event-calendar__nav-btn" disabled>
                    <i className="ri-arrow-left-s-line" />
                </button>
                <h3 className="apollo-event-calendar__title">
                    { monthNames[ currentMonth ] } { currentYear }
                </h3>
                <button className="apollo-event-calendar__nav-btn" disabled>
                    <i className="ri-arrow-right-s-line" />
                </button>
            </div>

            { showWeekdays && (
                <div className="apollo-event-calendar__weekdays">
                    { weekdays.map( ( day, index ) => (
                        <div key={ index } className="apollo-event-calendar__weekday">
                            { day }
                        </div>
                    ) ) }
                </div>
            ) }

            <div className="apollo-calendar-grid">
                { days.map( ( dayInfo, index ) => (
                    <div
                        key={ index }
                        className={ `apollo-calendar-day ${
                            dayInfo.empty ? 'apollo-calendar-day--empty' : ''
                        } ${
                            dayInfo.isToday ? 'apollo-calendar-day--today' : ''
                        } ${
                            dayInfo.hasEvents ? 'apollo-calendar-day--has-events' : ''
                        }` }
                    >
                        { dayInfo.day && (
                            <>
                                <span className="apollo-calendar-day__number">
                                    { dayInfo.day }
                                </span>
                                { showEventCount && dayInfo.hasEvents && (
                                    <span className="apollo-calendar-day__count">
                                        { dayInfo.eventCount }
                                    </span>
                                ) }
                            </>
                        ) }
                    </div>
                ) ) }
            </div>
        </div>
    );
};

/**
 * Event Calendar Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        category,
        type,
        sounds,
        season,
        showNavigation,
        showWeekdays,
        showEventCount,
        showEventList,
    } = attributes;

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

    const blockProps = useBlockProps( {
        className: 'apollo-event-calendar-block',
    } );

    const categoryOptions = [
        { value: '', label: __( 'All Categories', 'apollo-events-manager' ) },
        ...categories.map( ( cat ) => ( {
            value: cat.id.toString(),
            label: cat.name,
        } ) ),
    ];

    const typeOptions = [
        { value: '', label: __( 'All Types', 'apollo-events-manager' ) },
        ...types.map( ( t ) => ( {
            value: t.id.toString(),
            label: t.name,
        } ) ),
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Filter Settings', 'apollo-events-manager' ) } initialOpen={ true }>
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
                </PanelBody>

                <PanelBody title={ __( 'Display Options', 'apollo-events-manager' ) } initialOpen={ true }>
                    <ToggleControl
                        label={ __( 'Show Navigation', 'apollo-events-manager' ) }
                        checked={ showNavigation }
                        onChange={ ( value ) => setAttributes( { showNavigation: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Weekdays', 'apollo-events-manager' ) }
                        checked={ showWeekdays }
                        onChange={ ( value ) => setAttributes( { showWeekdays: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Event Count', 'apollo-events-manager' ) }
                        checked={ showEventCount }
                        onChange={ ( value ) => setAttributes( { showEventCount: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Event List Below', 'apollo-events-manager' ) }
                        checked={ showEventList }
                        onChange={ ( value ) => setAttributes( { showEventList: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <CalendarPreview
                    showWeekdays={ showWeekdays }
                    showEventCount={ showEventCount }
                />
                { showEventList && (
                    <div className="apollo-event-calendar__events-preview">
                        <p className="apollo-event-calendar__preview-note">
                            { __( 'Event list will appear here when a day is selected.', 'apollo-events-manager' ) }
                        </p>
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
    icon: <CalendarIcon />,
    edit: Edit,
    save: () => null,
} );
