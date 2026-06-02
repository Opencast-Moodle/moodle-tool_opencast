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
 * Renderer for opencast tool.
 *
 * @package    tool_opencast
 * @copyright  2026 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_opencast_renderer extends plugin_renderer_base {
    /**
     * Render a redirect form that posts a JWT to a target URL.
     *
     * @param string $formid The HTML id attribute for the form.
     * @param string $redirecturl The form action URL where the JWT will be submitted.
     * @param string $jwt The JSON Web Token to include as a hidden form field.
     * @param string $targeturl The target URL to which the redirect should ultimately point.
     * @return string|bool The rendered HTML for the JWT redirect form, or false on failure.
     */
    public function render_jwt_redirect_form(string $formid, string $redirecturl, string $jwt, string $targeturl): string|bool {
        $context = new \stdClass();
        $context->formid = $formid;
        $context->redirecturl = $redirecturl;
        $context->jwt = $jwt;
        $context->targeturl = $targeturl;
        $html = $this->output->notification(get_string('jwt_redirect_info', 'tool_opencast'), 'info');
        $html .= $this->render_from_template('tool_opencast/jwt_redirect_form', $context);
        return $html;
    }

    /**
     * Render an iframe that loads a JWT-secured player URL.
     *
     * @param string $iframeid The HTML id attribute for the iframe element.
     * @param string $src The iframe source URL.
     * @param array $classes CSS classes to apply to the wrapper and/or iframe.
     * @param string|null $resolution Optional resolution metadata for the iframe.
     * @param string|null $width Optional width value for the iframe container.
     * @param string|null $height Optional height value for the iframe container.
     * @return string|bool The rendered HTML for the JWT iframe wrapper, or false on failure.
     */
    public function render_jwt_iframe(
        string $iframeid,
        string $src,
        array $classes = [],
        ?string $resolution = null,
        ?string $width = null,
        ?string $height = null
    ): string|bool {
        $context = new \stdClass();
        $context->iframeid = $iframeid;
        $context->src = $src;
        $wrapperclasses = null;
        $iframeclasses = null;
        if (!empty($classes)) {
            if (array_is_list($classes)) {
                $wrapperclasses = implode(' ', $classes);
            } else {
                $wrapperclasses = !empty($classes['wrapper']) ? implode(' ', $classes['wrapper']) : null;
                $iframeclasses = !empty($classes['iframe']) ? implode(' ', $classes['iframe']) : null;
            }
        }
        $context->wrapperclasses = $wrapperclasses;
        $context->iframeclasses = $iframeclasses;
        $context->resolution = $resolution;
        $context->width = $this->normalize_css_sizes($width);
        $context->height = $this->normalize_css_sizes($height);
        return $this->render_from_template('tool_opencast/player_iframe_jwt', $context);
    }

    /**
     * Normalize a CSS size value for use in width/height attributes.
     *
     * @param string|int|float|null $value The raw size value.
     * @return string|null A normalized CSS size string, or null when the value is empty or invalid.
     */
    private function normalize_css_sizes(string|int|float|null $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $value . 'px';
        }

        $value = trim((string) $value);

        $allowedunitspattern = '/^\d+(\.\d+)?(px|rem|em|%|vw|vh|vmin|vmax|cm|mm|in|pt|pc)$/i';

        if (preg_match($allowedunitspattern, $value)) {
            return $value;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        $statics = ['auto', 'inherit', 'initial', 'unset'];
        if (in_array($value, $statics)) {
            return $value;
        }

        return null;
    }
}
