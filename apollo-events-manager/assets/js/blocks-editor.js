/**
 * Apollo Events Blocks - Editor JavaScript
 *
 * Gutenberg block registrations for Apollo Events.
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function(wp) {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    const { __ } = wp.i18n;
    const data = window.apolloBlocksData || {};

    // Block Icon
    const apolloIcon = el('svg', {
        width: 24,
        height: 24,
        viewBox: '0 0 24 24',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg'
    },
        el('path', {
            d: 'M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z',
            stroke: 'currentColor',
            strokeWidth: 2,
            strokeLinecap: 'round',
            strokeLinejoin: 'round'
        }),
        el('path', {
            d: 'M16 2V6M8 2V6M3 10H21',
            stroke: 'currentColor',
            strokeWidth: 2,
            strokeLinecap: 'round',
            strokeLinejoin: 'round'
        })
    );

    /**
     * Event List Block
     */
    registerBlockType('apollo-events/event-list', {
        title: data.i18n?.eventList || 'Lista de Eventos',
        description: __('Exibe uma lista de eventos.', 'apollo-events'),
        category: 'apollo-events',
        icon: apolloIcon,
        keywords: ['eventos', 'lista', 'grid'],
        attributes: {
            layout: { type: 'string', default: 'grid' },
            columns: { type: 'number', default: 3 },
            count: { type: 'number', default: 6 },
            showPast: { type: 'boolean', default: false },
            showDate: { type: 'boolean', default: true },
            showLocal: { type: 'boolean', default: true },
            showDJs: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({ className: 'wp-block-apollo-events-event-list columns-' + attributes.columns });

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(SelectControl, {
                        label: __('Layout', 'apollo-events'),
                        value: attributes.layout,
                        options: [
                            { label: 'Grid', value: 'grid' },
                            { label: 'Lista', value: 'list' }
                        ],
                        onChange: (val) => setAttributes({ layout: val })
                    }),
                    el(RangeControl, {
                        label: __('Colunas', 'apollo-events'),
                        value: attributes.columns,
                        min: 1,
                        max: 4,
                        onChange: (val) => setAttributes({ columns: val })
                    }),
                    el(RangeControl, {
                        label: __('Quantidade', 'apollo-events'),
                        value: attributes.count,
                        min: 1,
                        max: 12,
                        onChange: (val) => setAttributes({ count: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar eventos passados', 'apollo-events'),
                        checked: attributes.showPast,
                        onChange: (val) => setAttributes({ showPast: val })
                    })
                ),
                el(PanelBody, { title: __('Exibição', 'apollo-events'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Mostrar data', 'apollo-events'),
                        checked: attributes.showDate,
                        onChange: (val) => setAttributes({ showDate: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar local', 'apollo-events'),
                        checked: attributes.showLocal,
                        onChange: (val) => setAttributes({ showLocal: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar DJs', 'apollo-events'),
                        checked: attributes.showDJs,
                        onChange: (val) => setAttributes({ showDJs: val })
                    })
                )
            );

            // Preview cards
            const cards = [];
            for (let i = 0; i < Math.min(attributes.count, 3); i++) {
                cards.push(
                    el('div', { className: 'apollo-editor-event-card', key: i },
                        el('div', { className: 'apollo-editor-event-card__image' },
                            el('span', { className: 'dashicons dashicons-calendar-alt' })
                        ),
                        el('div', { className: 'apollo-editor-event-card__content' },
                            el('h4', { className: 'apollo-editor-event-card__title' }, 'Evento ' + (i + 1)),
                            attributes.showDate && el('p', { className: 'apollo-editor-event-card__meta' },
                                el('span', { className: 'dashicons dashicons-calendar-alt' }),
                                '25/12/2024 22:00'
                            ),
                            attributes.showLocal && el('p', { className: 'apollo-editor-event-card__meta' },
                                el('span', { className: 'dashicons dashicons-location' }),
                                'Local do Evento'
                            )
                        )
                    )
                );
            }

            return el(Fragment, {},
                inspector,
                el('div', blockProps, cards)
            );
        },
        save: function() {
            return null; // Dynamic block
        }
    });

    /**
     * Single Event Block
     */
    registerBlockType('apollo-events/single-event', {
        title: data.i18n?.singleEvent || 'Evento Único',
        description: __('Exibe um evento específico.', 'apollo-events'),
        category: 'apollo-events',
        icon: apolloIcon,
        keywords: ['evento', 'single', 'destaque'],
        attributes: {
            eventId: { type: 'number', default: 0 },
            layout: { type: 'string', default: 'card' },
            showImage: { type: 'boolean', default: true },
            showDate: { type: 'boolean', default: true },
            showLocal: { type: 'boolean', default: true },
            showButton: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            const eventOptions = [{ label: __('Selecione...', 'apollo-events'), value: 0 }];
            if (data.events) {
                data.events.forEach(event => {
                    eventOptions.push({ label: event.title, value: event.id });
                });
            }

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(SelectControl, {
                        label: __('Evento', 'apollo-events'),
                        value: attributes.eventId,
                        options: eventOptions,
                        onChange: (val) => setAttributes({ eventId: parseInt(val) })
                    }),
                    el(SelectControl, {
                        label: __('Layout', 'apollo-events'),
                        value: attributes.layout,
                        options: [
                            { label: 'Card', value: 'card' },
                            { label: 'Horizontal', value: 'horizontal' },
                            { label: 'Minimal', value: 'minimal' }
                        ],
                        onChange: (val) => setAttributes({ layout: val })
                    })
                ),
                el(PanelBody, { title: __('Exibição', 'apollo-events'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Mostrar imagem', 'apollo-events'),
                        checked: attributes.showImage,
                        onChange: (val) => setAttributes({ showImage: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar data', 'apollo-events'),
                        checked: attributes.showDate,
                        onChange: (val) => setAttributes({ showDate: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar local', 'apollo-events'),
                        checked: attributes.showLocal,
                        onChange: (val) => setAttributes({ showLocal: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar botão', 'apollo-events'),
                        checked: attributes.showButton,
                        onChange: (val) => setAttributes({ showButton: val })
                    })
                )
            );

            const selectedEvent = data.events?.find(e => e.id === attributes.eventId);

            return el(Fragment, {},
                inspector,
                el('div', blockProps,
                    attributes.eventId ?
                        el('div', { className: 'apollo-editor-event-card' },
                            attributes.showImage && el('div', { className: 'apollo-editor-event-card__image' },
                                el('span', { className: 'dashicons dashicons-calendar-alt' })
                            ),
                            el('div', { className: 'apollo-editor-event-card__content' },
                                el('h4', { className: 'apollo-editor-event-card__title' },
                                    selectedEvent?.title || 'Evento Selecionado'
                                ),
                                attributes.showDate && el('p', { className: 'apollo-editor-event-card__meta' },
                                    el('span', { className: 'dashicons dashicons-calendar-alt' }),
                                    '25/12/2024'
                                ),
                                attributes.showLocal && el('p', { className: 'apollo-editor-event-card__meta' },
                                    el('span', { className: 'dashicons dashicons-location' }),
                                    'Local'
                                )
                            )
                        ) :
                        el('div', { className: 'apollo-block-placeholder' },
                            el('span', { className: 'dashicons dashicons-calendar-alt apollo-block-placeholder__icon' }),
                            el('p', { className: 'apollo-block-placeholder__title' }, __('Evento Único', 'apollo-events')),
                            el('p', { className: 'apollo-block-placeholder__description' }, __('Selecione um evento no painel lateral.', 'apollo-events'))
                        )
                )
            );
        },
        save: function() {
            return null;
        }
    });

    /**
     * Countdown Block
     */
    registerBlockType('apollo-events/countdown', {
        title: data.i18n?.countdown || 'Contagem Regressiva',
        description: __('Exibe contagem regressiva para um evento.', 'apollo-events'),
        category: 'apollo-events',
        icon: 'clock',
        keywords: ['countdown', 'timer', 'contagem'],
        attributes: {
            eventId: { type: 'number', default: 0 },
            showTitle: { type: 'boolean', default: true },
            showDays: { type: 'boolean', default: true },
            showHours: { type: 'boolean', default: true },
            showMinutes: { type: 'boolean', default: true },
            showSeconds: { type: 'boolean', default: true },
            style: { type: 'string', default: 'default' }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            const eventOptions = [{ label: __('Próximo evento', 'apollo-events'), value: 0 }];
            if (data.events) {
                data.events.forEach(event => {
                    eventOptions.push({ label: event.title, value: event.id });
                });
            }

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(SelectControl, {
                        label: __('Evento', 'apollo-events'),
                        value: attributes.eventId,
                        options: eventOptions,
                        onChange: (val) => setAttributes({ eventId: parseInt(val) })
                    }),
                    el(SelectControl, {
                        label: __('Estilo', 'apollo-events'),
                        value: attributes.style,
                        options: [
                            { label: 'Padrão', value: 'default' },
                            { label: 'Minimal', value: 'minimal' },
                            { label: 'Dark', value: 'dark' },
                            { label: 'Gradient', value: 'gradient' }
                        ],
                        onChange: (val) => setAttributes({ style: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar título', 'apollo-events'),
                        checked: attributes.showTitle,
                        onChange: (val) => setAttributes({ showTitle: val })
                    })
                ),
                el(PanelBody, { title: __('Unidades', 'apollo-events'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Dias', 'apollo-events'),
                        checked: attributes.showDays,
                        onChange: (val) => setAttributes({ showDays: val })
                    }),
                    el(ToggleControl, {
                        label: __('Horas', 'apollo-events'),
                        checked: attributes.showHours,
                        onChange: (val) => setAttributes({ showHours: val })
                    }),
                    el(ToggleControl, {
                        label: __('Minutos', 'apollo-events'),
                        checked: attributes.showMinutes,
                        onChange: (val) => setAttributes({ showMinutes: val })
                    }),
                    el(ToggleControl, {
                        label: __('Segundos', 'apollo-events'),
                        checked: attributes.showSeconds,
                        onChange: (val) => setAttributes({ showSeconds: val })
                    })
                )
            );

            return el(Fragment, {},
                inspector,
                el('div', blockProps,
                    el('div', { className: 'apollo-editor-countdown' },
                        attributes.showTitle && el('h3', { className: 'apollo-editor-countdown__title' }, 'Próximo Evento'),
                        el('div', { className: 'apollo-editor-countdown__timer' },
                            attributes.showDays && el('div', { className: 'apollo-editor-countdown__unit' },
                                el('span', { className: 'apollo-editor-countdown__value' }, '00'),
                                el('span', { className: 'apollo-editor-countdown__label' }, 'Dias')
                            ),
                            attributes.showHours && el('div', { className: 'apollo-editor-countdown__unit' },
                                el('span', { className: 'apollo-editor-countdown__value' }, '00'),
                                el('span', { className: 'apollo-editor-countdown__label' }, 'Horas')
                            ),
                            attributes.showMinutes && el('div', { className: 'apollo-editor-countdown__unit' },
                                el('span', { className: 'apollo-editor-countdown__value' }, '00'),
                                el('span', { className: 'apollo-editor-countdown__label' }, 'Min')
                            ),
                            attributes.showSeconds && el('div', { className: 'apollo-editor-countdown__unit' },
                                el('span', { className: 'apollo-editor-countdown__value' }, '00'),
                                el('span', { className: 'apollo-editor-countdown__label' }, 'Seg')
                            )
                        )
                    )
                )
            );
        },
        save: function() {
            return null;
        }
    });

    /**
     * Calendar Block
     */
    registerBlockType('apollo-events/calendar', {
        title: data.i18n?.calendar || 'Calendário',
        description: __('Exibe calendário de eventos.', 'apollo-events'),
        category: 'apollo-events',
        icon: 'calendar-alt',
        keywords: ['calendar', 'calendario', 'mês'],
        attributes: {
            month: { type: 'number', default: 0 },
            year: { type: 'number', default: 0 },
            showNav: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(ToggleControl, {
                        label: __('Mostrar navegação', 'apollo-events'),
                        checked: attributes.showNav,
                        onChange: (val) => setAttributes({ showNav: val })
                    })
                )
            );

            const dayHeaders = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
            const days = [];

            for (let i = 0; i < 35; i++) {
                const dayNum = i < 3 ? '' : (i - 2);
                const isToday = dayNum === 15;
                const hasEvent = [5, 12, 20, 25].includes(dayNum);

                days.push(
                    el('div', {
                        className: 'apollo-editor-calendar__day' +
                            (isToday ? ' apollo-editor-calendar__day--today' : '') +
                            (hasEvent ? ' apollo-editor-calendar__day--event' : ''),
                        key: i
                    }, dayNum || '')
                );
            }

            return el(Fragment, {},
                inspector,
                el('div', blockProps,
                    el('div', { className: 'apollo-editor-calendar' },
                        attributes.showNav && el('div', { className: 'apollo-editor-calendar__header' },
                            el('button', { className: 'apollo-editor-calendar__nav' }, '◀'),
                            el('span', { className: 'apollo-editor-calendar__month' }, 'Dezembro 2024'),
                            el('button', { className: 'apollo-editor-calendar__nav' }, '▶')
                        ),
                        el('div', { className: 'apollo-editor-calendar__grid' },
                            dayHeaders.map((d, i) =>
                                el('div', { className: 'apollo-editor-calendar__day-header', key: 'h' + i }, d)
                            ),
                            days
                        )
                    )
                )
            );
        },
        save: function() {
            return null;
        }
    });

    /**
     * DJ Grid Block
     */
    registerBlockType('apollo-events/dj-grid', {
        title: data.i18n?.djGrid || 'Grid de DJs',
        description: __('Exibe grid de DJs.', 'apollo-events'),
        category: 'apollo-events',
        icon: 'admin-users',
        keywords: ['dj', 'artista', 'grid'],
        attributes: {
            columns: { type: 'number', default: 4 },
            count: { type: 'number', default: 8 },
            showName: { type: 'boolean', default: true },
            showGenre: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({ className: 'wp-block-apollo-events-dj-grid columns-' + attributes.columns });

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(RangeControl, {
                        label: __('Colunas', 'apollo-events'),
                        value: attributes.columns,
                        min: 2,
                        max: 6,
                        onChange: (val) => setAttributes({ columns: val })
                    }),
                    el(RangeControl, {
                        label: __('Quantidade', 'apollo-events'),
                        value: attributes.count,
                        min: 1,
                        max: 24,
                        onChange: (val) => setAttributes({ count: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar nome', 'apollo-events'),
                        checked: attributes.showName,
                        onChange: (val) => setAttributes({ showName: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar gênero', 'apollo-events'),
                        checked: attributes.showGenre,
                        onChange: (val) => setAttributes({ showGenre: val })
                    })
                )
            );

            const cards = [];
            for (let i = 0; i < Math.min(attributes.count, 4); i++) {
                cards.push(
                    el('div', { className: 'apollo-editor-dj-card', key: i },
                        el('div', { className: 'apollo-editor-dj-card__image' },
                            el('span', { className: 'dashicons dashicons-admin-users' })
                        ),
                        attributes.showName && el('h4', { className: 'apollo-editor-dj-card__name' }, 'DJ ' + (i + 1)),
                        attributes.showGenre && el('p', { className: 'apollo-editor-dj-card__genre' }, 'Tech House')
                    )
                );
            }

            return el(Fragment, {},
                inspector,
                el('div', blockProps, cards)
            );
        },
        save: function() {
            return null;
        }
    });

    /**
     * Local Grid Block
     */
    registerBlockType('apollo-events/local-grid', {
        title: data.i18n?.localGrid || 'Grid de Locais',
        description: __('Exibe grid de locais.', 'apollo-events'),
        category: 'apollo-events',
        icon: 'location',
        keywords: ['local', 'venue', 'grid'],
        attributes: {
            columns: { type: 'number', default: 3 },
            count: { type: 'number', default: 6 },
            showAddress: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({ className: 'wp-block-apollo-events-local-grid columns-' + attributes.columns });

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(RangeControl, {
                        label: __('Colunas', 'apollo-events'),
                        value: attributes.columns,
                        min: 2,
                        max: 4,
                        onChange: (val) => setAttributes({ columns: val })
                    }),
                    el(RangeControl, {
                        label: __('Quantidade', 'apollo-events'),
                        value: attributes.count,
                        min: 1,
                        max: 12,
                        onChange: (val) => setAttributes({ count: val })
                    }),
                    el(ToggleControl, {
                        label: __('Mostrar endereço', 'apollo-events'),
                        checked: attributes.showAddress,
                        onChange: (val) => setAttributes({ showAddress: val })
                    })
                )
            );

            const cards = [];
            for (let i = 0; i < Math.min(attributes.count, 3); i++) {
                cards.push(
                    el('div', { className: 'apollo-editor-local-card', key: i },
                        el('div', { className: 'apollo-editor-local-card__image' },
                            el('span', { className: 'dashicons dashicons-location' })
                        ),
                        el('div', { className: 'apollo-editor-local-card__content' },
                            el('h4', { className: 'apollo-editor-local-card__name' }, 'Local ' + (i + 1)),
                            attributes.showAddress && el('p', { className: 'apollo-editor-local-card__address' }, 'Rua Exemplo, 123')
                        )
                    )
                );
            }

            return el(Fragment, {},
                inspector,
                el('div', blockProps, cards)
            );
        },
        save: function() {
            return null;
        }
    });

    /**
     * Search Block
     */
    registerBlockType('apollo-events/search', {
        title: data.i18n?.search || 'Busca de Eventos',
        description: __('Formulário de busca de eventos.', 'apollo-events'),
        category: 'apollo-events',
        icon: 'search',
        keywords: ['search', 'busca', 'filtro'],
        attributes: {
            showDate: { type: 'boolean', default: true },
            showLocal: { type: 'boolean', default: true },
            showDJ: { type: 'boolean', default: true },
            placeholder: { type: 'string', default: '' }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            const inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Configurações', 'apollo-events'), initialOpen: true },
                    el(TextControl, {
                        label: __('Placeholder', 'apollo-events'),
                        value: attributes.placeholder,
                        onChange: (val) => setAttributes({ placeholder: val })
                    })
                ),
                el(PanelBody, { title: __('Filtros', 'apollo-events'), initialOpen: false },
                    el(ToggleControl, {
                        label: __('Filtro de data', 'apollo-events'),
                        checked: attributes.showDate,
                        onChange: (val) => setAttributes({ showDate: val })
                    }),
                    el(ToggleControl, {
                        label: __('Filtro de local', 'apollo-events'),
                        checked: attributes.showLocal,
                        onChange: (val) => setAttributes({ showLocal: val })
                    }),
                    el(ToggleControl, {
                        label: __('Filtro de DJ', 'apollo-events'),
                        checked: attributes.showDJ,
                        onChange: (val) => setAttributes({ showDJ: val })
                    })
                )
            );

            return el(Fragment, {},
                inspector,
                el('div', blockProps,
                    el('div', { className: 'apollo-editor-search' },
                        el('input', {
                            type: 'text',
                            className: 'apollo-editor-search__input',
                            placeholder: attributes.placeholder || __('Buscar eventos...', 'apollo-events'),
                            disabled: true
                        }),
                        el('div', { className: 'apollo-editor-search__filters' },
                            attributes.showDate && el('div', { className: 'apollo-editor-search__filter' },
                                el('label', {}, __('Data', 'apollo-events')),
                                el('input', { type: 'date', disabled: true })
                            ),
                            attributes.showLocal && el('div', { className: 'apollo-editor-search__filter' },
                                el('label', {}, __('Local', 'apollo-events')),
                                el('select', { disabled: true },
                                    el('option', {}, __('Todos', 'apollo-events'))
                                )
                            ),
                            attributes.showDJ && el('div', { className: 'apollo-editor-search__filter' },
                                el('label', {}, __('DJ', 'apollo-events')),
                                el('select', { disabled: true },
                                    el('option', {}, __('Todos', 'apollo-events'))
                                )
                            )
                        ),
                        el('button', { className: 'apollo-editor-search__button', disabled: true },
                            el('span', { className: 'dashicons dashicons-search' }),
                            __('Buscar', 'apollo-events')
                        )
                    )
                )
            );
        },
        save: function() {
            return null;
        }
    });

})(window.wp);
