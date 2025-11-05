(function ($) {
  'use strict';

  function isObject(value) {
    return Object.prototype.toString.call(value) === '[object Object]';
  }

  function parseAjaxData(data) {
    if (!data) {
      return {};
    }

    if (typeof data === 'string') {
      return data.split('&').reduce(function (acc, pair) {
        var parts = pair.split('=');
        if (parts.length === 2) {
          var key = decodeURIComponent(parts[0].replace(/\+/g, ' '));
          var value = decodeURIComponent(parts[1].replace(/\+/g, ' '));
          acc[key] = value;
        }
        return acc;
      }, {});
    }

    if (isObject(data)) {
      return data;
    }

    return {};
  }

  function safeProps(props) {
    if (!props || !isObject(props)) {
      return undefined;
    }

    var sanitized = {};
    Object.keys(props).forEach(function (key) {
      var value = props[key];
      if (value === null || value === undefined) {
        return;
      }
      sanitized[key] = String(value).slice(0, 80);
    });
    return sanitized;
  }

  window.apolloTrackPlausible = function (eventName, props) {
    if (typeof window !== 'undefined' && typeof window.plausible === 'function' && eventName) {
      var cleanProps = safeProps(props);
      if (cleanProps) {
        window.plausible(eventName, { props: cleanProps });
      } else {
        window.plausible(eventName);
      }
    }
  };

  var layoutState = 'list';

  $(document).on('click', '.event_listing', function () {
    var $card = $(this);
    var props = {
      event_id: $card.data('event-id') || '',
      category: $card.data('category') || '',
      month: $card.data('month-str') || '',
      density: $card.data('density') || ''
    };
    window.apolloTrackPlausible('event_card_click', props);
  });

  $(document).ajaxSuccess(function (event, xhr, settings) {
    var payload = parseAjaxData(settings && settings.data);
    if (!payload.action) {
      return;
    }

    if (payload.action === 'load_event_single') {
      var eventId = payload.event_id || payload.eventId || '';
      if (eventId) {
        window.apolloTrackPlausible('event_modal_open', { event_id: eventId });
      }
    }

    if (payload.action === 'filter_events') {
      var filterProps = {};
      if (payload.category && payload.category !== 'all') {
        filterProps.filter_type = 'category';
        filterProps.value = payload.category;
      }
      if (payload.date) {
        filterProps.filter_type = 'month';
        filterProps.value = payload.date;
      }
      if (payload.search) {
        filterProps.filter_type = 'search';
        filterProps.value = payload.search;
      }

      if (filterProps.filter_type) {
        window.apolloTrackPlausible('event_filter_change', filterProps);
      }
    }
  });

  $(document).on('click', '#favoriteTrigger', function () {
    var element = this;
    var eventId = element.getAttribute('data-event-id');

    if (!eventId) {
      var container = element.closest('[data-event-id]');
      if (container) {
        eventId = container.getAttribute('data-event-id');
      }
    }

    if (!eventId) {
      return;
    }

    window.apolloTrackPlausible('event_favorited', {
      event_id: eventId
    });
  });

  $(document).on('click', '#wpem-event-toggle-layout', function () {
    layoutState = layoutState === 'list' ? 'grid' : 'list';
    window.apolloTrackPlausible('event_layout_toggle', { layout: layoutState });
  });

  $(document).on('click', '.event-category', function () {
    var slug = $(this).data('slug') || 'all';
    window.apolloTrackPlausible('event_filter_change', {
      filter_type: 'category',
      value: slug
    });
  });

  $(document).on('click', '#datePrev, #dateNext', function () {
    var direction = this.id === 'datePrev' ? 'prev' : 'next';
    window.apolloTrackPlausible('event_filter_change', {
      filter_type: 'month_navigation',
      value: direction
    });
  });

  $('#eventSearchInput').on('change', function () {
    var value = $(this).val();
    if (value) {
      window.apolloTrackPlausible('event_filter_change', {
        filter_type: 'search',
        value: value
      });
    }
  });
})(jQuery);

