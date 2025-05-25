<?php

if (!defined('ABSPATH')) exit;

/**
 * Amadast Shipping Settings API Wrapper Class
 *
 * Provides settings management for the Amadast Shipping plugin.
 *
 * @version 1.3 (27-Sep-2016)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link https://tareq.co Tareq Hasan
 * @example example/oop-example.php How to use the class
 */
class AMDSP_SETTINGS
{

    protected $settings_sections = [];

    protected $settings_fields = [];

    private $allowed_tags = [
        'input' => [
            'type' => [],
            'name' => [],
            'value' => [],
            'id' => [],
            'class' => [],
            'placeholder' => [],
            'checked' => [],
            'disabled' => [],
            'min' => [],
            'max' => [],
            'step' => [],
        ],
        'select' => [
            'name' => [],
            'id' => [],
            'class' => [],
        ],
        'option' => [
            'value' => [],
            'selected' => [],
            'class' => [],
        ],
        'textarea' => [
            'name' => [],
            'id' => [],
            'class' => [],
            'rows' => [],
            'cols' => [],
            'placeholder' => [],
        ],
        'p' => [
            'class' => [],
        ],
        'fieldset' => [
            'class' => [],
        ],
        'label' => [
            'for' => [],
            'class' => [],
        ],
        'br' => [
        ],
    ];

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    function admin_enqueue_scripts()
    {
        wp_enqueue_style('wp-color-picker');

        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery');

        wp_register_script('selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.min.js', ['jquery'], '1.0.10', ['in_footer' => true]);
        wp_enqueue_script('selectWoo');
        wp_register_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.3');
        wp_enqueue_style('select2');

        wp_register_script('init-select2', AMDSP_URL . 'assets/js/select2.min.js', ['selectWoo', 'wp-i18n'], '2.1.1', ['in_footer' => true]);
        wp_enqueue_script('init-select2');

        wp_register_script('admin-page', AMDSP_URL . 'assets/js/admin.min.js', ['selectWoo'], '2.1.1', ['in_footer' => true]);
        wp_enqueue_script('admin-page');

        if (amdsp_need_translation()) {
            wp_set_script_translations('init-select2', 'amadast-shipping-wp');
        }
    }

    function set_sections($sections)
    {
        $this->settings_sections = $sections;

        return $this;
    }

    function add_section($section)
    {
        $this->settings_sections[] = $section;

        return $this;
    }

    function set_fields($fields)
    {
        $this->settings_fields = $fields;

        return $this;
    }

