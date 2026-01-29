/**
 * Apollo Blocks - Shared Editor Utilities
 *
 * Shared components, hooks, and utilities for Apollo Gutenberg blocks.
 *
 * @package Apollo_Core
 * @subpackage Blocks
 * @since 2.0.0
 */

import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Apollo block icon component.
 *
 * @param {Object} props Component props.
 * @param {string} props.icon Remix icon class name.
 * @return {JSX.Element} Icon element.
 */
export const ApolloIcon = ( { icon } ) => {
    return (
        <span
            className={ `apollo-block-icon ri-${ icon }` }
            style={ {
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: 24,
                height: 24,
                fontSize: 18,
            } }
        />
    );
};

/**
 * Apollo block wrapper component.
 *
 * @param {Object}      props          Component props.
 * @param {JSX.Element} props.children Child elements.
 * @param {boolean}     props.loading  Loading state.
 * @param {string}      props.error    Error message.
 * @return {JSX.Element} Wrapper element.
 */
export const ApolloBlockWrapper = ( { children, loading = false, error = null } ) => {
    if ( loading ) {
        return (
            <div className="apollo-block-preview apollo-block-preview--loading">
                <span className="components-spinner" />
            </div>
        );
    }

    if ( error ) {
        return (
            <div className="apollo-block-preview apollo-block-preview--error">
                <p>{ error }</p>
            </div>
        );
    }

    return (
        <div className="apollo-block-preview">
            { children }
        </div>
    );
};

/**
 * Custom hook to fetch posts.
 *
 * @param {string} postType  Post type to fetch.
 * @param {Object} queryArgs Query arguments.
 * @return {Object} Posts data and loading state.
 */
export const useApolloQuery = ( postType, queryArgs = {} ) => {
    const [ data, setData ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    useEffect( () => {
        setLoading( true );
        setError( null );

        const path = `/wp/v2/${ postType }`;
        const params = new URLSearchParams( {
            per_page: queryArgs.limit || 10,
            orderby: queryArgs.orderby || 'date',
            order: queryArgs.order || 'desc',
            ...queryArgs,
        } );

        apiFetch( { path: `${ path }?${ params.toString() }` } )
            .then( ( response ) => {
                setData( response );
                setLoading( false );
            } )
            .catch( ( err ) => {
                setError( err.message || __( 'Error loading data.', 'apollo-core' ) );
                setLoading( false );
            } );
    }, [ postType, JSON.stringify( queryArgs ) ] );

    return { data, loading, error };
};

/**
 * Custom hook to get taxonomy terms.
 *
 * @param {string} taxonomy Taxonomy slug.
 * @return {Array} Terms array.
 */
export const useTaxonomyTerms = ( taxonomy ) => {
    return useSelect(
        ( select ) => {
            const { getEntityRecords } = select( 'core' );
            return getEntityRecords( 'taxonomy', taxonomy, {
                per_page: -1,
                hide_empty: false,
            } ) || [];
        },
        [ taxonomy ]
    );
};

/**
 * Layout options for grid blocks.
 */
export const LAYOUT_OPTIONS = [
    { value: 'grid', label: __( 'Grid', 'apollo-core' ) },
    { value: 'list', label: __( 'List', 'apollo-core' ) },
    { value: 'carousel', label: __( 'Carousel', 'apollo-core' ) },
];

/**
 * Column options for grid layouts.
 */
export const COLUMN_OPTIONS = [
    { value: 1, label: '1' },
    { value: 2, label: '2' },
    { value: 3, label: '3' },
    { value: 4, label: '4' },
];

/**
 * Order options.
 */
export const ORDER_OPTIONS = [
    { value: 'asc', label: __( 'Ascending', 'apollo-core' ) },
    { value: 'desc', label: __( 'Descending', 'apollo-core' ) },
];

/**
 * Order by options for events.
 */
export const EVENT_ORDERBY_OPTIONS = [
    { value: 'date', label: __( 'Publish Date', 'apollo-core' ) },
    { value: 'title', label: __( 'Title', 'apollo-core' ) },
    { value: 'meta_value', label: __( 'Event Date', 'apollo-core' ) },
    { value: 'rand', label: __( 'Random', 'apollo-core' ) },
];

/**
 * Shared block attributes for grid layouts.
 */
export const GRID_ATTRIBUTES = {
    layout: {
        type: 'string',
        default: 'grid',
    },
    columns: {
        type: 'number',
        default: 3,
    },
    limit: {
        type: 'number',
        default: 6,
    },
    orderby: {
        type: 'string',
        default: 'date',
    },
    order: {
        type: 'string',
        default: 'desc',
    },
    showImage: {
        type: 'boolean',
        default: true,
    },
    showExcerpt: {
        type: 'boolean',
        default: true,
    },
    showPagination: {
        type: 'boolean',
        default: false,
    },
};

/**
 * Console logging for development.
 */
if ( process.env.NODE_ENV === 'development' ) {
    console.log( 'Apollo Blocks Editor utilities loaded.' );
}
