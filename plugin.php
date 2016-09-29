<?php
/*
Plugin Name: Pure CSS Theme Features
Plugin URI: http://conversionready.com/plugins/wordpress-pure-css/
Description: Load Pure CSS modules http://purecss.io/customize/ via add_theme_support()
Author: Leho Kraav
Version: 1.6.1
Author URI: http://github.com/lkraav/wordpress-pure-css/
*/

Pure_CSS::on_load();

class Pure_CSS {
    static $version = "1.6.1"; # TODO get_plugin_data()

    static $pure_css_version = "0.6.0"; #
    static $pure_css_minified = true; #

    # http://purecss.io/customize/
    static $supports = array(
        "base",
        "buttons",
        "forms",
        "forms-nr",
        "grids",
        "grids-nr",
        "menus",
        "menus-nr",
        "tables",
    );

    static function on_load() {
        add_action( "after_setup_theme", array( __CLASS__, "filter_get_the_image" ), 15 );
        add_action( "after_setup_theme", array( __CLASS__, "filter_gravityforms" ), 15 );
        add_action( "after_setup_theme", array( __CLASS__, "filter_grid_columns" ), 15 );
        add_action( "after_setup_theme", array( __CLASS__, "filter_hybrid_base_dynamic" ) );
        add_action( "after_setup_theme", array( __CLASS__, "filter_hybrid_base_dynamic_020" ) );
        add_action( "after_setup_theme", array( __CLASS__, "filter_menus" ), 15 );
        add_action( "wp_enqueue_scripts", array( __CLASS__, "enqueue_scripts" ) );
    }

    static function enqueue_scripts() {
        $pure_css_version = apply_filters( "pure_css_version", self::$pure_css_version );

        $pure_css_minified = apply_filters( "pure_css_minified", self::$pure_css_minified );
        $pure_css_minified = $pure_css_minified ? "-min" : "";

        $cdn = "https://yui-s.yahooapis.com";
        $url = "$cdn/combo?pure/$pure_css_version/base$pure_css_minified.css";

        $supports = get_theme_support( "pure-css" );

        if ( is_array( $supports ) && isset( $supports[0] ) ) {
            foreach ( $supports[0] as $s ) {
                if ( ! in_array( $s, self::$supports, true ) ) {
                    continue;
                }

                $url .= "&pure/$pure_css_version/$s$pure_css_minified.css";
            }

            if ( in_array( "grids", $supports[0], true ) ) {
                $url .= "&pure/$pure_css_version/grids-responsive$pure_css_minified.css";
            }
        }

        $enqueue = array(
            "pure" => array( "url" => $url ),
            "pure-grids-responsive-old-ie" => array( "data" => "lte IE 8", "url" => "$cdn/pure/$pure_css_version/grids-responsive-old-ie$pure_css_minified.css" ),
        );

        $enqueue = apply_filters( "pure_css_enqueue", $enqueue );

        foreach ( $enqueue as $slug => $resource ) {
            $version = null;

            if ( isset( $resource["version"] ) ) {
                $version = $resource["version"];
            }

            wp_enqueue_style( $slug, $resource["url"], false, $version );

            if ( isset( $resource["data"] ) && $data = $resource["data"] ) {
                wp_style_add_data( $slug, "conditional", $data );
            }
        }

        if ( class_exists( "Grid_Columns" ) ) {
            wp_dequeue_style( "grid-columns" );
        }
    }

    static function get_the_image_args( $args ) {
        $class = "pure-img";

        if ( is_array( $args["image_class"] ) ) {
            $args["image_class"][] = $class;
        } else {
            $args["image_class"] .= " {$class}";
        }

        return $args;
    }

    static function filter_get_the_image( $args ) {
        if ( ! function_exists( "get_the_image" ) ) {
            return;
        }

        add_filter( "get_the_image_args", array( __CLASS__, "get_the_image_args" ) );
    }

    static function gform_field_content( $content, $field, $value, $lead_id, $form_id ) {
        return $content; # wip, implement a specific opt-in?

        if ( "email" === $field->type ) {
            $dom = new DOMDocument();
            $dom->loadHTML( '<?xml encoding="UTF-8">' . $content );

            $label = $dom->getElementsByTagName( "label" )->item( 0 );
            $input = $dom->getElementsByTagName( "input" )->item( 0 );

            $content = html_entity_decode( $dom->saveHtml( $label ) . $dom->saveHtml( $input ) );
        }

        return $content;
    }

    static function gform_field_input( $input, $field, $value, $lead_id, $form_id ) {
        if ( "honeypot" !== $field->type ) {
            true;
        }

        return $input;
    }

