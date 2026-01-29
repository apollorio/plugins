/**
 * Apollo User Profile Block
 *
 * Displays user profile with avatar, info, stats, and actions.
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const {
    PanelBody,
    SelectControl,
    ToggleControl,
    RangeControl,
    ComboboxControl,
    Spinner,
    Placeholder,
} = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;

/**
 * Avatar sizes in pixels.
 */
const AVATAR_SIZES = {
    small: 48,
    medium: 80,
    large: 128,
    xlarge: 180,
};

/**
 * Layout options for the profile.
 */
const LAYOUT_OPTIONS = [
    { label: __( 'Card', 'apollo-social' ), value: 'card' },
    { label: __( 'Compacto', 'apollo-social' ), value: 'compact' },
    { label: __( 'Hero', 'apollo-social' ), value: 'hero' },
    { label: __( 'Minimal', 'apollo-social' ), value: 'minimal' },
];

/**
 * Avatar size options.
 */
const AVATAR_SIZE_OPTIONS = [
    { label: __( 'Pequeno (48px)', 'apollo-social' ), value: 'small' },
    { label: __( 'Médio (80px)', 'apollo-social' ), value: 'medium' },
    { label: __( 'Grande (128px)', 'apollo-social' ), value: 'large' },
    { label: __( 'Extra Grande (180px)', 'apollo-social' ), value: 'xlarge' },
];

/**
 * Mock user data for preview.
 */
const MOCK_USER = {
    id: 1,
    name: 'DJ Exemplo',
    bio: 'Produtor musical e DJ apaixonado por música eletrônica. Tocando nos melhores eventos desde 2015.',
    location: 'São Paulo, SP',
    website: 'https://exemplo.com',
    avatarUrl: '',
    coverUrl: '',
    stats: {
        posts: 42,
        followers: 1250,
        following: 180,
    },
    socialLinks: {
        instagram: 'https://instagram.com/exemplo',
        twitter: 'https://twitter.com/exemplo',
        soundcloud: 'https://soundcloud.com/exemplo',
    },
    badges: ['Verificado', 'DJ Residente'],
    joinDate: '2021-03-15',
};

/**
 * User search hook.
 *
 * @param {string} search - Search term.
 * @returns {Object} Users and loading state.
 */
const useUserSearch = ( search ) => {
    const [ users, setUsers ] = useState( [] );
    const [ loading, setLoading ] = useState( false );

    useEffect( () => {
        if ( ! search || search.length < 2 ) {
            setUsers( [] );
            return;
        }

        setLoading( true );

        apiFetch( {
            path: `/wp/v2/users?search=${ encodeURIComponent( search ) }&per_page=10`,
        } )
            .then( ( results ) => {
                setUsers(
                    results.map( ( user ) => ( {
                        value: user.id,
                        label: user.name,
                    } ) )
                );
            } )
            .catch( () => setUsers( [] ) )
            .finally( () => setLoading( false ) );
    }, [ search ] );

    return { users, loading };
};

/**
 * Profile preview component.
 *
 * @param {Object} props - Component props.
 * @returns {JSX.Element} Profile preview.
 */
