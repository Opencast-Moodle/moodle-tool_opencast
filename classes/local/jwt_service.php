<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JWT Auth service.
 * @package    tool_opencast
 * @copyright  2026 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_opencast\local;

use OpencastApi\Auth\JWT\OcJwtHandler;
use OpencastApi\Auth\JWT\OcJwtClaim;
use OpencastApi\Util\OcUtils;

/**
 * JWT Auth service.
 * @package    tool_opencast
 * @copyright  2026 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jwt_service {
    /**
     * @var string The default config value for algorithm.
     */
    public const CONFIGS_DEFAULT_ALGORITHM = 'ES256';

    /** @var array list of supported algorithms (Asymmetric) */
    public const SUPPORTED_ALGORITHMS = [
        'ES256',
        'ES384',
        'EdDSA',
    ];
    /**
     * @var int The default config value for token duration.
     */
    public const CONFIGS_DEFAULT_TOKEN_DURATION = 15;

    /**
     * @var int The default config value for video proxy token duration.
     */
    public const CONFIGS_DEFAULT_VIDEO_PROXY_TOKEN_DURATION = 60 * 60 * 4;

    /**
     * @var string The default config value for studio role.
     */
    public const CONFIGS_DEFAULT_STUDIO_ROLE = 'ROLE_STUDIO';

    /**
     * @var string The default config value for editor role.
     */
    public const CONFIGS_DEFAULT_EDITOR_ROLE = 'ROLE_EDITOR';

    /**
     * @var string The default config value for annotation role.
     */
    public const CONFIGS_DEFAULT_ANNOTATION_ROLE = 'ROLE_USER';

    /**
     * @var string The default config value for iframe source path.
     */
    public const CONFIGS_DEFAULT_IFRAME_SRC_PATH = '/paella7/ui/watch.html';

    /**
     * @var string The config id for jwt activation.
     */
    public const CONFIG_ID_ACTIVATION = 'jwt_enabled';

    /**
     * @var string The config id for token duration.
     */
    public const CONFIG_ID_TOKEN_DURATION = 'jwt_tokenduration';

    /**
     * @var string The config id for video proxy token duration.
     */
    public const CONFIG_ID_VIDEO_PROXY_TOKEN_DURATION = 'jwt_videoproxytokenduration';

    /**
     * @var string The config id for private key.
     */
    public const CONFIG_ID_PRIVATE_KEY = 'jwt_privatekey';

    /**
     * @var string The config id for algorithm.
     */
    public const CONFIG_ID_ALGORITHM = 'jwt_algorithm';

    /**
     * @var string The config id for player iframe url path.
     */
    public const CONFIG_ID_PLAYER_IFRAME_URL_PATH = 'jwt_playeriframeurlpath';

    /**
     * @var string The config id for studio roles.
     */
    public const CONFIG_ID_STUDIO_ROLES = 'jwt_studioroles';

    /**
     * @var string The config id for editor roles.
     */
    public const CONFIG_ID_EDITOR_ROLES = 'jwt_editorroles';

    /**
     * @var string The config id for annotation roles.
     */
    public const CONFIG_ID_ANNOTATION_ROLES = 'jwt_annotationroles';

    /**
     * @var string The proxy path url for serving the video with interceptions.
     */
    public const VIDEO_PROXY_URL_PATH = '/admin/tool/opencast/jwt_video_proxy.php';

    /**
     * @var string The Opencast redirect path.
     */
    public const REDIRECT_URL_PATH = '/redirect/get';

    /**
     * @var int The timeout milliseconds for the redirect form submission.
     */
    public const REDIRECT_FORM_SUBTIM_TIMEOUT = 250;

    /**
     * @var string The name of the plugin tool_opencast.
     */
    private const PLUGINNAME = 'tool_opencast';

    /**
     * @var array The array of action permissions for annotation service.
     */
    private const ANNOTATION_ACTIONS = [
        'default' => 'annotate',
        'admin' => 'annotate-admin',
    ];

    /**
     * @var string The read action permission granted to Opencast learner.
     */
    private const ACTION_PERMISSION_READ = 'read';

    /**
     * @var string The write action permission granted to Opencast instructors.
     */
    private const ACTION_PERMISSION_WRITE = 'write';

    /**
     * @var bool It determines whether the jwt handler from the Opencast API is there or not,
     * meaning the JWT is rightfully there to use.
     */
    private bool $activated = false;

    /**
     * @var null|OcJwtHandler The Opencast JWT Handler.
     */
    private ?OcJwtHandler $handler = null;

    /**
     * @var int The Opencast id.
     */
    private int $ocinstanceid;

    /**
     * @var array The static array of generated tokens.
     */
    public static $generatetokens = [];

    /**
     * Constructor method.
     * @param int $ocinstanceid Opencast instance id.
     * @param null|OcJwtHandler $handler The Opencast JWT handler.
     * @return void
     */
    public function __construct(int $ocinstanceid, ?OcJwtHandler $handler = null) {
        $this->ocinstanceid = $ocinstanceid;
        $this->handler = $handler;
        $this->activated = !is_null($handler);
    }

    /**
     * Determines whether the JWT service is activated/enabled or not.
     * @return bool Whether the service is activated.
     */
    public function is_enabled(): bool {
        return $this->activated;
    }

    /**
     * Issues a JWT token for the Opencast studio service.
     *
     * The issued token includes studio role claims and user info derived from
     * the current user when available.
     *
     * @return string|null The issued JWT access token or null if deactivated.
     */
    public function issue_jwt_for_ext_service_studio(): ?string {
        $studioroles = [self::CONFIGS_DEFAULT_STUDIO_ROLE];
        $configuredstudioroles = settings_api::get_jwt_studio_roles($this->ocinstanceid);
        if (!empty($configuredstudioroles)) {
            $studioroles = $configuredstudioroles;
        }
        $claim = new OcJwtClaim();
        $this->set_user_info_claim($claim);
        $claim->setRoles($studioroles);
        return $this->issue_access_token($claim);
    }

    /**
     * Issues a JWT token for the Opencast editor service.
     *
     * The issued token includes editor role claims and user info derived from
     * the current user when available.
     *
     * @return string|null The issued JWT access token or null if deactivated.
     */
    public function issue_jwt_for_ext_service_editor(): ?string {
        $editorroles = [self::CONFIGS_DEFAULT_EDITOR_ROLE];
        $configurededitorroles = settings_api::get_jwt_editor_roles($this->ocinstanceid);
        if (!empty($configurededitorroles)) {
            $editorroles = $configurededitorroles;
        }
        $claim = new OcJwtClaim();
        $this->set_user_info_claim($claim);
        $claim->setRoles($editorroles);
        return $this->issue_access_token($claim);
    }

    /**
     * Issues a JWT token for the Opencast annotation service.
     *
     * The token is restricted to the provided annotation identifier and includes
     * custom annotation actions based on the current user's instructor or learner
     * permissions.
     *
     * @param string $identifier The annotation resource identifier used in the token ACL.
     * @return string|null The issued JWT access token or null if deactivated.
     */
    public function issue_jwt_for_ext_service_annotation(string $identifier): ?string {
        $annotatioroles = [self::CONFIGS_DEFAULT_ANNOTATION_ROLE];
        $configuredannotatioroles = settings_api::get_jwt_annotation_roles($this->ocinstanceid);
        if (!empty($configuredannotatioroles)) {
            $annotatioroles = $configuredannotatioroles;
        }
        $claim = new OcJwtClaim();
        $this->set_user_info_claim($claim);
        $claim->setRoles($annotatioroles);

        $customactions = [];
        [$isinstructor, $islearner] = $this->determine_user_permission();
        if ($isinstructor || $islearner) {
            $customactions[] = $isinstructor ? self::ANNOTATION_ACTIONS['admin'] : self::ANNOTATION_ACTIONS['default'];
        }
        $filteredactions = $this->get_user_actions($customactions);
        $eventacl = [
            "$identifier" => $filteredactions,
        ];
        $claim->setEventAcls($eventacl);
        return $this->issue_access_token($claim);
    }

    /**
     * Determines whether the current user has Opencast instructor or learner permissions.
     *
     * Returns a tuple with instructor status first and learner status second.
     *
     * @param int|null $courseid Optional course id to evaluate permissions in a specific course context.
     * @return array [bool $isInstructor, bool $isLearner]
     */
    public function determine_user_permission(?int $courseid = null): array {
        global $COURSE;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        // phpcs:disable
        /** @disregard P1006 Course context extends context. */
        $islearner = has_capability('tool/opencast:learner', $coursecontext);
        /** @disregard P1006 Course context extends context. */
        $isinstructor = has_capability('tool/opencast:instructor', $coursecontext);
        // phpcs:enable
        return [$isinstructor, $islearner];
    }

    /**
     * Populates the user info claims on the provided JWT claim.
     *
     * If the current user has Opencast learner or instructor permissions,
     * their username, full name, and email are added to the claim.
     *
     * @param OcJwtClaim $claim The claim object to update.
     * @return void
     */
    public function set_user_info_claim(OcJwtClaim &$claim) {
        global $USER;
        [$isinstructor, $islearner] = $this->determine_user_permission();
        if ($isinstructor || $islearner) {
            $claim->setUserInfoClaims(
                $USER->username,
                $USER->firstname . ' ' . $USER->lastname,
                $USER->email ?? $USER->email,
            );
        }
    }

    /**
     * Issues a JWT for the given event identifier.
     *
     * This method constructs an event-specific claim with optional custom
     * actions and duration, then issues a new JWT access token.
     *
     * @param string $identifier The event identifier used for the claim ACL.
     * @param array $customactions Optional custom actions to include in the token.
     * @param int|null $duration Optional token duration in minutes.
     * @return string|null The issued JWT access token or null if deactivated.
     */
    public function issue_jwt_for_event(string $identifier, array $customactions = [], ?int $duration = null): ?string {
        $claim = $this->create_oc_claim_for_event($identifier, $customactions, $duration);
        return $this->issue_access_token($claim);
    }

    /**
     * Attaches a JWT token to an event URL using event ACLs.
     *
     * The event ACL includes any custom actions and optional duration, then
     * the resulting JWT is attached to the URL as a query parameter.
     *
     * @param string $url The event URL to protect with JWT.
     * @param string $identifier The event identifier used in the token ACL.
     * @param array $customactions Optional custom actions to include in the event ACL.
     * @param int|null $duration Optional token duration in minutes.
     * @return string The URL updated with a jwt query parameter.
     */
    public function attach_jwt_url_param_event(
        string $url,
        string $identifier,
        array $customactions = [],
        ?int $duration = null
    ): string {
        $claim = $this->create_oc_claim_for_event($identifier, $customactions, $duration);
        return $this->attach_jwt_url_param($url, $claim);
    }

    /**
     * Attaches a JWT token to a series URL using series ACLs.
     *
     * The series ACL is built from the current user's allowed actions and
     * attached to the URL as a jwt query parameter.
     *
     * @param string $url The series URL to protect with JWT.
     * @param string $identifier The series identifier used in the token ACL.
     * @param array $customactions Optional custom actions to include in the series ACL.
     * @return string The URL updated with a jwt query parameter.
     */
    public function attach_jwt_url_param_series(string $url, string $identifier, array $customactions = []): string {
        $claim = new OcJwtClaim();
        $filteredactions = $this->get_user_actions($customactions);
        $seriesacl = [
            "$identifier" => $filteredactions,
        ];
        $claim->setSeriesAcls($seriesacl);

        return $this->attach_jwt_url_param($url, $claim);
    }

    /**
     * Attaches a JWT token to a playlist URL using playlist ACLs.
     *
     * The playlist ACL is built from the current user's allowed actions and
     * attached to the URL as a jwt query parameter.
     *
     * @param string $url The playlist URL to protect with JWT.
     * @param string $identifier The playlist identifier used in the token ACL.
     * @param array $customactions Optional custom actions to include in the playlist ACL.
     * @return string The URL updated with a jwt query parameter.
     */
    public function attach_jwt_url_param_playlist(string $url, string $identifier, array $customactions = []): string {
        $claim = new OcJwtClaim();
        $filteredactions = $this->get_user_actions($customactions);
        $playlistsacl = [
            "$identifier" => $filteredactions,
        ];
        $claim->setPlaylistAcls($playlistsacl);

        return $this->attach_jwt_url_param($url, $claim);
    }

    /**
     * Issues or reuses a JWT access token for the provided claim.
     *
     * If JWT is disabled this returns null. The existing token is reused when
     * valid and the claim matches; otherwise a new token is issued and cached.
     *
     * @param OcJwtClaim $claim The JWT claim to use when issuing the token.
     * @param string|null $accesstoken Optional existing token to validate and reuse.
     * @param bool $forcereissue Force a new token issuance even if the existing token is valid.
     * @return string|null The issued or reused access token, or null if JWT is disabled.
     */
    public function issue_access_token(OcJwtClaim $claim, ?string $accesstoken = null, bool $forcereissue = false): ?string {
        if (!$this->is_enabled()) {
            return null;
        }

        $needsnewaccesstoken = true;

        if (!$forcereissue) {
            if (empty($accesstoken)) {
                $accesstoken = $this->get_local_static_cached_token($claim);
            }
            $needsnewaccesstoken = $this->should_renew_token($accesstoken, $claim);
        }

        if ($needsnewaccesstoken) {
            try {
                $accesstoken = $this->handler->issueToken($claim);
                $this->local_static_cache_token($claim, $accesstoken);
            } catch (\Throwable $th) {
                throw new \moodle_exception('jwt_error_issuingtokenfailed', 'tool_opencast', '', $th->getMessage());
            }
        }

        return $accesstoken;
    }

    /**
     * Recursively attaches JWT query parameters to event publication URLs.
     *
     * This method traverses arrays and objects to find any URL or URI fields,
     * then replaces them with a JWT-protected version for the specified event
     * identifier.
     *
     * @param mixed $data The publication data structure to scan and update.
     * @param string $identifier The event identifier used when issuing the JWT.
     * @return void
     */
    public function attach_jwt_to_event_publication_urls(mixed &$data, string $identifier): void {
        $urlkeys = ['url', 'uri'];
        if (!$this->is_enabled()) {
            return;
        }
        if (is_array($data)) {
            foreach ($data as &$value) {
                $this->attach_jwt_to_event_publication_urls($value, $identifier);
            }
        } else if (is_object($data)) {
            foreach ($data as $key => &$value) {
                if (in_array($key, $urlkeys) && is_string($value)) {
                    $value = $this->attach_jwt_url_param_event($value, $identifier);
                } else {
                    $this->attach_jwt_to_event_publication_urls($value, $identifier);
                }
            }
        }
    }

    /**
     * Builds the iframe source URL used by the JWT player.
     *
     * The generated URL includes the configured player iframe path, replaces
     * the placeholder event identifier, and appends the jwtRefresh flag.
     *
     * @param int $ocinstanceid The Opencast instance id used to resolve the API URL.
     * @param string $identifier The event identifier to insert into the iframe path.
     * @param string|null $baseurl Optional base URL override for the iframe source.
     * @return string The resolved iframe source URL.
     */
    public function generate_iframe_source_url(int $ocinstanceid, string $identifier, ?string $baseurl = null): string {
        if (empty($baseurl)) {
            $baseurl = settings_api::get_apiurl($ocinstanceid);
        }
        $parsedurl = parse_url($baseurl);

        if (!$configpath = settings_api::get_jwt_player_iframe_url_path($ocinstanceid)) {
            $configpath = self::CONFIGS_DEFAULT_IFRAME_SRC_PATH;
        }
        $configpath = '/' . ltrim($configpath, '/');
        $path = str_replace('{id}', $identifier, $configpath);
        $parsedurl['path'] = $path;
        $query = !empty($parsedurl['query']) ? $parsedurl['query'] : '';
        $parsedquery = [];
        parse_str($query, $parsedquery);
        // In case of having /paella.. as the path we make sure that the id exists in the query string.
        if (str_starts_with($configpath, '/paella')) {
            if (empty($parsedquery['id'])) {
                $parsedquery['id'] = $identifier;
            }
        }
        $parsedquery['jwtRefresh'] = 'true';
        $querybuilt = http_build_query($parsedquery, '', '&');
        $parsedurl['query'] = $querybuilt;
        return $this->unparse_url($parsedurl);
    }

    /**
     * Builds the HTML iframe element for a JWT-protected Opencast player.
     *
     * This method generates the iframe source URL, attaches a JWT token,
     * schedules client-side iframe refresh behavior, and renders the iframe via
     * the plugin renderer.
     *
     * @param int $ocinstanceid The Opencast instance id used to resolve the iframe source.
     * @param string $identifier The event or media identifier for the iframe source.
     * @param array $classes Optional CSS classes to apply to the iframe element.
     * @param string|null $baseurl Optional custom base URL for the iframe source.
     * @param string|null $resolution Optional resolution string for the iframe.
     * @param string|null $width Optional width attribute for the iframe.
     * @param string|null $height Optional height attribute for the iframe.
     * @return string|bool The rendered iframe HTML, or false when JWT is disabled.
     */
    public function get_jwt_iframe_player_html(
        int $ocinstanceid,
        string $identifier,
        array $classes = [],
        ?string $baseurl = null,
        ?string $resolution = null,
        ?string $width = null,
        ?string $height = null
    ): string|bool {
        global $PAGE, $COURSE;
        if (!$this->is_enabled()) {
            return false;
        }
        $coursecontext = \context_course::instance($COURSE->id, IGNORE_MISSING);
        $iframeid = uniqid("jwt-iframe-{$identifier}-");
        $src = $this->generate_iframe_source_url($ocinstanceid, $identifier, $baseurl);
        $srcwithjwt = $this->attach_jwt_url_param_event($src, $identifier);
        $PAGE->requires->js_call_amd(
            'tool_opencast/tool_jwt_service',
            'initIframeRefreshToken',
            [
                $coursecontext->id,
                $ocinstanceid,
                $iframeid,
                $identifier,
            ]
        );
        $renderer = $PAGE->get_renderer('tool_opencast');
        return $renderer->render_jwt_iframe(
            $iframeid,
            $srcwithjwt,
            $classes,
            $resolution,
            $width,
            $height,
        );
    }

    /**
     * Extract the base url from the data streams of Paella Player.
     * @param array $streams The Paella Player data stream.
     * @param int $ocinstanceid Opencast instance id
     * @return null|string The extraced url or the configured one, null if non them found.
     */
    public function extract_base_url_from_paella_streams_data(array $streams, int $ocinstanceid): ?string {
        $sources = OcUtils::findValueByKey($streams, 'sources');
        $baseurl = null;
        if (!empty($sources)) {
            $srcurl = OcUtils::findValueByKey($sources, 'src');
            $parsedurl = parse_url($srcurl);
            if (is_array($parsedurl)) {
                $baseurl = $this->unparse_url($parsedurl, true);
            }
        }
        return !empty($baseurl) ? $baseurl : settings_api::get_apiurl($ocinstanceid);
    }

    /**
     * Refreshes an existing JWT access token if it has expired.
     *
     * If the token is still valid, the original token is returned. Otherwise,
     * the expiration is extended and a fresh token is issued.
     *
     * @param int $ocinstanceid The Opencast instance id used to resolve token duration.
     * @param string $accesstoken The current JWT access token string.
     * @return string|null The refreshed access token, the original token if still valid, or null when no token is provided.
     */
    public function refresh_access_token(int $ocinstanceid, string $accesstoken): ?string {
        if (empty($accesstoken)) {
            return null;
        }
        $claim = $this->handler->getOcJwtClaimFromTokenString($accesstoken);
        if ($claim->getExp()->getTimestamp() > time()) {
            return $accesstoken;
        }
        $tokenduration = settings_api::get_jwt_token_duration($ocinstanceid) ?? self::CONFIGS_DEFAULT_TOKEN_DURATION;
        $expiryformatted = OcJwtClaim::generateFormattedDateTimeObject($tokenduration);
        $claim->setExp($expiryformatted);
        return $this->issue_access_token($claim);
    }

    /**
     * Returns the HTML form used for JWT redirecting to the target URL.
     *
     * This method builds a hidden redirect form, schedules AMD JavaScript to
     * submit it after a short timeout, and renders the form via the plugin
     * renderer.
     *
     * @param string $jwt The JWT token to include in the redirect form.
     * @param string $targeturl The destination URL after redirect.
     * @return string|bool The rendered form HTML or false when JWT is disabled.
     */
    public function get_jwt_redirect_form(string $jwt, string $targeturl): string|bool {
        global $PAGE;
        if (!$this->is_enabled()) {
            return false;
        }
        $formid = uniqid("jwt-redirect-form-");
        $redirecturl = $this->generate_redirect_url($targeturl);
        $PAGE->requires->js_call_amd(
            'tool_opencast/tool_jwt_service',
            'submitRedirectForm',
            [
                $formid,
                self::REDIRECT_FORM_SUBTIM_TIMEOUT,
            ]
        );
        $renderer = $PAGE->get_renderer('tool_opencast');
        return $renderer->render_jwt_redirect_form(
            $formid,
            $redirecturl,
            $jwt,
            $targeturl,
        );
    }

    /*************************/
    /*** PRIVATE FUNCTIONS ***/
    /*************************/

    /**
     * Generates the JWT redirect url based on the url.
     * @param string $url The url to reconstruct.
     * @return string The JWT redirect url.
     */
    private function generate_redirect_url(string $url): string {
        $parsedurl = (array) parse_url($url);
        $baseurl = $this->unparse_url($parsedurl, true);
        return rtrim($baseurl, '/') . self::REDIRECT_URL_PATH;
    }

    /**
     * Creates an OcJwtClaim for an event with optional custom actions and duration.
     *
     * @param string $identifier The event identifier used for the event ACL.
     * @param array $customactions Optional custom actions to include in the claim.
     * @param int|null $duration Optional token duration in minutes.
     * @return OcJwtClaim The constructed JWT claim for the event.
     */
    private function create_oc_claim_for_event(string $identifier, array $customactions = [], ?int $duration = null): OcJwtClaim {
        $claim = new OcJwtClaim();
        $filteredactions = $this->get_user_actions($customactions);
        $eventacl = [
            "$identifier" => $filteredactions,
        ];
        $claim->setEventAcls($eventacl);

        if (!empty($duration)) {
            $expiryformatted = OcJwtClaim::generateFormattedDateTimeObject($duration);
            $claim->setExp($expiryformatted);
        }

        $userroles = $this->get_user_deriven_roles();
        $claim->setRoles($userroles);

        return $claim;
    }

    /**
     * Returns derived user roles for the current course context.
     *
     * Learners receive a course-specific "Learner" role, while instructors
     * receive both a course-specific "Instructor" role and any additional
     * custom roles passed in.
     *
     * @param array $customroles Additional roles to merge with the derived roles.
     * @return array The distinct list of derived roles for the current user.
     */
    private function get_user_deriven_roles(array $customroles = []): array {
        global $COURSE;
        $filtereduserroles = [];
        [$isinstructor, $islearner] = $this->determine_user_permission();
        if ($islearner) {
            $filtereduserroles[] = "{$COURSE->id}_Learner";
        }
        if ($isinstructor) {
            $filtereduserroles[] = "{$COURSE->id}_Instructor";
        }
        $filtereduserroles = array_unique(array_merge($filtereduserroles, $customroles));
        return $filtereduserroles;
    }

    /**
     * Get the proper user action array, based on the permission the user has.
     *
     * Users with Learner rights get "read" whereas those with Instructor rights get "read" and "write".
     *
     * @param array $customactions Custom actions to add to the list of actions.
     * @return array The list of user actions.
     */
    private function get_user_actions(array $customactions = []): array {
        $filteredactions = [];
        [$isinstructor, $islearner] = $this->determine_user_permission();
        if ($islearner) {
            $filteredactions[] = self::ACTION_PERMISSION_READ;
        }
        if ($isinstructor) {
            $filteredactions[] = self::ACTION_PERMISSION_WRITE;
            $filteredactions[] = self::ACTION_PERMISSION_READ;
        }
        $filteredactions = array_unique(array_merge($filteredactions, $customactions));
        return $filteredactions;
    }

    /**
     * Appends or updates the JWT parameter on a URL.
     *
     * If the URL already contains a JWT, the existing token is reused when valid.
     * Otherwise this method issues a fresh token for the provided claim.
     *
     * @param string $url The destination URL to attach the JWT to.
     * @param OcJwtClaim $claim The JWT claim used to issue or refresh the token.
     * @return string The URL with the attached jwt query parameter.
     */
    private function attach_jwt_url_param(string $url, OcJwtClaim $claim): string {
        if (!$this->is_enabled()) {
            return $url;
        }

        $parsedurl = parse_url($url);
        $query = $parsedurl['query'] ?? '';
        $parsedquery = [];
        parse_str($query, $parsedquery);

        $accesstoken = null;

        if (!empty($parsedquery['jwt'])) {
            $accesstoken = $parsedquery['jwt'];
        }

        $accesstoken = $this->issue_access_token($claim, $accesstoken);

        if (!empty($accesstoken)) {
            $parsedquery['jwt'] = $accesstoken;
        }

        $newquery = http_build_query($parsedquery, '', '&');
        if (!empty($newquery)) {
            $parsedurl['query'] = $newquery;
        }

        return $this->unparse_url($parsedurl);
    }

    /**
     * Get the cached access token if existing based on the claim.
     * @param OcJwtClaim $claim The claim to look for.
     * @return null|string The cached access token if exists or null otherwise.
     */
    private function get_local_static_cached_token(OcJwtClaim $claim): ?string {
        $cachekey = $this->generate_cache_key_from_claim($claim);
        if (isset(self::$generatetokens[$cachekey]) && !empty(self::$generatetokens[$cachekey])) {
            return self::$generatetokens[$cachekey];
        }
        return null;
    }

    /**
     * Caches the access token statically.
     * @param OcJwtClaim $claim The claim to of the access token.
     * @param string $accesstoken The access token to cache
     * @return void
     */
    private function local_static_cache_token(OcJwtClaim $claim, string $accesstoken): void {
        $cachekey = $this->generate_cache_key_from_claim($claim);
        self::$generatetokens[$cachekey] = $accesstoken;
    }

    /**
     * Generate a unique caching key for the a claim.
     * @param OcJwtClaim $claim The claim to generate key for.
     * @return string The unique cache key.
     */
    private function generate_cache_key_from_claim(OcJwtClaim $claim): string {
        $cachablevalues = [];
        $cachablevalues[OcJwtClaim::NAME] = $claim->getName() ?? null;
        $cachablevalues[OcJwtClaim::SUB] = $claim->getSub() ?? null;
        $cachablevalues[OcJwtClaim::EMAIL] = $claim->getEmail() ?? null;
        $cachablevalues[OcJwtClaim::OC] = $claim->getAclsClaims() ?? null;
        $cachablevalues[OcJwtClaim::ROLES] = $claim->getRoles() ?? null;
        return serialize($cachablevalues);
    }

    /**
     * Determines whether the existing JWT access token must be renewed.
     *
     * The token is renewed when it is missing, invalid, or the new claim differs
     * from the already-issued token's claim payload.
     *
     * @param mixed $accesstoken The current JWT access token, if any.
     * @param OcJwtClaim $claim The claim to compare against the token's current claim.
     * @return bool True when a new token should be issued, false otherwise.
     */
    private function should_renew_token(mixed $accesstoken, OcJwtClaim $claim): bool {
        if (empty($accesstoken)) {
            return true;
        }

        if (!$this->handler->validateToken($accesstoken)) {
            return true;
        }

        $oldclaim = $this->handler->getOcJwtClaimFromTokenString($accesstoken);

        if ($claim->getNbf() !== $oldclaim->getNbf()) {
            return true;
        }

        if ($claim->getSub() !== $oldclaim->getSub()) {
            return true;
        }

        if ($claim->getEmail() !== $oldclaim->getEmail()) {
            return true;
        }

        if ($claim->getName() !== $oldclaim->getName()) {
            return true;
        }

        $aclsclaims = $claim->getAclsClaims() ?? [];
        $oldaclsclaims = $oldclaim->getAclsClaims() ?? [];

        if (!$this->ensure_equal_array_claims($aclsclaims, $oldaclsclaims)) {
            return true;
        }

        $rolesclaim = $claim->getRoles() ?? [];
        $oldrolesclaim = $oldclaim->getRoles() ?? [];

        if (!$this->ensure_equal_array_claims($rolesclaim, $oldrolesclaim)) {
            return true;
        }

        return false;
    }

    /**
     * Check if two claim arrays are equal by sorting them first.
     * @param array $firstclaim The first array of claims.
     * @param array $secondclaim The second array of claims.
     * @return bool Whether two arrays are equal.
     */
    private function ensure_equal_array_claims(array $firstclaim, array $secondclaim): bool {
        if (
            (empty($firstclaim) && !empty($secondclaim)) ||
            (!empty($firstclaim) && empty($secondclaim))
        ) {
            return false;
        }

        $this->sort_array_recursive($firstclaim);
        $this->sort_array_recursive($secondclaim);

        return $firstclaim === $secondclaim;
    }

    /**
     * Makes sure the targeted array is sorted either requalrily or by the keys.
     * @param array $array The targeted array.
     * @return void
     */
    private function sort_array_recursive(array $array): void {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sort_array_recursive($value);
                if (array_is_list($value)) {
                    sort($value);
                } else {
                    ksort($value);
                }
            }
        }
        ksort($array);
    }

    /**
     * Reconstructs a URL string from its parsed components.
     *
     * This method takes an associative array similar to the output of `parse_url()`
     * and rebuilds the original URL string, including scheme, host, port, user, password,
     * path, query, and fragment if they are present.
     *
     * @param array $parsedurl An associative array containing parts of a URL
     *                          (keys: scheme, host, port, user, pass, path, query, fragment).
     * @param bool $onlybaseurl A flag to return only the base url or not.
     *
     * @return string The reconstructed URL.
     */
    public function unparse_url(array $parsedurl, bool $onlybaseurl = false): string {
        $scheme   = isset($parsedurl['scheme']) ? $parsedurl['scheme'] . '://' : '';
        $host     = isset($parsedurl['host']) ? $parsedurl['host'] : '';
        $port     = isset($parsedurl['port']) ? ':' . $parsedurl['port'] : '';
        $user     = isset($parsedurl['user']) ? $parsedurl['user'] : '';
        $pass     = isset($parsedurl['pass']) ? ':' . $parsedurl['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path = '';
        $query = '';
        $fragment = '';
        if ($onlybaseurl === false) {
            $path     = isset($parsedurl['path']) ? $parsedurl['path'] : '';
            $query    = isset($parsedurl['query']) ? '?' . $parsedurl['query'] : '';
            $fragment = isset($parsedurl['fragment']) ? '#' . $parsedurl['fragment'] : '';
        }

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /************************/
    /*** STATIC FUNCTIONS ***/
    /************************/

    /**
     * Returns associative array of supported algorithms from OcJwtHandler.
     * @return array list of supported algorithms.
     */
    public static function get_supported_algorithms(): array {
        $algorithmsarray = self::SUPPORTED_ALGORITHMS;
        return array_combine($algorithmsarray, $algorithmsarray);
    }

    /**
     * Generates the configuration id related to JWT Service of each instance.
     *
     * @param string $configid The targeted config id.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string generated config id.
     */
    public static function generate_config_id(string $configid, int $ocintanceid, bool $withpluginname): string {
        $id = $configid . '_' . $ocintanceid;
        if ($withpluginname) {
            $id = self::PLUGINNAME . '/' . $id;
        }
        return $id;
    }

    /**
     * Return the config id for activation setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id for the jwt activation
     */
    public static function get_activation_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_ACTIVATION, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for token duration setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_token_duration_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_TOKEN_DURATION, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for token duration setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_video_proxy_token_duration_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_VIDEO_PROXY_TOKEN_DURATION, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for private key setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_private_key_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_PRIVATE_KEY, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for algorithm setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_algorithm_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_ALGORITHM, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for studio roles setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_studio_roles_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_STUDIO_ROLES, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for editor roles setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_editor_roles_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_EDITOR_ROLES, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for annotation roles setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_annotation_roles_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_ANNOTATION_ROLES, $ocintanceid, $withpluginname);
    }

    /**
     * Return the config id for player iframe url path setting of each instance.
     * @param int $ocintanceid Opencast instance id
     * @param bool $withpluginname A flag to add the plugin name prefix.
     * @return string config id
     */
    public static function get_player_iframe_url_path_config_id(int $ocintanceid, bool $withpluginname = false): string {
        return self::generate_config_id(self::CONFIG_ID_PLAYER_IFRAME_URL_PATH, $ocintanceid, $withpluginname);
    }

    /**
     * Get the JWT complete configuration to pass to the Opencast API instance.
     * @param int $ocinstance Opencast instance id.
     * @return null|array The JWT configuration. If the JWT is not configuraed the return result in null.
     */
    public static function get_jwt_api_config(int $ocinstance): ?array {
        $jwt = null;
        $isjwtenabled = settings_api::get_jwt_activation($ocinstance);
        $privatekey = settings_api::get_jwt_private_key($ocinstance);
        $tokenduration = (int) (settings_api::get_jwt_token_duration($ocinstance) ?? self::CONFIGS_DEFAULT_TOKEN_DURATION);
        $algorithm = settings_api::get_jwt_algorithm($ocinstance) ?? self::CONFIGS_DEFAULT_ALGORITHM;
        if ($isjwtenabled && !empty($privatekey)) {
            $jwt = [
                'private_key' => $privatekey,
                'algorithm' => $algorithm,
                'expiration' => $tokenduration,
            ];
        }

        return $jwt;
    }

    /**
     * Generates the proxy url by which serving video is intercepted to decide whether to apply JWT authentication or not.
     * @param array $params the url params to add
     * @return \moodle_url the proxy url to serve the video.
     */
    public static function get_video_proxy_url(array $params): \moodle_url {
        return new \moodle_url(self::VIDEO_PROXY_URL_PATH, $params);
    }

    /**
     * Returns the suggested lifetime for video proxy JWT tokens.
     *
     * If the Moodle session timeout is configured, the suggested token duration
     * is aligned with it. Otherwise the default proxy token duration is used.
     *
     * @return int Suggested token duration in minutes.
     */
    public static function get_suggested_video_proxy_token_duration(): int {
        global $CFG;
        $besttokenduration = self::CONFIGS_DEFAULT_VIDEO_PROXY_TOKEN_DURATION;
        if (!empty($CFG->sessiontimeout)) {
            $besttokenduration = (int) $CFG->sessiontimeout;
        }
        return $besttokenduration;
    }
}
