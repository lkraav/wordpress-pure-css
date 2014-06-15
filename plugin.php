<?php
/*
Plugin Name: Pure CSS Theme Features
Plugin URI: http://www.wordpress.org/plugins/wordpress-pure-css/
Description: Load Pure CSS modules http://purecss.io/customize/ via add_theme_support()
Author: Leho Kraav
Version: 1.4.3
Author URI: http://leho.kraav.com/wordpress/wordpress-pure-css/
*/

Pure_CSS::on_load();

class Pure_CSS {
    static $version = "1.4.3"; # TODO get_plugin_data()

    static $pure_css_version = "0.4.2"; #
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
        add_action( "wp_enqueue_scripts", array( __CLASS__, "enqueue_scripts" ) );
        add_action( "plugins_loaded", array( __CLASS__, "filter_grid_columns" ) );
    }

    static function enqueue_scripts() {
        $pure_css_version = apply_filters( "pure_css_version", self::$pure_css_version );

        $pure_css_minified = apply_filters( "pure_css_minified", self::$pure_css_minified );
        $pure_css_minified = $pure_css_minified ? "-min" : "";

        $enqueue = "https://yui-s.yahooapis.com/combo?pure/$pure_css_version/base$pure_css_minified.css";

        $supports = get_theme_support( "pure-css" );

        if ( is_array( $supports ) && isset( $supports[ 0 ] ) ) {
            foreach ( $supports[ 0 ] as $s ) {
                if ( ! in_array( $s, self::$supports ) ) continue;

                $enqueue .= "&pure/$pure_css_version/$s$pure_css_minified.css";
            }
        }

        wp_enqueue_style( "pure", $enqueue, false, null );
        wp_dequeue_style( "grid-columns" );
    }

    static function filter_grid_columns() {
        if ( class_exists( "Grid_Columns" ) ) {
            add_action( "gc_column_class", array( __CLASS__, "gc_column_class" ), 10, 2 );
            add_action( "gc_row_class", array( __CLASS__, "gc_row_class" ) );
            add_filter( "gc_allowed_grids", array( __CLASS__, "gc_allowed_grids" ) );
        }
    }

    static function gc_column_class( $classes, $attr ) {
        extract( $attr );

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

        $class[] = "pure-u-$span-$grid"; # converts string to array

        return array_merge( $class, $classes );
    }

    static function gc_row_class() {
        return array( "pure-g-r" );
    }

    static function gc_allowed_grids( $grids ) {
        $grids[] = 24;

        return $grids;
    }

    # http://stackoverflow.com/questions/12412782/simplify-a-fraction
    private function gcd( $a, $b ) {
        $a = abs( $a );
        $b = abs( $b );

        if ( $a < $b ) list( $b, $a ) = array( $a, $b );
        if ( $b == 0 ) return $a;

        $r = $a % $b;

        while ( $r > 0 ) {
            $a = $b;
            $b = $r;
            $r = $a % $b;
        }

        return $b;
    }
}
?>
