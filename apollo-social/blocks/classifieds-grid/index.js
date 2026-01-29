/**
 * Apollo Classifieds Grid Block
 *
 * Displays a grid of classified listings with filters and sorting.
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
    FormTokenField,
} = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;

/**
 * Layout options.
 */
const LAYOUT_OPTIONS = [
    { label: __( 'Grid', 'apollo-social' ), value: 'grid' },
    { label: __( 'Lista', 'apollo-social' ), value: 'list' },
    { label: __( 'Compacto', 'apollo-social' ), value: 'compact' },
    { label: __( 'Masonry', 'apollo-social' ), value: 'masonry' },
];

/**
 * Column options.
 */
const COLUMN_OPTIONS = [
    { label: __( '2 Colunas', 'apollo-social' ), value: 2 },
    { label: __( '3 Colunas', 'apollo-social' ), value: 3 },
    { label: __( '4 Colunas', 'apollo-social' ), value: 4 },
    { label: __( '5 Colunas', 'apollo-social' ), value: 5 },
];

/**
 * Order options.
 */
const ORDER_OPTIONS = [
    { label: __( 'Mais Recentes', 'apollo-social' ), value: 'date' },
    { label: __( 'Menor Preço', 'apollo-social' ), value: 'price_low' },
    { label: __( 'Maior Preço', 'apollo-social' ), value: 'price_high' },
    { label: __( 'Mais Vistos', 'apollo-social' ), value: 'views' },
    { label: __( 'Título A-Z', 'apollo-social' ), value: 'title' },
];

/**
 * Status options.
 */
const STATUS_OPTIONS = [
    { label: __( 'Ativos', 'apollo-social' ), value: 'active' },
    { label: __( 'Vendidos', 'apollo-social' ), value: 'sold' },
    { label: __( 'Todos', 'apollo-social' ), value: 'all' },
];

/**
 * Aspect ratio options.
 */
const ASPECT_RATIO_OPTIONS = [
    { label: __( 'Quadrado (1:1)', 'apollo-social' ), value: '1/1' },
    { label: __( 'Retrato (4:3)', 'apollo-social' ), value: '4/3' },
    { label: __( 'Wide (16:9)', 'apollo-social' ), value: '16/9' },
    { label: __( 'Vertical (3:4)', 'apollo-social' ), value: '3/4' },
];

/**
 * Mock classified data for preview.
 */
const MOCK_CLASSIFIEDS = [
    {
        id: 1,
        title: 'Pioneer DDJ-1000 - Controladora DJ',
        price: 5500,
        image: '',
        category: 'Equipamentos',
        location: 'São Paulo, SP',
        condition: 'Usado - Excelente',
        date: '2 dias atrás',
        views: 234,
        author: {
            name: 'DJ Carlos',
            avatar: '',
        },
        featured: true,
    },
    {
        id: 2,
        title: 'Par de CDJ-2000NXS2',
        price: 18000,
        image: '',
        category: 'Equipamentos',
        location: 'Rio de Janeiro, RJ',
        condition: 'Usado - Bom',
        date: '5 dias atrás',
        views: 456,
        author: {
            name: 'Studio Music',
            avatar: '',
        },
        featured: false,
    },
    {
        id: 3,
        title: 'Fones Sennheiser HD 25',
        price: 850,
        image: '',
        category: 'Acessórios',
        location: 'Curitiba, PR',
        condition: 'Novo',
        date: '1 semana atrás',
        views: 123,
        author: {
            name: 'Maria DJ',
            avatar: '',
        },
        featured: false,
    },
];

/**
 * Use categories hook.
 *
 * @returns {Object} Categories and loading state.
 */
const useCategories = () => {
    const [ categories, setCategories ] = useState( [] );
    const [ loading, setLoading ] = useState( true );

    useEffect( () => {
        apiFetch( { path: '/wp/v2/classified-category?per_page=100' } )
            .then( ( results ) => {
                setCategories(
                    results.map( ( term ) => ( {
                        id: term.id,
                        name: term.name,
                    } ) )
                );
            } )
            .catch( () => setCategories( [] ) )
            .finally( () => setLoading( false ) );
    }, [] );

    return { categories, loading };
};

