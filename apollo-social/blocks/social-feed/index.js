/**
 * Apollo Social Feed Block - Editor Interface
 *
 * Gutenberg block for displaying a social activity feed.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Social Feed Icon
 */
const SocialFeedIcon = () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="3" width="18" height="4" rx="1" fill="currentColor" />
        <rect x="3" y="10" width="18" height="4" rx="1" fill="currentColor" fillOpacity="0.7" />
        <rect x="3" y="17" width="18" height="4" rx="1" fill="currentColor" fillOpacity="0.4" />
    </svg>
);

/**
 * Social Post Preview Component
 */
const SocialPostPreview = ( { showAuthor, showAvatar, showDate, showLikes, showComments, showShare } ) => {
    return (
        <div className="apollo-social-post-preview">
            { ( showAuthor || showAvatar ) && (
                <div className="apollo-social-post__header">
                    { showAvatar && (
                        <div className="apollo-social-post__avatar">
                            <i className="ri-user-fill" />
                        </div>
                    ) }
                    <div className="apollo-social-post__author-info">
                        { showAuthor && (
                            <span className="apollo-social-post__author">
                                { __( 'Nome do Usuário', 'apollo-social' ) }
                            </span>
                        ) }
                        { showDate && (
                            <span className="apollo-social-post__date">
                                { __( 'há 2 horas', 'apollo-social' ) }
                            </span>
                        ) }
                    </div>
                </div>
            ) }
            <div className="apollo-social-post__content">
                <p>{ __( 'Este é um exemplo de post social. O conteúdo real será carregado dinamicamente.', 'apollo-social' ) }</p>
            </div>
            <div className="apollo-social-post__actions">
                { showLikes && (
                    <button className="apollo-social-post__action">
                        <i className="ri-heart-line" />
                        <span>42</span>
                    </button>
                ) }
                { showComments && (
                    <button className="apollo-social-post__action">
                        <i className="ri-chat-1-line" />
                        <span>12</span>
                    </button>
                ) }
                { showShare && (
                    <button className="apollo-social-post__action">
                        <i className="ri-share-line" />
                    </button>
                ) }
            </div>
        </div>
    );
};

/**
 * Social Feed Edit Component
 */
const Edit = ( { attributes, setAttributes } ) => {
    const {
        layout,
        limit,
        showAuthor,
        showAvatar,
        showDate,
        showLikes,
        showComments,
        showShare,
        showLoadMore,
    } = attributes;

    const blockProps = useBlockProps( {
        className: `apollo-social-feed-block layout-${ layout }`,
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout Settings', 'apollo-social' ) } initialOpen={ true }>
                    <SelectControl
                        label={ __( 'Layout', 'apollo-social' ) }
                        value={ layout }
                        options={ [
                            { value: 'list', label: __( 'List', 'apollo-social' ) },
                            { value: 'grid', label: __( 'Grid', 'apollo-social' ) },
                            { value: 'masonry', label: __( 'Masonry', 'apollo-social' ) },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    <RangeControl
                        label={ __( 'Number of Posts', 'apollo-social' ) }
                        value={ limit }
                        onChange={ ( value ) => setAttributes( { limit: value } ) }
                        min={ 1 }
                        max={ 50 }
                    />
                    <ToggleControl
                        label={ __( 'Show Load More Button', 'apollo-social' ) }
                        checked={ showLoadMore }
                        onChange={ ( value ) => setAttributes( { showLoadMore: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Display Options', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Author Name', 'apollo-social' ) }
                        checked={ showAuthor }
                        onChange={ ( value ) => setAttributes( { showAuthor: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Avatar', 'apollo-social' ) }
                        checked={ showAvatar }
                        onChange={ ( value ) => setAttributes( { showAvatar: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Date', 'apollo-social' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Interactions', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Show Likes', 'apollo-social' ) }
                        checked={ showLikes }
                        onChange={ ( value ) => setAttributes( { showLikes: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Comments', 'apollo-social' ) }
                        checked={ showComments }
                        onChange={ ( value ) => setAttributes( { showComments: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Share Button', 'apollo-social' ) }
                        checked={ showShare }
                        onChange={ ( value ) => setAttributes( { showShare: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <div className="apollo-social-feed">
                    <SocialPostPreview
                        showAuthor={ showAuthor }
                        showAvatar={ showAvatar }
                        showDate={ showDate }
                        showLikes={ showLikes }
                        showComments={ showComments }
                        showShare={ showShare }
                    />
                    <SocialPostPreview
                        showAuthor={ showAuthor }
                        showAvatar={ showAvatar }
                        showDate={ showDate }
                        showLikes={ showLikes }
                        showComments={ showComments }
                        showShare={ showShare }
                    />
                    <p className="apollo-social-feed__preview-note">
                        { __( 'O feed social real será exibido no frontend.', 'apollo-social' ) }
                    </p>
                </div>
            </div>
        </>
    );
};

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
    icon: <SocialFeedIcon />,
    edit: Edit,
    save: () => null,
} );
