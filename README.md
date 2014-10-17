# Easy Pure CSS http://purecss.io usage for WordPress themes

Activating the plugin doesn't do anything by itself. You'll want to use
add_theme_support().

Somewhere in your "after_setup_theme" action handler, do this

    add_theme_support( "pure-css" );

This loads the latest version of Pure CSS base library using
"wp_enqueue_scripts" action.

# Load more Pure modules

    add_theme_support( "pure-css", array( "grids", "forms" ) );

# Override Pure CSS version to load

    add_filter( "pure_css_version", function( $version ) { return "0.4.2"; } );

# Built-in integrations

 * Hybrid Base Dynamic (custom fork of Hybrid Base)
 * Grid Columns
 * TODO Gravity Forms
