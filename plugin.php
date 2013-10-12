<?php
/*
Plugin Name: Pure CSS Theme Features
Plugin URI: http://www.wordpress.org/plugins/wordpress-pure-css/
Description: Load Pure CSS modules http://purecss.io/customize/ via add_theme_support()
Author: Leho Kraav
Version: 1.3.1
Author URI: http://leho.kraav.com/wordpress/wordpress-pure-css/
*/

Pure_CSS::on_load();

class Pure_CSS {
    static $version = '1.3.1'; # TODO get_plugin_data()

    static $pure_css_version = '0.3.0'; #
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
        add_action( 'wp_enqueue_scripts', array( __CLASS__, "enqueue_scripts" ) );
        add_action( 'plugins_loaded', array( __CLASS__, "filter_grid_columns" ) );
    }

    static function enqueue_scripts() {
        $pure_css_version = apply_filters( "pure_css_version", Pure_CSS::$pure_css_version );

        $pure_css_minified = apply_filters( "pure_css_minified", Pure_CSS::$pure_css_minified );
        $pure_css_minified = $pure_css_minified ? "-min" : "";

        $enqueue = "http://yui.yahooapis.com/combo?pure/$pure_css_version/base$pure_css_minified.css";

        $supports = get_theme_support( "pure-css" );

        if ( is_array( $supports ) && isset( $supports[ 0 ] ) ) {
            foreach ( $supports[ 0 ] as $s ) {
                if ( ! in_array( $s, Pure_CSS::$supports ) ) continue;

                $enqueue .= "&pure/$pure_css_version/$s$pure_css_minified.css";
            }
        }

        wp_enqueue_style( "pure", $enqueue, false, null );
    }

    static function filter_grid_columns() {
        if ( class_exists( "Grid_Columns" ) ) {
            add_action( "gc_column_class", array( __CLASS__, "gc_column_class" ), 10, 2 );
            add_action( "gc_row_class", array( __CLASS__, "gc_row_class" ) );
        }
    }

    static function gc_column_class( $classes, $attr ) {
        extract( $attr );

        if ( $push ) $span += $push;

        if ( $grid % $span === 0 ) {
            $grid = $grid / $span;
            $span = 1;
        }

        if ( sizeof( $class ) ) $class[] = "pure-u-$span-$grid";

        return $class;
    }

    static function gc_row_class() {
        return array( "pure-g-r" );
    }
}
?>
