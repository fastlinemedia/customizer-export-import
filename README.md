# Customizer Export/Import #

The Customizer Export/Import plugin allows you to export or import your WordPress customizer settings from directly within the customizer interface! If your theme makes use of the WordPress customizer for its settings, this plugin is for you!

Please visit our blog for more info on the [Customizer Export/Import plugin](http://www.wpbeaverbuilder.com/wordpress-customizer-export-import-plugin/?utm_source=external&utm_medium=github&utm_campaign=customizer-export-description).

## New! Export Options! ##

The Customizer Export/Import plugin previously only exported options saved as theme mods using the [get_theme_mods](http://codex.wordpress.org/Function_Reference/get_theme_mods) function, but that is no more! The Customizer Export/Import plugin now exports settings saved as options as well!

## How It Works ##

Exporting customizer settings is easy. Click the export button from within the customizer and a file will automatically begin downloading with your settings. Export files are named after your theme and can only be used to import settings for the theme or child theme that they came from. Export files contain a serialized dump of mods retrieved using the [get_theme_mods](http://codex.wordpress.org/Function_Reference/get_theme_mods) function or customizer settings saved as options.

Importing customizer settings is just as easy. Choose the export file you would like to import, select whether you would like to download and import images (similar to importing posts), and finally, click the import button. Once your settings have been imported the page will refresh and your new design will be displayed.

## Exporting Custom Options ##

Developers can also have arbitrary options that aren't part of the customizer exported by using the cei_export_option_keys filter. Those options can be exported and imported by adding your option key to the array of options that will be exported as shown below.

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