/**
 * Classified card preview component.
 *
 * @param {Object} props - Component props.
 * @returns {JSX.Element} Card preview.
 */
const ClassifiedCardPreview = ( { item, attributes } ) => {
    const {
        layout,
        showImage,
        showPrice,
        showCategory,
        showLocation,
        showDate,
        showAuthor,
        showCondition,
        showViews,
        showFavoriteButton,
        showContactButton,
        imageAspectRatio,
    } = attributes;

    // List layout.
    if ( layout === 'list' ) {
        return (
            <div className="apollo-classified-card apollo-classified-card--list" style={ { display: 'flex', background: '#fff', borderRadius: '8px', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } }>
                { showImage && (
                    <div
                        className="apollo-classified-card__image"
                        style={ {
                            width: 200,
                            flexShrink: 0,
                            background: 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#94a3b8',
                        } }
                    >
                        <i className="ri-image-line ri-2x"></i>
                    </div>
                ) }
                <div className="apollo-classified-card__content" style={ { padding: '1rem', flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' } }>
                    <div>
                        <div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' } }>
                            <div>
                                { showCategory && (
                                    <span style={ { fontSize: '0.75rem', color: '#6366f1', fontWeight: 500 } }>{ item.category }</span>
                                ) }
                                <h3 style={ { margin: '0.25rem 0', fontSize: '1rem', fontWeight: 600 } }>{ item.title }</h3>
                            </div>
                            { showPrice && (
                                <span style={ { fontSize: '1.25rem', fontWeight: 700, color: '#059669' } }>
                                    R$ { item.price.toLocaleString() }
                                </span>
                            ) }
                        </div>
                        <div style={ { display: 'flex', gap: '1rem', marginTop: '0.5rem', fontSize: '0.75rem', color: '#64748b' } }>
                            { showLocation && (
                                <span><i className="ri-map-pin-line"></i> { item.location }</span>
                            ) }
                            { showCondition && (
                                <span><i className="ri-checkbox-circle-line"></i> { item.condition }</span>
                            ) }
                            { showDate && (
                                <span><i className="ri-time-line"></i> { item.date }</span>
                            ) }
                        </div>
                    </div>
                    <div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.75rem' } }>
                        { showAuthor && (
                            <div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
                                <div style={ { width: 28, height: 28, borderRadius: '50%', background: '#e2e8f0' } }></div>
                                <span style={ { fontSize: '0.875rem' } }>{ item.author.name }</span>
                            </div>
                        ) }
                        <div style={ { display: 'flex', gap: '0.5rem' } }>
                            { showFavoriteButton && (
                                <button style={ { background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8' } }>
                                    <i className="ri-heart-line"></i>
                                </button>
                            ) }
                            { showContactButton && (
                                <button className="apollo-btn apollo-btn--sm apollo-btn--primary">
                                    <i className="ri-message-3-line"></i> { __( 'Contato', 'apollo-social' ) }
                                </button>
                            ) }
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Compact layout.
    if ( layout === 'compact' ) {
        return (
            <div className="apollo-classified-card apollo-classified-card--compact" style={ { display: 'flex', alignItems: 'center', gap: '0.75rem', padding: '0.75rem', background: '#fff', borderRadius: '6px', boxShadow: '0 1px 2px rgba(0,0,0,0.05)' } }>
                { showImage && (
                    <div
                        style={ {
                            width: 60,
                            height: 60,
                            borderRadius: '6px',
                            background: 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#94a3b8',
                            flexShrink: 0,
                        } }
                    >
                        <i className="ri-image-line"></i>
                    </div>
                ) }
                <div style={ { flex: 1, minWidth: 0 } }>
                    <h4 style={ { margin: 0, fontSize: '0.875rem', fontWeight: 600, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' } }>
                        { item.title }
                    </h4>
                    { showLocation && (
                        <span style={ { fontSize: '0.75rem', color: '#64748b' } }>
                            <i className="ri-map-pin-line"></i> { item.location }
                        </span>
                    ) }
                </div>
                { showPrice && (
                    <span style={ { fontSize: '1rem', fontWeight: 700, color: '#059669', flexShrink: 0 } }>
                        R$ { item.price.toLocaleString() }
                    </span>
                ) }
            </div>
        );
    }

    // Grid/Masonry layout.
    return (
        <div className="apollo-classified-card apollo-classified-card--grid" style={ { background: '#fff', borderRadius: '8px', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } }>
            { showImage && (
                <div
                    className="apollo-classified-card__image"
                    style={ {
                        aspectRatio: imageAspectRatio,
                        background: 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: '#94a3b8',
                        position: 'relative',
                    } }
                >
                    <i className="ri-image-line ri-3x"></i>
                    { item.featured && (
                        <span style={ { position: 'absolute', top: 8, left: 8, background: '#f59e0b', color: '#fff', fontSize: '0.7rem', padding: '0.125rem 0.5rem', borderRadius: '4px', fontWeight: 600 } }>
                            { __( 'Destaque', 'apollo-social' ) }
                        </span>
                    ) }
                    { showFavoriteButton && (
                        <button style={ { position: 'absolute', top: 8, right: 8, background: 'rgba(255,255,255,0.9)', border: 'none', borderRadius: '50%', width: 32, height: 32, cursor: 'pointer', color: '#94a3b8' } }>
                            <i className="ri-heart-line"></i>
                        </button>
                    ) }
                </div>
            ) }
            <div className="apollo-classified-card__content" style={ { padding: '1rem' } }>
                { showCategory && (
                    <span style={ { fontSize: '0.7rem', color: '#6366f1', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em' } }>
                        { item.category }
                    </span>
                ) }
                <h3 style={ { margin: '0.25rem 0', fontSize: '0.9375rem', fontWeight: 600, lineHeight: 1.3 } }>
                    { item.title }
                </h3>
                { showPrice && (
                    <p style={ { margin: '0.5rem 0', fontSize: '1.125rem', fontWeight: 700, color: '#059669' } }>
                        R$ { item.price.toLocaleString() }
                    </p>
                ) }
                <div style={ { display: 'flex', flexWrap: 'wrap', gap: '0.5rem', marginTop: '0.5rem', fontSize: '0.75rem', color: '#64748b' } }>
                    { showLocation && (
                        <span><i className="ri-map-pin-line"></i> { item.location }</span>
                    ) }
                    { showCondition && (
                        <span style={ { background: '#e2e8f0', padding: '0.125rem 0.375rem', borderRadius: '4px' } }>
                            { item.condition }
                        </span>
                    ) }
                </div>
                <div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.75rem', paddingTop: '0.75rem', borderTop: '1px solid #f1f5f9' } }>
                    { showAuthor && (
                        <div style={ { display: 'flex', alignItems: 'center', gap: '0.375rem' } }>
                            <div style={ { width: 24, height: 24, borderRadius: '50%', background: '#e2e8f0' } }></div>
                            <span style={ { fontSize: '0.75rem', color: '#64748b' } }>{ item.author.name }</span>
                        </div>
                    ) }
                    { showViews && (
                        <span style={ { fontSize: '0.75rem', color: '#94a3b8' } }>
                            <i className="ri-eye-line"></i> { item.views }
                        </span>
                    ) }
                </div>
                { showContactButton && (
                    <button className="apollo-btn apollo-btn--primary" style={ { width: '100%', marginTop: '0.75rem', padding: '0.5rem', fontSize: '0.875rem' } }>
                        <i className="ri-message-3-line"></i> { __( 'Entrar em Contato', 'apollo-social' ) }
                    </button>
                ) }
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
    const { categories, loading: loadingCategories } = useCategories();

    const {
        layout,
        columns,
        limit,
        category,
        orderBy,
        status,
        showImage,
        showPrice,
        showCategory,
        showLocation,
        showDate,
        showAuthor,
        showCondition,
        showViews,
        showFavoriteButton,
        showContactButton,
        showFilters,
        showSearch,
        showPagination,
        imageAspectRatio,
        featured,
        currentUserOnly,
    } = attributes;

    // Get selected category names for FormTokenField.
    const selectedCategoryNames = categories
        .filter( ( cat ) => category.includes( cat.id ) )
        .map( ( cat ) => cat.name );

    const handleCategoryChange = ( tokens ) => {
        const newIds = tokens
            .map( ( name ) => categories.find( ( cat ) => cat.name === name )?.id )
            .filter( Boolean );
        setAttributes( { category: newIds } );
    };

    // Determine grid styles.
    const gridStyle = {
        display: layout === 'compact' ? 'flex' : 'grid',
        flexDirection: layout === 'compact' ? 'column' : undefined,
        gridTemplateColumns: layout !== 'compact' && layout !== 'list' ? `repeat(${ columns }, 1fr)` : '1fr',
        gap: '1rem',
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Layout', 'apollo-social' ) }>
                    <SelectControl
                        label={ __( 'Exibição', 'apollo-social' ) }
                        value={ layout }
                        options={ LAYOUT_OPTIONS }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />
                    { ( layout === 'grid' || layout === 'masonry' ) && (
                        <SelectControl
                            label={ __( 'Colunas', 'apollo-social' ) }
                            value={ columns }
                            options={ COLUMN_OPTIONS }
                            onChange={ ( value ) => setAttributes( { columns: parseInt( value, 10 ) } ) }
                        />
                    ) }
                    <RangeControl
                        label={ __( 'Quantidade', 'apollo-social' ) }
                        value={ limit }
                        onChange={ ( value ) => setAttributes( { limit: value } ) }
                        min={ 1 }
                        max={ 24 }
                    />
                    { layout !== 'compact' && (
                        <SelectControl
                            label={ __( 'Proporção da Imagem', 'apollo-social' ) }
                            value={ imageAspectRatio }
                            options={ ASPECT_RATIO_OPTIONS }
                            onChange={ ( value ) => setAttributes( { imageAspectRatio: value } ) }
                        />
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Filtros', 'apollo-social' ) } initialOpen={ false }>
                    <FormTokenField
                        label={ __( 'Categorias', 'apollo-social' ) }
                        value={ selectedCategoryNames }
                        suggestions={ categories.map( ( cat ) => cat.name ) }
                        onChange={ handleCategoryChange }
                    />
                    <SelectControl
                        label={ __( 'Ordenar por', 'apollo-social' ) }
                        value={ orderBy }
                        options={ ORDER_OPTIONS }
                        onChange={ ( value ) => setAttributes( { orderBy: value } ) }
                    />
                    <SelectControl
                        label={ __( 'Status', 'apollo-social' ) }
                        value={ status }
                        options={ STATUS_OPTIONS }
                        onChange={ ( value ) => setAttributes( { status: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Apenas Destaques', 'apollo-social' ) }
                        checked={ featured }
                        onChange={ ( value ) => setAttributes( { featured: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Apenas do Usuário Atual', 'apollo-social' ) }
                        checked={ currentUserOnly }
                        onChange={ ( value ) => setAttributes( { currentUserOnly: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Exibição', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Imagem', 'apollo-social' ) }
                        checked={ showImage }
                        onChange={ ( value ) => setAttributes( { showImage: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Preço', 'apollo-social' ) }
                        checked={ showPrice }
                        onChange={ ( value ) => setAttributes( { showPrice: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Categoria', 'apollo-social' ) }
                        checked={ showCategory }
                        onChange={ ( value ) => setAttributes( { showCategory: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Localização', 'apollo-social' ) }
                        checked={ showLocation }
                        onChange={ ( value ) => setAttributes( { showLocation: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Data', 'apollo-social' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Autor', 'apollo-social' ) }
                        checked={ showAuthor }
                        onChange={ ( value ) => setAttributes( { showAuthor: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Condição', 'apollo-social' ) }
                        checked={ showCondition }
                        onChange={ ( value ) => setAttributes( { showCondition: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Visualizações', 'apollo-social' ) }
                        checked={ showViews }
                        onChange={ ( value ) => setAttributes( { showViews: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Ações', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Botão Favoritar', 'apollo-social' ) }
                        checked={ showFavoriteButton }
                        onChange={ ( value ) => setAttributes( { showFavoriteButton: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Botão Contato', 'apollo-social' ) }
                        checked={ showContactButton }
                        onChange={ ( value ) => setAttributes( { showContactButton: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Interface', 'apollo-social' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Mostrar Filtros', 'apollo-social' ) }
                        checked={ showFilters }
                        onChange={ ( value ) => setAttributes( { showFilters: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Busca', 'apollo-social' ) }
                        checked={ showSearch }
                        onChange={ ( value ) => setAttributes( { showSearch: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Mostrar Paginação', 'apollo-social' ) }
                        checked={ showPagination }
                        onChange={ ( value ) => setAttributes( { showPagination: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <div className="apollo-classifieds-grid-preview">
                    { ( showSearch || showFilters ) && (
                        <div className="apollo-classifieds-toolbar" style={ { display: 'flex', gap: '1rem', marginBottom: '1rem', flexWrap: 'wrap' } }>
                            { showSearch && (
                                <div style={ { flex: 1, minWidth: 200 } }>
                                    <input
                                        type="text"
                                        placeholder={ __( 'Buscar classificados...', 'apollo-social' ) }
                                        style={ { width: '100%', padding: '0.5rem 1rem', border: '1px solid #e2e8f0', borderRadius: '8px' } }
                                        disabled
                                    />
                                </div>
                            ) }
                            { showFilters && (
                                <div style={ { display: 'flex', gap: '0.5rem' } }>
                                    <select style={ { padding: '0.5rem', border: '1px solid #e2e8f0', borderRadius: '6px' } } disabled>
                                        <option>{ __( 'Categoria', 'apollo-social' ) }</option>
                                    </select>
                                    <select style={ { padding: '0.5rem', border: '1px solid #e2e8f0', borderRadius: '6px' } } disabled>
                                        <option>{ __( 'Ordenar', 'apollo-social' ) }</option>
                                    </select>
                                </div>
                            ) }
                        </div>
                    ) }

                    <div style={ gridStyle }>
                        { MOCK_CLASSIFIEDS.slice( 0, Math.min( limit, 3 ) ).map( ( item ) => (
                            <ClassifiedCardPreview
                                key={ item.id }
                                item={ item }
                                attributes={ attributes }
                            />
                        ) ) }
                    </div>

                    { showPagination && (
                        <div style={ { display: 'flex', justifyContent: 'center', gap: '0.25rem', marginTop: '1.5rem' } }>
                            <button style={ { padding: '0.5rem 0.75rem', border: '1px solid #e2e8f0', borderRadius: '6px', background: '#6366f1', color: '#fff' } }>1</button>
                            <button style={ { padding: '0.5rem 0.75rem', border: '1px solid #e2e8f0', borderRadius: '6px', background: '#fff' } }>2</button>
                            <button style={ { padding: '0.5rem 0.75rem', border: '1px solid #e2e8f0', borderRadius: '6px', background: '#fff' } }>3</button>
                        </div>
                    ) }
                </div>
            </div>
        </>
    );
};

/**
 * Register block.
 */
registerBlockType( 'apollo/classifieds-grid', {
    edit: Edit,
} );
