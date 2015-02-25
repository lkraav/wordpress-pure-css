<?php
/*
Plugin Name: Pure CSS Theme Features
Plugin URI: http://conversionready.com/plugins/wordpress-pure-css/
Description: Load Pure CSS modules http://purecss.io/customize/ via add_theme_support()
Author: Leho Kraav
Version: 1.6.0
Author URI: http://github.com/lkraav/wordpress-pure-css/
*/

Pure_CSS::on_load();

class Pure_CSS {
    static $version = "1.5.1"; # TODO get_plugin_data()

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
        add_action( "after_setup_theme", array( __CLASS__, "filter_hybrid_base_dynamic" ) );
        add_action( "after_setup_theme", array( __CLASS__, "filter_hybrid_base_dynamic_020" ) );
        add_action( "wp_enqueue_scripts", array( __CLASS__, "enqueue_scripts" ) );
        add_action( "plugins_loaded", array( __CLASS__, "filter_grid_columns" ) );
    }

    static function enqueue_scripts() {
        $pure_css_version = apply_filters( "pure_css_version", self::$pure_css_version );

        $pure_css_minified = apply_filters( "pure_css_minified", self::$pure_css_minified );
        $pure_css_minified = $pure_css_minified ? "-min" : "";

        $enqueue = "https://yui-s.yahooapis.com/combo?pure/$pure_css_version/base$pure_css_minified.css";

        $supports = get_theme_support( "pure-css" );

        if ( is_array( $supports ) && isset( $supports[0] ) ) {
            foreach ( $supports[0] as $s ) {
                if ( ! in_array( $s, self::$supports ) ) {
                    continue;
                }

                $enqueue .= "&pure/$pure_css_version/$s$pure_css_minified.css";
            }

            if ( in_array( "grids", $supports[0] ) ) {
                $enqueue .= "&pure/$pure_css_version/grids-responsive$pure_css_minified.css";
            }
        }

        wp_enqueue_style( "pure", $enqueue, false, null );
        wp_dequeue_style( "grid-columns" );
    }

    static function filter_grid_columns() {
        if ( class_exists( "Grid_Columns" ) ) {
            add_action( "gc_column_class", array( __CLASS__, "gc_column_class" ), 10, 2 );
            add_action( "gc_column_defaults", array( __CLASS__, "gc_column_defaults" ) );
            add_action( "gc_row_class", array( __CLASS__, "gc_row_class" ) );
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
        add_filter( "{$prefix}_main_class", function( $class ) { return $class .= " pure-g"; } );

        # collapse by default, media query overrides set in theme
        add_filter( "{$prefix}_content_class", function( $class ) { return $class .= " pure-u-1"; } );
        add_filter( "{$prefix}_sidebar_class", function( $class ) { return $class .= " pure-u-1"; } );
    }

    static function gc_column_class( $classes, $attr ) {
        /*
         * None of this matters with pure-0.5.0 and should be removed?
         * Plugin should always return lowest mobile view class "pure-u-1"
         * All higher media queries handled in content class parameter
         * Column shortcode grid/span attrs only determine pure-g groupings
        if ( $push ) $span += $push;

        # must simplify fractions
        if ( $grid % $span === 0 ) {
            $grid = $grid / $span;
            $span = 1;
        }
        else {
            $gcd = self::gcd( $grid, $span );

            $grid = $grid / $gcd;
            $span = $span / $gcd;
        }

        if ( sizeof( $class ) ) {
            $class[] = ( 1 === $span && 1 === $grid ?
                "pure-u-1" :
                "pure-u-$span-$grid"
            );
        }
        */

        # content should be able to override minimum mobile grid
        # https://github.com/yahoo/pure/issues/437
        $default_unit = array( "pure-u-1" );

        if ( sizeof( preg_grep( "/pure-u-\d/", $classes ) ) ) {
            $default_unit = array();
        }

        return array_merge( $classes, $default_unit );
    }

    static function gc_column_defaults( $defaults ) {
        $defaults["grid"] = 1;
        $defaults["class"] = "pure-u-1";

        return $defaults;
    }

    static function gc_row_class() {
        return array( "pure-g" );
    }

    static function gc_allowed_grids( $grids ) {
        $grids[] = 1;
        $grids[] = 24;

        return $grids;
    }
}
?>