    function add_field($section, $field)
    {
        $defaults = [
            'name' => '',
            'label' => '',
            'desc' => '',
            'type' => 'text'
        ];

        $arg = wp_parse_args($field, $defaults);
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    function admin_init()
    {
        foreach ($this->settings_sections as $section) {
            if (false === get_option($section['id'])) {
                add_option(sanitize_key($section['id']));
            }

            $callback = null;
            if (isset($section['desc']) && !empty($section['desc'])) {

                $callback = function () use ($section) {
                    echo wp_kses_post($section['desc']);
                };
            } elseif (isset($section['callback']) && is_callable($section['callback'])) {
                $callback = $section['callback'];
            }

            add_settings_section(
                sanitize_key($section['id']),
                $section['title'],
                $callback,
                sanitize_key($section['id'])
            );
        }

        foreach ($this->settings_fields as $section => $fields) {
            foreach ($fields as $field) {
                $id = sanitize_key($field['id'] ?? $field['name']);
                $name = sanitize_key($field['name']);
                $type = $field['type'] ?? 'text';
                $label = $field['label'] ?? '';
                $callback = isset($field['callback']) && is_callable($field['callback'])
                    ? $field['callback']
                    : (method_exists($this, 'callback_' . $type) ? [$this, 'callback_' . $type] : null);

                $args = [
                    'id' => $id,
                    'class' => $field['class'] ?? $name,
                    'label_for' => "{$section}[{$name}]",
                    'desc' => $field['desc'] ?? '',
                    'name' => $label,
                    'section' => sanitize_key($section),
                    'size' => $field['size'] ?? null,
                    'options' => $field['options'] ?? [],
                    'std' => $field['default'] ?? '',
                    'sanitize_callback' => isset($field['sanitize_callback']) && is_callable($field['sanitize_callback'])
                        ? $field['sanitize_callback']
                        : false,
                    'type' => sanitize_key($type),
                    'placeholder' => $field['placeholder'] ?? null,
                    'min' => $field['min'] ?? '',
                    'max' => $field['max'] ?? '',
                    'step' => $field['step'] ?? '',
                    'attributes' => array_map('esc_attr', ($field['attributes'] ?? [])),
                ];

                add_settings_field(
                    "{$section}[{$name}]",
                    $label,
                    $callback,
                    sanitize_key($section),
                    sanitize_key($section),
                    $args
                );
            }
        }

        foreach ($this->settings_sections as $section) {
            register_setting(
                sanitize_key($section['id']),
                sanitize_key($section['id']),
                [$this, 'sanitize_options']
            );
        }
    }

    function render_html($html)
    {
        echo wp_kses($html, $this->allowed_tags);
    }

    function render_html_attributes(array $attributes)
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            // Ensure the attribute key is valid
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-_]*$/', $key)) {
                continue; // Skip invalid attribute names
            }

            if (is_bool($value)) {
                // Render boolean attributes (like 'checked', 'disabled') properly
                if ($value) {
                    $html .= ' ' . esc_attr($key);
                }
            } else {
                // Render other attributes with key="value"
                $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }

        return $html;
    }

    public function get_field_description($args)
    {
        if (!empty($args['desc'])) {
            $desc = sprintf('<p class="description">%s</p>', $args['desc']);
        } else {
            $desc = '';
        }

        return $desc;
    }

    function callback_text($args)
    {
        $value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
        $size = $args['size'] ?? 'regular';
        $type = $args['type'] ?? 'text';
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $html_attrs = $this->render_html_attributes($args['attributes']);

        $html = sprintf(
            '<input type="%1$s" class="%2$s-text" id="myplugin-%4$s" name="%3$s[%4$s]" value="%5$s"%6$s %7$s/>',
            $type,
            $size,
            $args['section'],
            $args['id'],
            $value,
            $placeholder,
            $html_attrs
        );

        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_url($args)
    {
        $args['type'] = 'url';
        $this->callback_text($args);
    }

    function callback_number($args)
    {
        $value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
        $size = $args['size'] ?? 'regular';
        $type = 'number';
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $min = isset($args['min']) && $args['min'] !== '' ? ' min="' . $args['min'] . '"' : '';
        $max = isset($args['max']) && $args['max'] !== '' ? ' max="' . $args['max'] . '"' : '';
        $step = isset($args['step']) && $args['step'] !== '' ? ' step="' . $args['step'] . '"' : '';
        $html_attrs = $this->render_html_attributes($args['attributes']);

        $html = sprintf(
            '<input type="%1$s" class="%2$s-text" id="myplugin-%4$s" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s%10$s/>',
            $type,
            $size,
            $args['section'],
            $args['id'],
            $value,
            $placeholder,
            $min,
            $max,
            $step,
            $html_attrs
        );

        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_checkbox($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']) === 'on' ? 'on' : 'off';

        $html = '<fieldset>';
        $html .= sprintf(
            '<label for="myplugin-%1$s[%2$s]">',
            $args['section'],
            $args['id']
        );
        $html .= sprintf(
            '<input type="hidden" name="%1$s[%2$s]" value="off" />',
            $args['section'],
            $args['id']
        );
        $html .= sprintf(
            '<input type="checkbox" class="checkbox" id="myplugin-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />',
            $args['section'],
            $args['id'],
            checked($value, 'on', false)
        );
        $html .= sprintf('%1$s</label>', $args['desc']);
        $html .= '</fieldset>';

        $this->render_html($html);
    }

    function callback_multicheck($args)
    {
        $value = (array)$this->get_option($args['id'], $args['section'], $args['std']);
        $html = '<fieldset>';

        foreach ($args['options'] as $key => $label) {
            $checked = in_array($key, $value);
            $html .= sprintf(
                '<label for="myplugin-%1$s[%2$s][%3$s]">',
                $args['section'], $args['id'], $key
            );
            $html .= sprintf(
                '<input type="checkbox" class="checkbox" id="myplugin-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />',
                $args['section'], $args['id'], $key, checked($checked, true, false)
            );
            $html .= sprintf('%1$s</label><br>', $label);
        }

        $html .= $this->get_field_description($args);
        $html .= '</fieldset>';

        $this->render_html($html);
    }

    function callback_radio($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $html = '<fieldset>';

        foreach ($args['options'] as $key => $label) {
            $html .= sprintf(
                '<label for="myplugin-%1$s[%2$s][%3$s]">',
                $args['section'], $args['id'], $key
            );
            $html .= sprintf(
                '<input type="radio" class="radio" id="myplugin-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />',
                $args['section'], $args['id'], $key, checked($value, $key, false)
            );
            $html .= sprintf('%1$s</label><br>', $label);
        }

        $html .= $this->get_field_description($args);
        $html .= '</fieldset>';

        $this->render_html($html);
    }

    function callback_select($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size = $args['size'] ?? 'regular';
        $class = isset($args['class']) ? $args['class'] . ' ' . $size . '-text' : $size . '-text';
        $placeholder = $args['placeholder'] ?? '';
        $html_attrs = $this->render_html_attributes($args['attributes']);

        $html = sprintf(
            '<select class="%1$s" name="%2$s[%3$s]" id="%3$s" placeholder="%4$s" %5$s>',
            $class, $args['section'], $args['id'], $placeholder, $html_attrs
        );

        foreach ($args['options'] as $key => $label) {
            $html .= sprintf('<option value="%s"%s>%s</option>', $key, selected($value, $key, false), $label);
        }

        $html .= '</select>';
        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_select2($args)
    {
        $args['placeholder'] = 'انتخاب کنید';
        $args['class'] .= ' amdsp-select2';

        $this->callback_select($args);
    }

    function callback_textarea($args)
    {
        $value = esc_textarea($this->get_option($args['id'], $args['section'], $args['std']));
        $size = $args['size'] ?? 'regular';

        $html = sprintf(
            '<textarea rows="5" cols="55" class="%1$s-text" id="%3$s" name="%2$s[%3$s]" placeholder="%4$s">%5$s</textarea>',
            $size, $args['section'], $args['id'], $args['placeholder'], $value
        );
        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_html($args)
    {
        echo wp_kses_post($this->get_field_description($args));
    }

    function callback_wysiwyg($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size = $args['size'] ?? '500px';

        echo '<div style="max-width: ' . esc_attr($size) . ';">';

        $editor_settings = [
            'teeny' => true,
            'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
            'textarea_rows' => 10
        ];

        if (isset($args['options']) && is_array($args['options'])) {
            $editor_settings = array_merge($editor_settings, $args['options']);
        }

        wp_editor($value, $args['section'] . '-' . $args['id'], $editor_settings);

        echo '</div>';

        echo wp_kses_post($this->get_field_description($args));
    }

    function callback_file($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size = $args['size'] ?? 'regular';
        $id = $args['section'] . '[' . $args['id'] . ']';
        $label = $args['options']['button_label'] ?? __('انتخاب فایل', 'amadast-shipping-wp');

        $html = sprintf('<input type="text" class="%1$s-text amdsp-url" id="%3$s" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value);
        $html .= '<input type="button" class="button amdsp-browse" value="' . $label . '" />';
        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_password($args)
    {
        $value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
        $size = $args['size'] ?? 'regular';

        $html = sprintf('<input type="password" class="%1$s-text" id="%3$s" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value);
        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_color($args)
    {
        $value = esc_attr($this->get_option($args['id'], $args['section'], $args['std']));
        $size = $args['size'] ?? 'regular';

        $html = sprintf('<input type="text" class="%1$s-text amdsp-color-picker-field" id="%3$s" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std']);
        $html .= $this->get_field_description($args);

        $this->render_html($html);
    }

    function callback_pages($args)
    {
        $dropdown_args = [
            'selected' => esc_attr($this->get_option($args['id'], $args['section'], $args['std'])),
            'name' => $args['section'] . '[' . $args['id'] . ']',
            'id' => $args['section'] . '[' . $args['id'] . ']',
            'echo' => 0
        ];

        $html = wp_dropdown_pages(array_map('esc_attr', $dropdown_args));

        $this->render_html($html);
    }

    function sanitize_options($options)
    {
        if (!$options) {
            return $options;
        }

        foreach ($options as $option_slug => $option_value) {
            $sanitize_callback = $this->get_sanitize_callback($option_slug);

            // If callback is set, call it
            if ($sanitize_callback) {
                $options[$option_slug] = call_user_func($sanitize_callback, $option_value);
            }
        }

        return $options;
    }

    function get_sanitize_callback($slug = '')
    {
        if (empty($slug)) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach ($this->settings_fields as $section => $options) {
            foreach ($options as $option) {
                if ($option['name'] != $slug) {
                    continue;
                }

                // Return the callback name
                return isset($option['sanitize_callback']) && is_callable($option['sanitize_callback']) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    function get_option($option, $section, $default = '')
    {
        $options = get_option($section);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }

    function show_navigation()
    {
        $html = '<h2 class="nav-tab-wrapper">';

        $count = count($this->settings_sections);

        // don't show the navigation if only one section exists
        if ($count === 1) {
            return;
        }

        foreach ($this->settings_sections as $tab) {
            $html .= sprintf('<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title']);
        }

        $html .= '</h2>';

        $this->render_html($html);
    }

    function show_forms()
    {
        ?>
        <div class="metabox-holder">
            <?php
            $page_index = 0;
            foreach ($this->settings_sections as $form) :
                ?>
                <div id="<?php echo esc_attr($form['id']); ?>" class="amdsp-group" <?php echo $page_index !== 0 ? 'style="display: none;"' : '' ?>>
                    <form method="post" action="options.php">
                        <?php
                        do_action('amdsp_form_top_' . $form['id'], $form);
                        settings_fields($form['id']);
                        do_settings_sections($form['id']);
                        do_action('amdsp_form_bottom_' . $form['id'], $form);
                        if (isset($this->settings_fields[$form['id']])):
                            ?>
                            <div style="padding-left: 10px">
                                <?php submit_button(); ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                <?php
                $page_index++;
            endforeach;
            ?>
        </div>
        <?php
    }
}
