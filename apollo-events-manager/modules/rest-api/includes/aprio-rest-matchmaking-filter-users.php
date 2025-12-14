<?php

// phpcs:ignoreFile
class APRIO_REST_Filter_Users_Controller extends APRIO_REST_CRUD_Controller
{
    protected $namespace = 'aprio';
    protected $rest_base = 'filter-users';

    public function __construct()
    {
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    /**
     * This function used to register routes
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        $auth_controller = new APRIO_REST_Authentication();
        // General filter
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'aprio_compatibilidade_filter_users' ],
                'permission_callback' => [ $this, 'check_user_permission' ],
                'args'                => [
                    'profession' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'company_name' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'country' => [
                        'required'          => false,
                        'type'              => 'array',
                        'sanitize_callback' => function ($arr) { return array_map('sanitize_text_field', (array) $arr); },
                    ],
                    'city' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'experience' => [
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'skills' => [
                        'required'          => false,
                        'type'              => 'array',
                        'sanitize_callback' => function ($arr) { return array_map('sanitize_text_field', (array) $arr); },
                    ],
                    'interests' => [
                        'required'          => false,
                        'type'              => 'array',
                        'sanitize_callback' => function ($arr) { return array_map('sanitize_text_field', (array) $arr); },
                    ],
                    'event_id' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'user_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'search' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'per_page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 5,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ],

                ],
            ]
        );
    }

    /**
     * Check user permission for compatibilidade endpoints.
     *
     * @return bool|WP_Error True if user is logged in, WP_Error otherwise.
     */
    public function check_user_permission()
    {
        if (! is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to use compatibilidade.', 'apollo-events-manager'),
                [ 'status' => 401 ]
            );
        }

        return true;
    }