    static function gform_form_tag( $form_tag, $form ) {
        return str_replace( "class='", "class='pure-form ", $form_tag );
    }

    static function gform_next_button( $button, $form ) {
        return str_replace( "class='", "class='pure-button ", $button );
    }

    static function gform_previous_button( $button, $form ) {
        return str_replace( "class='", "class='pure-button ", $button );
    }

    static function gform_submit_button( $button, $form ) {
        return str_replace( "class='", "class='pure-button pure-button-primary ", $button );
    }

    static function filter_gravityforms() {
        if ( ! class_exists( "GFForms" ) ) {
            return;
        }

        $supports = get_theme_support( "pure-css" );

        if ( ! is_array( $supports ) ) {
            return;
        }

        if ( ! isset( $supports[0] ) ) {
            return;
        }

        if ( in_array( "buttons", $supports[0], true ) ) {
            add_filter( "gform_next_button", array( __CLASS__, "gform_next_button" ), 10, 2 );
            add_filter( "gform_previous_button", array( __CLASS__, "gform_previous_button" ), 10, 2 );
            add_filter( "gform_submit_button", array( __CLASS__, "gform_submit_button" ), 10, 2 );
        }

        if ( in_array( "forms", $supports[0], true ) ) {
            add_filter( "gform_field_content", array( __CLASS__, "gform_field_content" ), 10, 5 );
            add_filter( "gform_field_input", array( __CLASS__, "gform_field_input" ), 10, 5 );
            add_filter( "gform_form_tag", array( __CLASS__, "gform_form_tag" ), 10, 2 );
        }
    }

    static function filter_grid_columns() {
        if ( class_exists( "Grid_Columns" ) ) {
            add_action( "gc_column_class", array( __CLASS__, "gc_column_class" ), 10, 2 );
            add_action( "gc_column_defaults", array( __CLASS__, "gc_column_defaults" ) );
            add_action( "gc_row_class", array( __CLASS__, "gc_row_class" ), 10, 2 );
            add_filter( "gc_allowed_grids", array( __CLASS__, "gc_allowed_grids" ) );
        }
    }

    static function filter_hybrid_base_dynamic() {
        return;
    }

    static function filter_hybrid_base_dynamic_020() {
        if ( ! function_exists( "hybrid_get_prefix" ) ) {
            return;
        }

        $prefix = hybrid_get_prefix();

        # main div is always a grid
        add_filter( "{$prefix}_main_class", function( $class ) {
            return $class .= " pure-g";
        } );

        # collapse by default, media query overrides set in theme
        add_filter( "{$prefix}_content_class", function( $class ) {
            return $class .= " column pure-u-1";
        } );
        add_filter( "{$prefix}_sidebar_class", function( $class ) {
            return $class .= " column pure-u-1";
        } );
    }

    static function filter_menus() {
        $supports = get_theme_support( "pure-css" );

        if ( ! is_array( $supports ) ) {
            return;
        }

        if ( ! isset( $supports[0] ) ) {
            return;
        }

        if ( in_array( "menus", $supports[0], true ) ) {
            add_filter( "nav_menu_css_class", array( __CLASS__, "filter_nav_menu_css_class" ), 10, 4 );
            add_filter( "wp_nav_menu_args", array( __CLASS__, "filter_wp_nav_menu" ) );
        }
    }

    static function has_class_pure_u( $classes ) {
        return count( preg_grep( "/pure-u-\d/", $classes ) );
    }

    static function filter_nav_menu_css_class( $classes, $item, $args, $depth ) {
        if ( ! self::has_class_pure_u( $classes ) ) {
            $classes[] = "pure-u-1";
        }

        $classes[] = "column";

        return $classes;
    }

    static function filter_wp_nav_menu( $args ) {
        $args["menu_class"] .= " pure-g";
        return $args;
    }

    static function gc_column_class( $classes, $attr ) {
        # content should be able to override minimum mobile grid https://github.com/yahoo/pure/issues/437
        if ( ! self::has_class_pure_u( $classes ) ) {
            $classes[] = "pure-u-1";
        }

        return $classes;
    }

    static function gc_column_defaults( $defaults ) {
        $defaults["grid"] = 1;
        $defaults["class"] = "pure-u-1";

        return $defaults;
    }

    static function gc_row_class( $row_classes, $attr ) {
        /* order matters! */
        return array( "pure-g-" . $attr["grid"], "pure-g" );
    }

    static function gc_allowed_grids( $grids ) {
        $grids[] = 1;
        $grids[] = 24;

        return $grids;
    }
}
