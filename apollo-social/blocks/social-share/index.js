/**
 * Apollo Social Share Block - Editor Interface
 *
 * Gutenberg block for social sharing buttons.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
    CheckboxControl,
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Social Share Icon
 */
const SocialShareIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="18" cy="5" r="3" fill="currentColor" />
        <circle cx="6" cy="12" r="3" fill="currentColor" />
        <circle cx="18" cy="19" r="3" fill="currentColor" />
        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" stroke="currentColor" strokeWidth="2" />
        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" stroke="currentColor" strokeWidth="2" />
    </svg>
);

/**
 * Available networks configuration.
 */
const NETWORKS = {
    facebook: { label: 'Facebook', icon: 'ri-facebook-fill', color: '#1877f2' },
    twitter: { label: 'X (Twitter)', icon: 'ri-twitter-x-fill', color: '#000000' },
    whatsapp: { label: 'WhatsApp', icon: 'ri-whatsapp-fill', color: '#25d366' },
    linkedin: { label: 'LinkedIn', icon: 'ri-linkedin-fill', color: '#0a66c2' },
    telegram: { label: 'Telegram', icon: 'ri-telegram-fill', color: '#0088cc' },
    pinterest: { label: 'Pinterest', icon: 'ri-pinterest-fill', color: '#e60023' },
    email: { label: 'Email', icon: 'ri-mail-fill', color: '#64748b' },
    copy: { label: __( 'Copy Link', 'apollo-social' ), icon: 'ri-link', color: '#475569' },
};

/**
 * Share Button Preview Component
 */
const ShareButtonPreview = ( { network, style, size, shape, showLabels } ) => {
    const config = NETWORKS[ network ];
    if ( ! config ) return null;

    const sizeClasses = {
        small: 'apollo-share-button--sm',
        medium: 'apollo-share-button--md',
        large: 'apollo-share-button--lg',
    };

    const shapeClasses = {
        rounded: 'apollo-share-button--rounded',
        circle: 'apollo-share-button--circle',
        square: 'apollo-share-button--square',
    };

    const buttonClass = `apollo-share-button apollo-share-button--${ network } ${ sizeClasses[ size ] || '' } ${ shapeClasses[ shape ] || '' }`;

    return (
        <button
            className={ buttonClass }
            style={ {
                backgroundColor: style === 'colored' ? config.color : undefined,
                color: style === 'colored' ? '#fff' : config.color,
            } }
            disabled
        >
            <i className={ config.icon } />
            { showLabels && <span>{ config.label }</span> }
        </button>
    );
};

/**
 * Social Share Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        networks,
        style,
        size,
        shape,
        showLabels,
        showCounts,
        alignment,
        customUrl,
        customTitle,
    } = attributes;

    const toggleNetwork = ( network ) => {
        const newNetworks = networks.includes( network )
            ? networks.filter( ( n ) => n !== network )
            : [ ...networks, network ];
        setAttributes( { networks: newNetworks } );
    };

    const blockProps = useBlockProps( {
        className: `apollo-social-share-block align-${ alignment }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Networks', 'apollo-social' ) } initialOpen={ true }>
                    { Object.entries( NETWORKS ).map( ( [ key, config ] ) => (
                        <CheckboxControl
                            key={ key }
                            label={ config.label }
                            checked={ networks.includes( key ) }
                            onChange={ () => toggleNetwork( key ) }
                        />
                    ) ) }
                </PanelBody>

                <PanelBody title={ __( 'Style', 'apollo-social' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Button Style', 'apollo-social' ) }
                        value={ style }
                        options={ [
                            { value: 'icons', label: __( 'Icons Only', 'apollo-social' ) },
                            { value: 'colored', label: __( 'Colored Background', 'apollo-social' ) },
                            { value: 'outline', label: __( 'Outline', 'apollo-social' ) },
                            { value: 'minimal', label: __( 'Minimal', 'apollo-social' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { style: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Size', 'apollo-social' ) }
                        value={ size }
                        options={ [
                            { value: 'small', label: __( 'Small', 'apollo-social' ) },
                            { value: 'medium', label: __( 'Medium', 'apollo-social' ) },
                            { value: 'large', label: __( 'Large', 'apollo-social' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { size: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Shape', 'apollo-social' ) }
                        value={ shape }
                        options={ [
                            { value: 'rounded', label: __( 'Rounded', 'apollo-social' ) },
                            { value: 'circle', label: __( 'Circle', 'apollo-social' ) },
                            { value: 'square', label: __( 'Square', 'apollo-social' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { shape: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Alignment', 'apollo-social' ) }
                        value={ alignment }
                        options={ [
                            { value: 'left', label: __( 'Left', 'apollo-social' ) },
                            { value: 'center', label: __( 'Center', 'apollo-social' ) },
                            { value: 'right', label: __( 'Right', 'apollo-social' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { alignment: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Display Options', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Labels', 'apollo-social' ) }
                        checked={ showLabels }
                        onChange={ ( value ) => setAttributes( { showLabels: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Share Counts', 'apollo-social' ) }
                        checked={ showCounts }
                        onChange={ ( value ) => setAttributes( { showCounts: value } ) }
                        help={ __( 'Requires API access to each network.', 'apollo-social' ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Custom Share Content', 'apollo-social' ) } initialOpen={ false }>
                    <TextControl
                        label={ __( 'Custom URL', 'apollo-social' ) }
                        value={ customUrl }
                        onChange={ ( value ) => setAttributes( { customUrl: value } ) }
                        help={ __( 'Leave empty to use current page URL.', 'apollo-social' ) }
                    />
                    <TextControl
                        label={ __( 'Custom Title', 'apollo-social' ) }
                        value={ customTitle }
                        onChange={ ( value ) => setAttributes( { customTitle: value } ) }
                        help={ __( 'Leave empty to use current page title.', 'apollo-social' ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <div
                    className={ `apollo-social-share apollo-social-share--${ style } apollo-social-share--${ size } apollo-social-share--${ shape }` }
                    style={ { justifyContent: alignment === 'center' ? 'center' : alignment === 'right' ? 'flex-end' : 'flex-start' } }
                >
                    { networks.length > 0 ? (
                        networks.map( ( network ) => (
                            <ShareButtonPreview
                                key={ network }
                                network={ network }
                                style={ style }
                                size={ size }
                                shape={ shape }
                                showLabels={ showLabels }
                            />
                        ) )
                    ) : (
                        <p className="apollo-social-share__empty">
                            { __( 'Select networks to display.', 'apollo-social' ) }
                        </p>
                    ) }
                </div>
            </div>
        </>
    );
};

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
    icon: <SocialShareIcon />,
    edit: Edit,
    save: () => null,
} );
