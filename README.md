# Customizer Export/Import #

The Customizer Export/Import plugin allows you to export or import your WordPress customizer settings from directly within the customizer interface! If your theme makes use of the WordPress customizer for its settings, this plugin is for you!

Please visit our blog for more info on the [Customizer Export/Import plugin](http://www.wpbeaverbuilder.com/wordpress-customizer-export-import-plugin/?utm_source=external&utm_medium=github&utm_campaign=customizer-export-description).

## How It Works ##

Exporting customizer settings is easy. Click the export button from within the customizer and a file will automatically begin downloading with your settings. Export files are named after your theme and can only be used to import settings for the theme or child theme that they came from. Export files contain a serialized dump of mods retrieved using the [get_theme_mods](http://codex.wordpress.org/Function_Reference/get_theme_mods) function.

Importing customizer settings is just as easy. Choose the export file you would like to import, select whether you would like to download and import images (similar to importing posts), and finally, click the import button. Once your settings have been imported the page will refresh and your new design will be displayed.

## Exporting Custom Options ##

Some plugins or themes may create controls that don't store their settings as theme mods and instead store them in the WordPress options table. These settings can also be exported and imported by adding your option key to the array of options that will be exported as shown below.

```
function my_export_option_keys( $keys ) {
	$keys[] = 'my_option_key';
	$keys[] = 'another_option_key';
	return $keys;
}

add_filter( 'cei_export_option_keys', 'my_export_option_keys' );
```

## Contribute! ##

We'd love to hear your feedback as to how we could improve the Customizer Export/Import plugin, or better yet, see theme developers actively contribute! Don't hesitate to let us know if you're interested in contributing as we would gladly have others on board.