const ProfilePreview = ( { attributes } ) => {
    const {
        layout,
        showAvatar,
        avatarSize,
        showName,
        showBio,
        showLocation,
        showWebsite,
        showSocialLinks,
        showStats,
        showFollowButton,
        showMessageButton,
        showCover,
        coverHeight,
        showBadges,
        showJoinDate,
    } = attributes;

    const avatarPx = AVATAR_SIZES[ avatarSize ] || 128;
    const user = MOCK_USER;

    // Minimal layout.
    if ( layout === 'minimal' ) {
        return (
            <div className="apollo-user-profile apollo-user-profile--minimal">
                { showAvatar && (
                    <div
                        className="apollo-user-profile__avatar"
                        style={ { width: avatarPx, height: avatarPx } }
                    >
                        <div
                            className="apollo-user-profile__avatar-placeholder"
                            style={ {
                                width: avatarPx,
                                height: avatarPx,
                                borderRadius: '50%',
                                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#fff',
                                fontSize: avatarPx * 0.4,
                            } }
                        >
                            <i className="ri-user-fill"></i>
                        </div>
                    </div>
                ) }
                <div className="apollo-user-profile__info">
                    { showName && <h3 className="apollo-user-profile__name">{ user.name }</h3> }
                    { showFollowButton && (
                        <button className="apollo-btn apollo-btn--sm apollo-btn--primary">
                            <i className="ri-user-add-line"></i>
                            { __( 'Seguir', 'apollo-social' ) }
                        </button>
                    ) }
                </div>
            </div>
        );
    }

    // Compact layout.
    if ( layout === 'compact' ) {
        return (
            <div className="apollo-user-profile apollo-user-profile--compact">
                { showAvatar && (
                    <div
                        className="apollo-user-profile__avatar"
                        style={ { width: avatarPx, height: avatarPx } }
                    >
                        <div
                            style={ {
                                width: avatarPx,
                                height: avatarPx,
                                borderRadius: '50%',
                                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#fff',
                                fontSize: avatarPx * 0.4,
                            } }
                        >
                            <i className="ri-user-fill"></i>
                        </div>
                    </div>
                ) }
                <div className="apollo-user-profile__content">
                    { showName && <h3 className="apollo-user-profile__name">{ user.name }</h3> }
                    { showBio && (
                        <p className="apollo-user-profile__bio" style={ { fontSize: '0.875rem', margin: '0.25rem 0' } }>
                            { user.bio.substring( 0, 80 ) }...
                        </p>
                    ) }
                    { showStats && (
                        <div className="apollo-user-profile__stats-row" style={ { display: 'flex', gap: '1rem', fontSize: '0.75rem', color: '#64748b' } }>
                            <span><strong>{ user.stats.followers }</strong> seguidores</span>
                            <span><strong>{ user.stats.posts }</strong> posts</span>
                        </div>
                    ) }
                </div>
                { showFollowButton && (
                    <button className="apollo-btn apollo-btn--sm apollo-btn--outline">
                        { __( 'Seguir', 'apollo-social' ) }
                    </button>
                ) }
            </div>
        );
    }

    // Hero layout.
    if ( layout === 'hero' ) {
        return (
            <div className="apollo-user-profile apollo-user-profile--hero">
                { showCover && (
                    <div
                        className="apollo-user-profile__cover"
                        style={ {
                            height: coverHeight,
                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                        } }
                    />
                ) }
                <div className="apollo-user-profile__hero-content" style={ { padding: '0 2rem', marginTop: showCover ? -avatarPx/2 : 0 } }>
                    { showAvatar && (
                        <div
                            style={ {
                                width: avatarPx,
                                height: avatarPx,
                                borderRadius: '50%',
                                background: 'linear-gradient(135deg, #764ba2 0%, #667eea 100%)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: '#fff',
                                fontSize: avatarPx * 0.35,
                                border: '4px solid #fff',
                                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                            } }
                        >
                            <i className="ri-user-fill"></i>
                        </div>
                    ) }
                    <div className="apollo-user-profile__hero-info" style={ { marginTop: '1rem', textAlign: 'center' } }>
                        { showName && <h2 className="apollo-user-profile__name" style={ { fontSize: '1.75rem', margin: 0 } }>{ user.name }</h2> }
                        { showBadges && user.badges.length > 0 && (
                            <div style={ { display: 'flex', gap: '0.5rem', justifyContent: 'center', marginTop: '0.5rem' } }>
                                { user.badges.map( ( badge, i ) => (
                                    <span key={ i } style={ { background: '#6366f1', color: '#fff', padding: '0.125rem 0.5rem', borderRadius: '9999px', fontSize: '0.75rem' } }>
                                        { badge }
                                    </span>
                                ) ) }
                            </div>
                        ) }
                        { showLocation && (
                            <p style={ { color: '#64748b', margin: '0.5rem 0', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.25rem' } }>
                                <i className="ri-map-pin-line"></i> { user.location }
                            </p>
                        ) }
                        { showBio && <p style={ { maxWidth: 600, margin: '1rem auto' } }>{ user.bio }</p> }
                        { showStats && (
                            <div style={ { display: 'flex', gap: '2rem', justifyContent: 'center', margin: '1.5rem 0' } }>
                                <div style={ { textAlign: 'center' } }>
                                    <strong style={ { fontSize: '1.5rem' } }>{ user.stats.posts }</strong>
                                    <div style={ { color: '#64748b', fontSize: '0.875rem' } }>Posts</div>
                                </div>
                                <div style={ { textAlign: 'center' } }>
                                    <strong style={ { fontSize: '1.5rem' } }>{ user.stats.followers.toLocaleString() }</strong>
                                    <div style={ { color: '#64748b', fontSize: '0.875rem' } }>Seguidores</div>
                                </div>
                                <div style={ { textAlign: 'center' } }>
                                    <strong style={ { fontSize: '1.5rem' } }>{ user.stats.following }</strong>
                                    <div style={ { color: '#64748b', fontSize: '0.875rem' } }>Seguindo</div>
                                </div>
                            </div>
                        ) }
                        <div style={ { display: 'flex', gap: '0.5rem', justifyContent: 'center' } }>
                            { showFollowButton && (
                                <button className="apollo-btn apollo-btn--primary">
                                    <i className="ri-user-add-line"></i> { __( 'Seguir', 'apollo-social' ) }
                                </button>
                            ) }
                            { showMessageButton && (
                                <button className="apollo-btn apollo-btn--outline">
                                    <i className="ri-message-3-line"></i> { __( 'Mensagem', 'apollo-social' ) }
                                </button>
                            ) }
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Default card layout.
    return (
        <div className="apollo-user-profile apollo-user-profile--card" style={ { background: '#fff', borderRadius: '12px', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } }>
            { showCover && (
                <div
                    className="apollo-user-profile__cover"
                    style={ {
                        height: coverHeight * 0.6,
                        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    } }
                />
            ) }
            <div className="apollo-user-profile__card-body" style={ { padding: '1.5rem', marginTop: showCover ? -avatarPx/2 : 0 } }>
                { showAvatar && (
                    <div
                        style={ {
                            width: avatarPx,
                            height: avatarPx,
                            borderRadius: '50%',
                            background: 'linear-gradient(135deg, #764ba2 0%, #667eea 100%)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#fff',
                            fontSize: avatarPx * 0.35,
                            border: '3px solid #fff',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                        } }
                    >
                        <i className="ri-user-fill"></i>
                    </div>
                ) }
                <div style={ { marginTop: '1rem' } }>
                    { showName && <h3 className="apollo-user-profile__name" style={ { margin: 0, fontSize: '1.25rem' } }>{ user.name }</h3> }
                    { showBadges && user.badges.length > 0 && (
                        <div style={ { display: 'flex', gap: '0.375rem', marginTop: '0.5rem' } }>
                            { user.badges.map( ( badge, i ) => (
                                <span key={ i } style={ { background: '#e0e7ff', color: '#4f46e5', padding: '0.125rem 0.375rem', borderRadius: '9999px', fontSize: '0.7rem' } }>
                                    { badge }
                                </span>
                            ) ) }
                        </div>
                    ) }
                    { showLocation && (
                        <p style={ { color: '#64748b', margin: '0.375rem 0', fontSize: '0.875rem', display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
                            <i className="ri-map-pin-line"></i> { user.location }
                        </p>
                    ) }
                    { showBio && <p style={ { fontSize: '0.875rem', margin: '0.5rem 0', color: '#475569' } }>{ user.bio }</p> }
                    { showStats && (
                        <div style={ { display: 'flex', gap: '1rem', margin: '1rem 0', fontSize: '0.875rem' } }>
                            <span><strong>{ user.stats.posts }</strong> posts</span>
                            <span><strong>{ user.stats.followers.toLocaleString() }</strong> seguidores</span>
                        </div>
                    ) }
                    { showJoinDate && (
                        <p style={ { fontSize: '0.75rem', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
                            <i className="ri-calendar-line"></i> { __( 'Membro desde', 'apollo-social' ) } Mar 2021
                        </p>
                    ) }
                    { showSocialLinks && (
                        <div style={ { display: 'flex', gap: '0.5rem', margin: '1rem 0' } }>
                            <a href="#" style={ { color: '#e4405f' } }><i className="ri-instagram-line ri-lg"></i></a>
                            <a href="#" style={ { color: '#1da1f2' } }><i className="ri-twitter-x-line ri-lg"></i></a>
                            <a href="#" style={ { color: '#ff5500' } }><i className="ri-soundcloud-line ri-lg"></i></a>
                        </div>
                    ) }
                    <div style={ { display: 'flex', gap: '0.5rem' } }>
                        { showFollowButton && (
                            <button className="apollo-btn apollo-btn--sm apollo-btn--primary" style={ { flex: 1 } }>
                                <i className="ri-user-add-line"></i> { __( 'Seguir', 'apollo-social' ) }
                            </button>
                        ) }
                        { showMessageButton && (
                            <button className="apollo-btn apollo-btn--sm apollo-btn--outline">
                                <i className="ri-message-3-line"></i>
                            </button>
                        ) }
                    </div>
                </div>
            </div>
        </div>
    );
};

/**
 * Edit component.
 *
 * @param {Object} props - Block props.
 * @returns {JSX.Element} Editor interface.
 */
const Edit = ( { attributes, setAttributes } ) => {
    const blockProps = useBlockProps();
    const [ searchTerm, setSearchTerm ] = useState( '' );
    const { users, loading } = useUserSearch( searchTerm );

    const {
        userId,
        layout,
        showAvatar,
        avatarSize,
        showName,
        showBio,
        showLocation,
        showWebsite,
        showSocialLinks,
        showStats,
        showFollowButton,
        showMessageButton,
        showRecentPosts,
        recentPostsCount,
        showCover,
        coverHeight,
        showBadges,
        showJoinDate,
        useCurrentUser,
    } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Configurações do Perfil', 'apollo-social' ) }>
                    <ToggleControl
                        label={ __( 'Usar Usuário Atual', 'apollo-social' ) }
                        help={ __( 'Exibir o perfil do usuário logado', 'apollo-social' ) }
                        checked={ useCurrentUser }
                        onChange={ ( value ) => setAttributes( { useCurrentUser: value } ) }
                    />
                    { ! useCurrentUser && (
                        <ComboboxControl
                            label={ __( 'Selecionar Usuário', 'apollo-social' ) }
                            value={ userId }
                            options={ users }
                            onChange={ ( value ) => setAttributes( { userId: parseInt( value, 10 ) } ) }
                            onFilterValueChange={ setSearchTerm }
                            help={ __( 'Digite para buscar usuários', 'apollo-social' ) }
                        />
                    ) }
                    <SelectControl
                        label={ __( 'Layout', 'apollo-social' ) }
                        value={ layout }
                        options={ LAYOUT_OPTIONS }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Avatar', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Avatar', 'apollo-social' ) }
                        checked={ showAvatar }
                        onChange={ ( value ) => setAttributes( { showAvatar: value } ) }
                    />
                    { showAvatar && (
                        <SelectControl
                            label={ __( 'Tamanho do Avatar', 'apollo-social' ) }
                            value={ avatarSize }
                            options={ AVATAR_SIZE_OPTIONS }
                            onChange={ ( value ) => setAttributes( { avatarSize: value } ) }
                        />
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Informações', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Nome', 'apollo-social' ) }
                        checked={ showName }
                        onChange={ ( value ) => setAttributes( { showName: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Bio', 'apollo-social' ) }
                        checked={ showBio }
                        onChange={ ( value ) => setAttributes( { showBio: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Localização', 'apollo-social' ) }
                        checked={ showLocation }
                        onChange={ ( value ) => setAttributes( { showLocation: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Website', 'apollo-social' ) }
                        checked={ showWebsite }
                        onChange={ ( value ) => setAttributes( { showWebsite: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Badges', 'apollo-social' ) }
                        checked={ showBadges }
                        onChange={ ( value ) => setAttributes( { showBadges: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Data de Entrada', 'apollo-social' ) }
                        checked={ showJoinDate }
                        onChange={ ( value ) => setAttributes( { showJoinDate: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Social', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Links Sociais', 'apollo-social' ) }
                        checked={ showSocialLinks }
                        onChange={ ( value ) => setAttributes( { showSocialLinks: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Estatísticas', 'apollo-social' ) }
                        checked={ showStats }
                        onChange={ ( value ) => setAttributes( { showStats: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Ações', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Botão Seguir', 'apollo-social' ) }
                        checked={ showFollowButton }
                        onChange={ ( value ) => setAttributes( { showFollowButton: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Botão Mensagem', 'apollo-social' ) }
                        checked={ showMessageButton }
                        onChange={ ( value ) => setAttributes( { showMessageButton: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Capa', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Capa', 'apollo-social' ) }
                        checked={ showCover }
                        onChange={ ( value ) => setAttributes( { showCover: value } ) }
                    />
                    { showCover && (
                        <RangeControl
                            label={ __( 'Altura da Capa', 'apollo-social' ) }
                            value={ coverHeight }
                            onChange={ ( value ) => setAttributes( { coverHeight: value } ) }
                            min={ 100 }
                            max={ 400 }
                        />
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Posts Recentes', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Posts Recentes', 'apollo-social' ) }
                        checked={ showRecentPosts }
                        onChange={ ( value ) => setAttributes( { showRecentPosts: value } ) }
                    />
                    { showRecentPosts && (
                        <RangeControl
                            label={ __( 'Quantidade', 'apollo-social' ) }
                            value={ recentPostsCount }
                            onChange={ ( value ) => setAttributes( { recentPostsCount: value } ) }
                            min={ 1 }
                            max={ 10 }
                        />
                    ) }
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { loading && <Spinner /> }
                <ProfilePreview attributes={ attributes } />
            </div>
        </>
    );
};

/**
 * Register block.
 */
registerBlockType( 'apollo/user-profile', {
    edit: Edit,
} );