    /**
     * This function used to filter users
     *
     * @since 1.1.0
     * @param $request
     * @return WP_REST_Response
     */
    public function aprio_compatibilidade_filter_users($request)
    {
        global $wpdb;

        if (! get_option('enable_compatibilidade', false)) {
            return new WP_REST_Response(
                [
                    'code'    => 403,
                    'status'  => 'Disabled',
                    'message' => 'Matchmaking functionality is not enabled.',
                    'data'    => null,
                ],
                403
            );
        }
        $auth_check = $this->aprio_check_authorized_user();
        if ($auth_check) {
            return self::prepare_error_for_response(405);
        } else {
            $event_id = intval($request->get_param('event_id'));
            $user_id  = intval($request->get_param('user_id'));

            // Step 1: Validate registration
            $registered_user_ids = [];
            if ($event_id && $user_id) {
                $registration_query = new WP_Query(
                    [
                        'post_type'      => 'event_registration',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'post_author'    => $user_id,
                    ]
                );

                $has_registration = false;
                foreach ($registration_query->posts as $registration_id) {
                    if (wp_get_post_parent_id($registration_id) == $event_id) {
                        $has_registration = true;

                        break;
                    }
                }

                if (! $has_registration) {
                    return new WP_REST_Response(
                        [
                            'code'    => 403,
                            'status'  => 'Forbidden',
                            'message' => 'You are not registered for this event.',
                            'data'    => [],
                        ],
                        403
                    );
                }

                // Collect all registered users for this event
                $attendee_query = new WP_Query(
                    [
                        'post_type'      => 'event_registration',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                    ]
                );

                foreach ($attendee_query->posts as $registration_id) {
                    if (wp_get_post_parent_id($registration_id) == $event_id) {
                        $uid = intval(get_post_field('post_author', $registration_id));
                        if ($uid && ! in_array($uid, $registered_user_ids)) {
                            $registered_user_ids[] = $uid;
                        }
                    }
                }
            }//end if

            if (empty($registered_user_ids)) {
                return new WP_REST_Response(
                    [
                        'code'    => 200,
                        'status'  => 'OK',
                        'message' => 'No attendees found for this event.',
                        'data'    => [],
                    ],
                    200
                );
            }

            // Step 2: Build user data
            $profession_terms = get_event_registration_taxonomy_list('event_registration_professions');
            // [slug => name]
            $skills_terms    = get_event_registration_taxonomy_list('event_registration_skills');
            $interests_terms = get_event_registration_taxonomy_list('event_registration_interests');

            $users_data = [];
            foreach ($registered_user_ids as $uid) {
                if ($uid == $user_id) {
                    continue;
                }
                if (! get_user_meta($uid, '_compatibilidade_profile', true)) {
                    continue;
                }

                $photo = get_aprio_user_profile_photo($uid) ?: plugins_url('apollo-events-manager/assets/images/user-profile-photo.png');

                // Normalize organization logo
                $organization_logo = get_user_meta($uid, '_organization_logo', true);
                $organization_logo = maybe_unserialize($organization_logo);
                if (is_array($organization_logo)) {
                    $organization_logo = reset($organization_logo);
                }
                $organization_logo = $organization_logo ?: plugins_url('apollo-events-manager/assets/images/organisation-icon.jpg');
                // Profession
                $profession_value = get_user_meta($uid, '_profession', true);
                $profession_slug  = $profession_value;
                if ($profession_value && ! isset($profession_terms[ $profession_value ])) {
                    $found_slug = array_search($profession_value, $profession_terms);
                    if ($found_slug) {
                        $profession_slug = $found_slug;
                    }
                }

                // --- Skills ---
                $skills_slugs = [];
                $skills_arr   = maybe_unserialize(get_user_meta($uid, '_skills', true));
                if (is_array($skills_arr)) {
                    foreach ($skills_arr as $skill) {
                        $term = get_term_by('slug', $skill, 'event_registration_skills');
                        if (! $term) {
                            $term = get_term_by('name', $skill, 'event_registration_skills');
                        }
                        if (! $term) {
                            $term = get_term_by('id', $skill, 'event_registration_skills');
                        }
                        if ($term) {
                            $skills_slugs[] = $term->slug;
                        }
                    }
                }
                $skills_slugs = array_filter($skills_slugs);
                // remove blanks
                $skills_serialized = serialize($skills_slugs);

                // --- Interests ---
                $interests_slugs = [];
                $interests_arr   = maybe_unserialize(get_user_meta($uid, '_interests', true));
                if (is_array($interests_arr)) {
                    foreach ($interests_arr as $interest) {
                        $term = get_term_by('slug', $interest, 'event_registration_interests');
                        if (! $term) {
                            $term = get_term_by('name', $interest, 'event_registration_interests');
                        }
                        if (! $term) {
                            $term = get_term_by('id', $interest, 'event_registration_interests');
                        }
                        if ($term) {
                            $interests_slugs[] = $term->slug;
                        }
                    }
                }
                $interests_slugs      = array_filter($interests_slugs);
                $interests_serialized = serialize($interests_slugs);

                $countries     = aprio_get_all_countries();
                $country_value = get_user_meta($uid, '_country', true);
                $country_code  = '';
                if ($country_value) {
                    if (isset($countries[ $country_value ])) {
                        $country_code = $country_value;
                    } else {
                        $country_code = array_search($country_value, $countries);
                    }
                }
                // Get organization country value from user meta
                $org_country_value = get_user_meta($uid, '_organization_country', true);
                $org_country_code  = '';
                if ($org_country_value) {
                    if (isset($countries[ $org_country_value ])) {
                        $org_country_code = $org_country_value;
                    } else {
                        $org_country_code = array_search($org_country_value, $countries);
                    }
                }
                $users_data[] = [
                    'user_id'                  => $uid,
                    'display_name'             => get_the_author_meta('display_name', $uid),
                    'first_name'               => get_user_meta($uid, 'first_name', true),
                    'last_name'                => get_user_meta($uid, 'last_name', true),
                    'email'                    => get_userdata($uid)->user_email,
                    'compatibilidade_profile'  => get_user_meta($uid, '_compatibilidade_profile', true),
                    'profile_photo'            => $photo,
                    'profession'               => $profession_slug,
                    'experience'               => get_user_meta($uid, '_experience', true),
                    'company_name'             => get_user_meta($uid, '_company_name', true),
                    'country'                  => $country_code,
                    'city'                     => get_user_meta($uid, '_city', true),
                    'about'                    => get_user_meta($uid, '_about', true),
                    'skills'                   => $skills_serialized,
                    'interests'                => $interests_serialized,
                    'message_notification'     => get_user_meta($uid, '_message_notification', true),
                    'organization_name'        => get_user_meta($uid, '_organization_name', true),
                    'organization_logo'        => $organization_logo,
                    'organization_country'     => $org_country_code,
                    'organization_city'        => get_user_meta($uid, '_organization_city', true),
                    'organization_description' => get_user_meta($uid, '_organization_description', true),
                    'organization_website'     => get_user_meta($uid, '_organization_website', true),
                    'available_for_meeting'    => get_user_meta($uid, '_available_for_meeting', true),
                    'approve_profile_status'   => get_user_meta($uid, '_approve_profile_status', true),
                ];
            }//end foreach

            // Step 3: Apply filters
            $profession = sanitize_text_field($request->get_param('profession'));
            $country    = $request->get_param('country');
            $skills     = $request->get_param('skills');
            $interests  = $request->get_param('interests');
            $search     = sanitize_text_field($request->get_param('search'));

            $filtered_users = array_filter(
                $users_data,
                function ($user) use ($profession, $country, $skills, $interests, $search) {
                    if ($profession && strtolower($user['profession']) !== strtolower($profession)) {
                        return false;
                    }
                    if (! empty($country) && is_array($country) && ! in_array($user['country'], $country)) {
                        return false;
                    }
                    if (! empty($skills) && is_array($skills)) {
                        if (! array_intersect($skills, $user['skills'])) {
                            return false;
                        }
                    }
                    if (! empty($interests) && is_array($interests)) {
                        if (! array_intersect($interests, $user['interests'])) {
                            return false;
                        }
                    }
                    if ($search) {
                        $haystack_parts = [];
                        foreach ($user as $key => $val) {
                            if (is_array($val)) {
                                $haystack_parts = array_merge($haystack_parts, $val);
                            } else {
                                $haystack_parts[] = $val;
                            }
                        }
                        $haystack = strtolower(implode(' ', $haystack_parts));
                        if (strpos($haystack, strtolower($search)) === false) {
                            return false;
                        }
                    }

                    return true;
                }
            );

            // Step 4: Pagination
            $per_page    = max(1, (int) $request->get_param('per_page'));
            $page        = max(1, (int) $request->get_param('page'));
            $total       = count($filtered_users);
            $offset      = ($page - 1) * $per_page;
            $paged_users = array_slice($filtered_users, $offset, $per_page);

            return new WP_REST_Response(
                [
                    'code'    => 200,
                    'status'  => 'OK',
                    'message' => 'Users retrieved successfully.',
                    'data'    => [
                        'total_post_count' => $total,
                        'current_page'     => $page,
                        'last_page'        => ceil($total / $per_page),
                        'total_pages'      => ceil($total / $per_page),
                        'users'            => array_values($paged_users),
                    ],
                ],
                200
            );
        }//end if
    }
}
new APRIO_REST_Filter_Users_Controller();
